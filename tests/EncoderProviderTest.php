<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yethee\Tiktoken\EncoderProvider;

use function dirname;
use function hash;

final class EncoderProviderTest extends TestCase
{
    /**
     * @param non-empty-string $modelName
     * @param non-empty-string $encoding
     */
    #[DataProvider('getEncoderForModelProvider')]
    public function testGetEncoderForModel(string $modelName, string $encoding): void
    {
        $provider = new EncoderProvider();
        $provider->setVocabCache(dirname(__DIR__) . '/.cache/vocab');

        $encoder = $provider->getForModel($modelName);

        self::assertSame($encoding, $encoder->name);
    }

    public function testEncode(): void
    {
        $provider = new EncoderProvider();
        $provider->setVocabCache(dirname(__DIR__) . '/.cache/vocab');

        $encoder = $provider->get('p50k_base');
        self::assertSame([31373, 995], $encoder->encode('hello world'));

        $encoder = $provider->get('cl100k_base');
        self::assertSame([15339, 1917], $encoder->encode('hello world'));
    }

    public function testUseHashWhenLoadVocab(): void
    {
        $cache = vfsStream::setup('cache');
        $vocabCacheFilename = hash('sha1', EncoderProvider::ENCODINGS['p50k_base']['vocab']);

        $cacheFile = vfsStream::newFile($vocabCacheFilename)
            ->withContent('broken cache')
            ->at($cache);

        $provider = new EncoderProvider();
        /** @psalm-suppress ArgumentTypeCoercion */
        $provider->setVocabCache($cache->url());

        $provider->get('p50k_base');

        self::assertNotEquals('broken cache', $cacheFile->getContent());
    }

    /**
     * @return iterable<array{non-empty-string, non-empty-string}>
     *
     * @psalm-api
     */
    public static function getEncoderForModelProvider(): iterable
    {
        yield 'text-davinci-003' => ['text-davinci-003', 'p50k_base'];
        yield 'text-davinci-edit-001' => ['text-davinci-edit-001', 'p50k_edit'];
        yield 'gpt-3.5-turbo-0301' => ['gpt-3.5-turbo-0301', 'cl100k_base'];
        yield 'gpt-4-32k' => ['gpt-4-32k', 'cl100k_base'];
        yield 'gpt-4o-2024-05-13' => ['gpt-4o-2024-05-13', 'o200k_base'];
    }
}
