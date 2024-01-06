<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\EncoderProvider;
use Yethee\Tiktoken\Vocab\Vocab;

final class EncoderTest extends TestCase
{
    /** @param int[] $encodings */
    #[DataProvider('provideDataForFlatTokenization')]
    public function testEncode(string $text, array $encodings): void
    {
        $vocab = Vocab::fromFile(__DIR__ . '/Fixtures/cl100k_base.tiktoken');
        $encoder = new Encoder(
            'cl100k_base',
            $vocab,
            '/(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)|[^\r\n\p{L}\p{N}]?\p{L}+|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n]*|\s*[\r\n]+|\s+(?!\S)|\s+/u',
        );

        self::assertSame($encodings, $encoder->encode($text));
    }

    /** @param int[] $encodings */
    #[DataProvider('provideDataForFlatTokenization')]
    public function testDecode(string $text, array $encodings): void
    {
        $vocab = Vocab::fromFile(__DIR__ . '/Fixtures/cl100k_base.tiktoken');
        $encoder = new Encoder(
            'cl100k_base',
            $vocab,
            '/(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)|[^\r\n\p{L}\p{N}]?\p{L}+|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n]*|\s*[\r\n]+|\s+(?!\S)|\s+/u',
        );

        self::assertSame($text, $encoder->decode($encodings));
    }

    /** @param int[][] $expected */
    #[DataProvider('provideDataForChunkBasedTokenization')]
    public function testEncodeInChunks(Encoder $encoder, string $text, int $maxTokensPerChunk, array $expected): void
    {
        self::assertSame($expected, $encoder->encodeInChunks($text, $maxTokensPerChunk));
    }

    /**
     * @return Generator<array<mixed>>
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function provideDataForFlatTokenization(): Generator
    {
        yield 'hello world' => ['hello world', [15339, 1917]];

        yield 'привет мир' => ['привет мир', [8164, 2233, 28089, 8341, 11562, 78746]];

        yield 'emoji' => ['🌶', [9468, 234, 114]];

        yield 'new line character' => [".\n", [627]];
    }

    /**
     * @return Generator<array<mixed>>
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function provideDataForChunkBasedTokenization(): Generator
    {
        yield 'p50k_base' => [
            self::getEncoder('p50k_base'),
            '1 2 hello，world 3 4',
            3,
            [
                [16, 362, 23748],
                [171, 120, 234],
                [6894, 513, 604],
            ],
        ];

        yield 'cl100k_base' => [
            self::getEncoder('cl100k_base'),
            '1 2 hello，world 3 4',
            5,
            [
                [16, 220, 17, 24748],
                [3922, 14957, 220, 18, 220],
                [19],
            ],
        ];
    }

    /** @param non-empty-string $encoding */
    private static function getEncoder(string $encoding): Encoder
    {
        return new Encoder(
            $encoding,
            Vocab::fromFile(__DIR__ . '/Fixtures/' . $encoding . '.tiktoken'),
            EncoderProvider::ENCODINGS[$encoding]['pat'],
        );
    }
}
