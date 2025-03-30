<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Encoder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yethee\Tiktoken\Encoder;

abstract class EncoderTestCase extends TestCase
{
    /**
     * @param non-empty-string $encoding
     * @param list<int>        $tokens
     */
    #[DataProvider('provideDataForFlatTokenization')]
    public function testEncode(string $text, string $encoding, array $tokens): void
    {
        $encoder = $this->getEncoder($encoding);

        self::assertSame($tokens, $encoder->encode($text));
    }

    /**
     * @param non-empty-string $encoding
     * @param list<int>        $tokens
     */
    #[DataProvider('provideDataForFlatTokenization')]
    public function testDecode(string $text, string $encoding, array $tokens): void
    {
        $encoder = $this->getEncoder($encoding);

        self::assertSame($text, $encoder->decode($tokens));
    }

    /**
     * @param non-empty-string $encoding
     * @param positive-int     $maxTokensPerChunk
     * @param list<list<int>>  $expected
     */
    #[DataProvider('provideDataForChunkBasedTokenization')]
    public function testEncodeInChunks(string $encoding, string $text, int $maxTokensPerChunk, array $expected): void
    {
        $encoder = $this->getEncoder($encoding);

        self::assertSame($expected, $encoder->encodeInChunks($text, $maxTokensPerChunk));
    }

    /**
     * @return iterable<array{string, string, list<int>}>
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function provideDataForFlatTokenization(): iterable
    {
        yield '[cl100k_base] hello world' => ['hello world', 'cl100k_base', [15339, 1917]];

        yield '[cl100k_base] привет мир' => ['привет мир', 'cl100k_base', [8164, 2233, 28089, 8341, 11562, 78746]];

        yield '[cl100k_base] emoji' => ['🌶', 'cl100k_base', [9468, 234, 114]];

        yield '[cl100k_base] new line character' => [".\n", 'cl100k_base', [627]];

        yield '[o200k_base] hello world' => ['hello world', 'o200k_base', [24912, 2375]];

        yield '[o200k_base] привет мир' => ['привет мир', 'o200k_base', [9501, 131903, 37934]];

        yield '[o200k_base] emoji' => ['🌶', 'o200k_base', [64364, 114]];

        yield '[o200k_base] new line character' => [".\n", 'o200k_base', [558]];
    }

    /**
     * @return iterable<array{
     *     non-empty-string,
     *     string,
     *     positive-int,
     *     list<list<int>>
     * }>
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function provideDataForChunkBasedTokenization(): iterable
    {
        yield 'p50k_base' => [
            'p50k_base',
            '1 2 hello，world 3 4',
            3,
            [
                [16, 362, 23748],
                [171, 120, 234],
                [6894, 513, 604],
            ],
        ];

        yield 'cl100k_base' => [
            'cl100k_base',
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
    abstract protected function getEncoder(string $encoding): Encoder;
}
