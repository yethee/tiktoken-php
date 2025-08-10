<?php

declare(strict_types=1);

namespace Yethee\Tiktoken;

use FFI;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Symfony\Contracts\Service\ResetInterface;
use Yethee\Tiktoken\Encoder\LibEncoder;
use Yethee\Tiktoken\Encoder\NativeEncoder;
use Yethee\Tiktoken\Vocab\Loader\DefaultVocabLoader;
use Yethee\Tiktoken\Vocab\Vocab;
use Yethee\Tiktoken\Vocab\VocabLoader;

use function class_exists;
use function getenv;
use function sprintf;
use function str_starts_with;
use function sys_get_temp_dir;

use const DIRECTORY_SEPARATOR;

final class EncoderProvider implements ResetInterface
{
    public const ENCODINGS = [
        'r50k_base' => [
            'vocab' => 'https://openaipublic.blob.core.windows.net/encodings/r50k_base.tiktoken',
            'hash' => '306cd27f03c1a714eca7108e03d66b7dc042abe8c258b44c199a7ed9838dd930',
            'pat' => '\'s|\'t|\'re|\'ve|\'m|\'ll|\'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+',
        ],
        'p50k_base' => [
            'vocab' => 'https://openaipublic.blob.core.windows.net/encodings/p50k_base.tiktoken',
            'hash' => '94b5ca7dff4d00767bc256fdd1b27e5b17361d7b8a5f968547f9f23eb70d2069',
            'pat' => '\'s|\'t|\'re|\'ve|\'m|\'ll|\'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+',
        ],
        'p50k_edit' => [
            'vocab' => 'https://openaipublic.blob.core.windows.net/encodings/p50k_base.tiktoken',
            'hash' => '94b5ca7dff4d00767bc256fdd1b27e5b17361d7b8a5f968547f9f23eb70d2069',
            'pat' => '\'s|\'t|\'re|\'ve|\'m|\'ll|\'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+',
        ],
        'cl100k_base' => [
            'vocab' => 'https://openaipublic.blob.core.windows.net/encodings/cl100k_base.tiktoken',
            'hash' => '223921b76ee99bde995b7ff738513eef100fb51d18c93597a113bcffe865b2a7',
            'pat' => '(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)|[^\r\n\p{L}\p{N}]?\p{L}+|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n]*|\s*[\r\n]+|\s+(?!\S)|\s+',
        ],
        'o200k_base' => [
            'vocab' => 'https://openaipublic.blob.core.windows.net/encodings/o200k_base.tiktoken',
            'hash' => '446a9538cb6c348e3516120d7c08b09f57c36495e2acfffe59a5bf8b0cfb1a2d',
            'pat' => '[^\r\n\p{L}\p{N}]?[\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{M}]*[\p{Ll}\p{Lm}\p{Lo}\p{M}]+(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)?|[^\r\n\p{L}\p{N}]?[\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{M}]+[\p{Ll}\p{Lm}\p{Lo}\p{M}]*(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)?|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n\/]*|\s*[\r\n]+|\s+(?!\S)|\s+',
        ],
    ];
    private const MODEL_PREFIX_TO_ENCODING = [
        'o1-' => 'o200k_base',
        'o3-' => 'o200k_base',
        'o4-mini-' => 'o200k_base',
        'chatgpt-4o-' => 'o200k_base',
        'gpt-5-' => 'o200k_base',
        'gpt-4-' => 'cl100k_base',
        'gpt-4.1-' => 'o200k_base',
        'gpt-4.5-' => 'o200k_base',
        'gpt-4o-' => 'o200k_base',
        'gpt-3.5-turbo-' => 'cl100k_base',
        'gpt-oss-' => 'o200k_base',
    ];
    private const MODEL_TO_ENCODING = [
        'o1' => 'o200k_base',
        'o3' => 'o200k_base',
        'o4-mini' => 'o200k_base',
        'gpt-4' => 'cl100k_base',
        'gpt-4.1' => 'o200k_base',
        'gpt-4o' => 'o200k_base',
        'gpt-3.5-turbo' => 'cl100k_base',
        'gpt-3.5' => 'cl100k_base',
        'davinci-002' => 'cl100k_base',
        'babbage-002' => 'cl100k_base',
        'text-embedding-ada-002' => 'cl100k_base',
        'text-embedding-3-small' => 'cl100k_base',
        'text-embedding-3-large' => 'cl100k_base',
        'text-davinci-003' => 'p50k_base',
        'text-davinci-002' => 'p50k_base',
        'text-davinci-001' => 'r50k_base',
        'text-curie-001' => 'r50k_base',
        'text-babbage-001' => 'r50k_base',
        'text-ada-001' => 'r50k_base',
        'davinci' => 'r50k_base',
        'curie' => 'r50k_base',
        'babbage' => 'r50k_base',
        'ada' => 'r50k_base',
        'code-davinci-002' => 'p50k_base',
        'code-davinci-001' => 'p50k_base',
        'code-cushman-002' => 'p50k_base',
        'code-cushman-001' => 'p50k_base',
        'davinci-codex' => 'p50k_base',
        'cushman-codex' => 'p50k_base',
        'text-davinci-edit-001' => 'p50k_edit',
        'code-davinci-edit-001' => 'p50k_edit',
        'text-similarity-davinci-001' => 'r50k_base',
        'text-similarity-curie-001' => 'r50k_base',
        'text-similarity-babbage-001' => 'r50k_base',
        'text-similarity-ada-001' => 'r50k_base',
        'text-search-davinci-doc-001' => 'r50k_base',
        'text-search-curie-doc-001' => 'r50k_base',
        'text-search-babbage-doc-001' => 'r50k_base',
        'text-search-ada-doc-001' => 'r50k_base',
        'code-search-babbage-code-001' => 'r50k_base',
        'code-search-ada-code-001' => 'r50k_base',
    ];

    private VocabLoader|null $vocabLoader = null;

    /** @var non-empty-string */
    private string $vocabCacheDir;

    /** @var array<non-empty-string, Encoder> */
    private array $encoders = [];

    /** @var array<string, Vocab> */
    private array $vocabs = [];

    public function __construct(private bool $useLib = false)
    {
        if ($useLib && ! class_exists(FFI::class)) {
            throw new RuntimeException('Required FFI extension is not loaded');
        }

        $cacheDir = getenv('TIKTOKEN_CACHE_DIR');

        if ($cacheDir === false || $cacheDir === '') {
            $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tiktoken';
        }

        $this->vocabCacheDir = $cacheDir;
    }

    /** @param non-empty-string $model */
    public function getForModel(string $model): Encoder
    {
        if (isset(self::MODEL_TO_ENCODING[$model])) {
            return $this->get(self::MODEL_TO_ENCODING[$model]);
        }

        foreach (self::MODEL_PREFIX_TO_ENCODING as $prefix => $modelEncoding) {
            if (str_starts_with($model, $prefix)) {
                return $this->get($modelEncoding);
            }
        }

        throw new InvalidArgumentException(sprintf('Unknown model name: %s', $model));
    }

    /** @param non-empty-string $encodingName */
    public function get(string $encodingName): Encoder
    {
        if (! isset(self::ENCODINGS[$encodingName])) {
            throw new InvalidArgumentException(sprintf('Unknown encoding: %s', $encodingName));
        }

        if (! isset($this->encoders[$encodingName])) {
            $options = self::ENCODINGS[$encodingName];

            $encoder = $this->useLib
                ? new LibEncoder(
                    $encodingName,
                    $this->getVocabLoader()->loadFile($options['vocab'], $options['hash'] ?? null),
                    $options['pat'],
                )
                : new NativeEncoder($encodingName, $this->getVocab($encodingName), sprintf('/%s/u', $options['pat']));

            return $this->encoders[$encodingName] = $encoder;
        }

        return $this->encoders[$encodingName];
    }

    /** @param non-empty-string $cacheDir */
    public function setVocabCache(string $cacheDir): void
    {
        $this->vocabCacheDir = $cacheDir;
        $this->vocabLoader = null;
    }

    /** @psalm-api */
    public function setVocabLoader(VocabLoader $loader): void
    {
        $this->vocabLoader = $loader;
    }

    #[Override]
    public function reset(): void
    {
        $this->encoders = [];
        $this->vocabs = [];
    }

    private function getVocabLoader(): VocabLoader
    {
        if ($this->vocabLoader === null) {
            $this->vocabLoader = new DefaultVocabLoader($this->vocabCacheDir);
        }

        return $this->vocabLoader;
    }

    private function getVocab(string $encodingName): Vocab
    {
        if (isset($this->vocabs[$encodingName])) {
            return $this->vocabs[$encodingName];
        }

        return $this->vocabs[$encodingName] = $this->getVocabLoader()->load(
            self::ENCODINGS[$encodingName]['vocab'],
            self::ENCODINGS[$encodingName]['hash'] ?? null,
        );
    }
}
