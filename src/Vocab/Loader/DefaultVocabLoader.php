<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Vocab\Loader;

use Override;
use Yethee\Tiktoken\Exception\IOError;
use Yethee\Tiktoken\Vocab\Vocab;
use Yethee\Tiktoken\Vocab\VocabLoader;

use function error_get_last;
use function fclose;
use function file_exists;
use function fopen;
use function hash_equals;
use function hash_final;
use function hash_init;
use function hash_update_file;
use function hash_update_stream;
use function is_dir;
use function is_resource;
use function is_writable;
use function mkdir;
use function sha1;
use function sprintf;
use function stream_copy_to_stream;

use const DIRECTORY_SEPARATOR;

final class DefaultVocabLoader implements VocabLoader
{
    /** @param non-empty-string $cacheDir */
    public function __construct(private readonly string $cacheDir)
    {
    }

    #[Override]
    public function load(string $uri, string|null $checksum = null): Vocab
    {
        return Vocab::fromFile($this->loadFile($uri, $checksum));
    }

    #[Override]
    public function loadFile(string $uri, string|null $checksum = null): string
    {
        $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR . sha1($uri);

        if (file_exists($cacheFile) && $this->checkHash($cacheFile, $checksum)) {
            return $cacheFile;
        }

        if (! is_dir($this->cacheDir) && ! @mkdir($this->cacheDir, 0750, true)) {
            throw new IOError(sprintf(
                'Directory does not exist and cannot be created: %s',
                $this->cacheDir,
            ));
        }

        if (! is_writable($this->cacheDir)) {
            throw new IOError(sprintf('Directory is not writable: %s', $this->cacheDir));
        }

        $stream = fopen($uri, 'r');

        if ($stream === false) {
            throw new IOError(sprintf('Could not open stream for URI: %s', $uri));
        }

        try {
            $cacheStream = fopen($cacheFile, 'w+');

            if ($cacheStream === false) {
                throw new IOError(sprintf('Could not open file for write: %s', $cacheFile));
            }

            try {
                if (stream_copy_to_stream($stream, $cacheStream) === false) {
                    $message = 'Could not copy source stream to file';
                    $lastError = error_get_last();

                    if ($lastError !== null) {
                        $message .= ': ' . $lastError['message'];
                    }

                    throw new IOError($message);
                }

                if ($checksum !== null) {
                    if (! $this->checkHash($cacheFile, $checksum)) {
                        throw new IOError(sprintf(
                            'Checksum failed. Could not load vocab from URI: %s',
                            $uri,
                        ));
                    }
                }
            } finally {
                fclose($cacheStream);
            }
        } finally {
            fclose($stream);
        }

        return $cacheFile;
    }

    /** @param string|resource $resource */
    private function checkHash($resource, string|null $expectedHash): bool
    {
        if ($expectedHash === null) {
            return true;
        }

        $ctx = hash_init('sha256');

        if (is_resource($resource)) {
            hash_update_stream($ctx, $resource);
        } else {
            hash_update_file($ctx, $resource);
        }

        $hash = hash_final($ctx);

        return hash_equals($hash, $expectedHash);
    }
}
