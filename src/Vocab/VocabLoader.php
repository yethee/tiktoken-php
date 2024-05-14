<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Vocab;

interface VocabLoader
{
    /** @param non-empty-string $uri */
    public function load(string $uri, string|null $checksum = null): Vocab;
}
