<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Vocab\Loader;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Yethee\Tiktoken\Vocab\Loader\DefaultVocabLoader;

use function dirname;
use function file_get_contents;
use function hash;

final class DefaultVocabLoaderTest extends TestCase
{
    private vfsStreamDirectory $cacheDir;

    public function testLoadFromCache(): void
    {
        $loader = new DefaultVocabLoader($this->cacheDir->url());
        $vocabUrl = 'http://localhost/cl100k_base.tiktoken';
        $expectedHash = '223921b76ee99bde995b7ff738513eef100fb51d18c93597a113bcffe865b2a7';

        vfsStream::newFile(hash('sha1', $vocabUrl))
            ->withContent(file_get_contents(dirname(__DIR__, 2) . '/Fixtures/cl100k_base.tiktoken'))
            ->at($this->cacheDir);

        $vocab = $loader->load($vocabUrl, $expectedHash);

        self::assertSame(100256, $vocab->count());
    }

    public function testInvalidateCacheWhenChecksumMismatch(): void
    {
        $loader = new DefaultVocabLoader($this->cacheDir->url());
        $vocabUrl = dirname(__DIR__, 2) . '/Fixtures/p50k_base.tiktoken';
        $expectedHash = '94b5ca7dff4d00767bc256fdd1b27e5b17361d7b8a5f968547f9f23eb70d2069';

        $cacheFile = vfsStream::newFile(hash('sha1', $vocabUrl))
            ->withContent('outdated content')
            ->at($this->cacheDir);

        $vocab = $loader->load($vocabUrl, $expectedHash);

        self::assertSame(50280, $vocab->count());

        self::assertFileExists($cacheFile->url());
        self::assertFileEquals($vocabUrl, $cacheFile->url());
    }

    protected function setUp(): void
    {
        $this->cacheDir = vfsStream::setup('cache');
    }
}
