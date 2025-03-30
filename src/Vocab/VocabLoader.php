<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Vocab;

use Yethee\Tiktoken\Exception\IOError;

interface VocabLoader
{
    /**
     * @param non-empty-string $uri
     *
     * @throws IOError
     */
    public function load(string $uri, string|null $checksum = null): Vocab;

    /**
     * @param non-empty-string $uri
     *
     * @return non-empty-string
     *
     * @throws IOError
     */
    public function loadFile(string $uri, string|null $checksum = null): string;
}
