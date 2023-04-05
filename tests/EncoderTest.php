<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests;

use PHPUnit\Framework\TestCase;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\Vocab\Vocab;

final class EncoderTest extends TestCase
{
    public function testEncode(): void
    {
        $vocab = Vocab::fromFile(__DIR__ . '/Fixtures/cl100k_base.tiktoken');
        $encoder = new Encoder(
            'cl100k_base',
            $vocab,
            '/(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)|[^\r\n\p{L}\p{N}]?\p{L}+|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n]*|\s*[\r\n]+|\s+(?!\S)|\s+/u',
        );

        self::assertSame(
            [15339, 1917],
            $encoder->encode('hello world'),
        );

        self::assertSame(
            [8164, 2233, 28089, 8341, 11562, 78746],
            $encoder->encode('привет мир'),
        );

        self::assertSame(
            [9468, 234, 114],
            $encoder->encode('🌶'),
        );
    }

    public function testDecode(): void
    {
        $vocab = Vocab::fromFile(__DIR__ . '/Fixtures/cl100k_base.tiktoken');
        $encoder = new Encoder(
            'cl100k_base',
            $vocab,
            '/(?i:\'s|\'t|\'re|\'ve|\'m|\'ll|\'d)|[^\r\n\p{L}\p{N}]?\p{L}+|\p{N}{1,3}| ?[^\s\p{L}\p{N}]+[\r\n]*|\s*[\r\n]+|\s+(?!\S)|\s+/u',
        );

        self::assertSame('hello world', $encoder->decode([15339, 1917]));
        self::assertSame('привет мир', $encoder->decode([8164, 2233, 28089, 8341, 11562, 78746]));
        self::assertSame('🌶', $encoder->decode([9468, 234, 114]));
    }
}
