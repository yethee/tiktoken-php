<?php

declare(strict_types=1);

namespace Yethee\Tiktoken;

interface Encoder
{
    public function getEncoding(): string;

    /** @return list<int> */
    public function encode(string $text): array;

    /**
     * Encodes a given text into chunks of Byte-Pair Encoded (BPE) tokens, with each chunk containing a specified
     * maximum number of tokens.
     *
     * @param string       $text              The input text to be encoded.
     * @param positive-int $maxTokensPerChunk The maximum number of tokens allowed per chunk.
     *
     * @return list<list<int>> An array of arrays containing BPE token chunks.
     */
    public function encodeInChunks(string $text, int $maxTokensPerChunk): array;

    /** @param array<int> $tokens */
    public function decode(array $tokens): string;
}
