<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Vocab\Loader;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Yethee\Tiktoken\Vocab\Loader\DefaultVocabLoader;

use function copy;
use function dirname;
use function file_put_contents;
use function hash;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

final class DefaultVocabLoaderTest extends TestCase
{
    private string $cacheDir;

    public function testLoadFromCache(): void
    {
        $loader = new DefaultVocabLoader($this->cacheDir);

        $vocabUrl = 'http://localhost/cl100k_base.tiktoken';
        $cacheFile = $this->cacheDir . '/' . hash('sha1', $vocabUrl);

        copy(dirname(__DIR__, 2) . '/Fixtures/cl100k_base.tiktoken', $cacheFile);
        self::assertFileEquals(dirname(__DIR__, 2) . '/Fixtures/cl100k_base.tiktoken', $cacheFile);

        $vocab = $loader->load($vocabUrl, '223921b76ee99bde995b7ff738513eef100fb51d18c93597a113bcffe865b2a7');

        self::assertSame(100256, $vocab->count());
    }

    public function testInvalidateCacheWhenChecksumMismatch(): void
    {
        $loader = new DefaultVocabLoader($this->cacheDir);

        $vocabUrl = dirname(__DIR__, 2) . '/Fixtures/p50k_base.tiktoken';
        $cacheFile = $this->cacheDir . '/' . hash('sha1', $vocabUrl);

        file_put_contents($cacheFile, 'outdated content');
        self::assertFileExists($cacheFile);

        $vocab = $loader->load($vocabUrl, '94b5ca7dff4d00767bc256fdd1b27e5b17361d7b8a5f968547f9f23eb70d2069');

        self::assertSame(50280, $vocab->count());

        self::assertFileExists($cacheFile);
        self::assertFileEquals($vocabUrl, $cacheFile);
    }

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/tiktoken-test';

        self::removeDir($this->cacheDir);
    }

    protected function tearDown(): void
    {
        self::removeDir($this->cacheDir);
    }

    private static function removeDir(string $path): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            if ($entry->isFile()) {
                unlink($entry->getPathname());
            } else {
                rmdir($entry->getPathname());
            }
        }
    }
}
