<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Vocab\Loader;

use RuntimeException;
use Yethee\Tiktoken\Vocab\Vocab;
use Yethee\Tiktoken\Vocab\VocabLoader;

use function assert;
use function fclose;
use function file_exists;
use function fopen;
use function is_dir;
use function is_writable;
use function mkdir;
use function preg_match;
use function sha1;
use function sprintf;
use function stream_copy_to_stream;

use const DIRECTORY_SEPARATOR;

final class DefaultVocabLoader implements VocabLoader
{
    public $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir;
    }

    public function load(string $uri): Vocab
    {
        if ($this->cacheDir !== null && preg_match('@^https?://@i', $uri)) {
            $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR . sha1($uri);
        } else {
            $cacheFile = null;
        }

        if ($cacheFile !== null) {
            if (file_exists($cacheFile)) {
                return Vocab::fromFile($cacheFile);
            }

            assert($this->cacheDir !== null);

            if (! is_dir($this->cacheDir) && ! @mkdir($this->cacheDir, 0750, true)) {
                throw new RuntimeException(sprintf(
                    'Directory does not exist and cannot be created: %s',
                    $this->cacheDir,
                ));
            }

            if (! is_writable($this->cacheDir)) {
                throw new RuntimeException(sprintf('Directory is not writable: %s', $this->cacheDir));
            }
        }

        $stream = fopen($uri, 'r');

        if ($stream === false) {
            throw new RuntimeException(sprintf('Could not open stream for URI: %s', $uri));
        }

        try {
            if ($cacheFile !== null) {
                $cacheStream = fopen($cacheFile, 'w+');

                if ($cacheStream === false) {
                    throw new RuntimeException(sprintf('Could not open file for write: %s', $cacheFile));
                }

                try {
                    stream_copy_to_stream($stream, $cacheStream);

                    return Vocab::fromStream($cacheStream);
                } finally {
                    fclose($cacheStream);
                }
            }

            return Vocab::fromStream($stream);
        } finally {
            fclose($stream);
        }

        return Vocab::fromFile($uri);
    }
}
