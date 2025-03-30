<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Encoder;

use FFI;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\Encoder\LibEncoder;
use Yethee\Tiktoken\EncoderProvider;

use function class_exists;
use function dirname;

final class LibEncoderTest extends EncoderTestCase
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    #[DataProvider('provideDataForChunkBasedTokenization')]
    public function testEncodeInChunks(string $encoding, string $text, int $maxTokensPerChunk, array $expected): void
    {
        $this->markTestIncomplete('Method not implemented yet');
    }

    public function testPreload(): void
    {
        if (! class_exists(FFI::class)) {
            $this->markTestSkipped('Required FFI extension');
        }

        LibEncoder::preload(dirname(__DIR__, 2) . '/target/release');

        $encoder = $this->getEncoder('cl100k_base');

        $tokens = $encoder->encode('Hello world');

        self::assertEquals([9906, 1917], $tokens);
    }

    #[Override]
    protected function getEncoder(string $encoding): Encoder
    {
        if (! class_exists(FFI::class)) {
            $this->markTestSkipped('Required FFI extension');
        }

        LibEncoder::init(dirname(__DIR__, 2) . '/target/release');

        return new LibEncoder(
            $encoding,
            dirname(__DIR__) . '/Fixtures/' . $encoding . '.tiktoken',
            EncoderProvider::ENCODINGS[$encoding]['pat'],
        );
    }
}
