<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Encoder;

use BadMethodCallException;
use FFI;
use FFI\CData;
use FFI\Exception as FFIException;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Stringable;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\Exception\LibError;

use function count;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function ini_get;
use function is_string;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PATH_SEPARATOR;
use const PHP_OS_FAMILY;
use const PHP_SAPI;

final class LibEncoder implements Encoder, Stringable
{
    private const FFI_SCOPE = 'tiktoken';

    private static LibFFIProxy|null $ffi = null;
    private static string|null $libPath = null;
    private CData $bpe;

    /**
     * @param non-empty-string $vocabFile
     * @param non-empty-string $pattern
     */
    public function __construct(private string $encoding, string $vocabFile, string $pattern)
    {
        if (! file_exists($vocabFile)) {
            throw new InvalidArgumentException(sprintf('The vocab file %s does not exist', $vocabFile));
        }

        $ptr = self::getFFI()->init($pattern, $vocabFile);

        if ($ptr === null) {
            throw new LibError(self::getFFI()->last_error_message() ?? 'Initialization failed');
        }

        $this->bpe = $ptr;
    }

    public function __destruct()
    {
        self::getFFI()->destroy($this->bpe);
    }

    #[Override]
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    #[Override]
    public function __toString(): string
    {
        return sprintf('LibEncoder(encoding="%s")', $this->encoding);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function encode(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $tokens = self::getFFI()->encode($this->bpe, $text);

        if ($tokens === null) {
            throw new LibError(self::getFFI()->last_error_message() ?? 'Encoding failed');
        }

        $res = [];

        for ($i = 0; $i < $tokens->len; $i++) {
            $res[] = $tokens->data[$i];
        }

        self::getFFI()->free_tokens($tokens);

        return $res;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function encodeInChunks(string $text, int $maxTokensPerChunk): array
    {
        throw new BadMethodCallException('Not implemented yet');
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function decode(array $tokens): string
    {
        if (count($tokens) === 0) {
            return '';
        }

        $ranks = self::getFFI()->new(sprintf('uint32_t[%d]', count($tokens)));
        $index = 0;

        foreach ($tokens as $token) {
            $ranks[$index] = $token;
            $index++;
        }

        $result = self::getFFI()->decode($this->bpe, $ranks, count($tokens));

        if ($result === null) {
            throw new LibError(self::getFFI()->last_error_message() ?? 'Decoding failed');
        }

        return $result;
    }

    private static function getFFI(): LibFFIProxy
    {
        if (self::$ffi === null) {
            try {
                self::$ffi = new LibFFIProxy(FFI::scope(self::FFI_SCOPE));
            } catch (FFIException $e) {
                if (ini_get('ffi.enable') === 'preload' && PHP_SAPI !== 'cli') {
                    throw new RuntimeException(
                        sprintf(
                            'FFI_SCOPE "%s" not found (ffi.enable=preload requires you to call %s::preload() in preload script)',
                            self::FFI_SCOPE,
                            self::class,
                        ),
                        previous: $e,
                    );
                }

                self::$ffi = new LibFFIProxy(FFI::cdef(self::loadCDef(), self::getLibFile()));
            }
        }

        return self::$ffi;
    }

    public static function init(string|null $libPath = null): void
    {
        self::$libPath = $libPath;
    }

    public static function preload(string|null $libPath = null): void
    {
        self::init($libPath);

        $tmpFile = tempnam(sys_get_temp_dir(), 'tiktoken-ffi');

        if ($tmpFile === false) {
            throw new RuntimeException('Could not create temporary file');
        }

        try {
            $library = sprintf('#define FFI_LIB "%s"', self::getLibFile()) . "\n";
            file_put_contents($tmpFile, $library . self::loadCDef());
            FFI::load($tmpFile);
        } finally {
            unlink($tmpFile);
        }
    }

    private static function loadCDef(): string
    {
        $headerFile = dirname(__DIR__) . '/lib.h';

        if (! file_exists($headerFile)) {
            throw new RuntimeException(sprintf('File "%s" does not exist', $headerFile));
        }

        $cdef = file_get_contents($headerFile);

        if ($cdef === false) {
            throw new RuntimeException(sprintf('Unable to read file %s', $headerFile));
        }

        return $cdef;
    }

    private static function getLibFile(): string
    {
        $filename = match (PHP_OS_FAMILY) {
            'Darwin' => 'libtiktoken_php.dylib',
            'Windows' => 'tiktoken_php.dll',
            default => 'libtiktoken_php.so',
        };

        foreach (self::resolveLibPaths() as $path) {
            $libFile = $path . DIRECTORY_SEPARATOR . $filename;

            if (file_exists($libFile)) {
                return $libFile;
            }
        }

        throw new RuntimeException(sprintf('Lib %s file not found', $filename));
    }

    /** @return iterable<string> */
    private static function resolveLibPaths(): iterable
    {
        if (self::$libPath !== null) {
            yield self::$libPath;
        }

        foreach (['TIKTOKEN_LIB_PATH', 'LD_LIBRARY_PATH'] as $envVar) {
            $value = getenv($envVar);

            if (! is_string($value)) {
                continue;
            }

            yield from explode(PATH_SEPARATOR, $value);
        }
    }
}
