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
use function hash_equals;
use function hash_final;
use function hash_init;
use function hash_update_file;
use function hash_update_stream;
use function is_dir;
use function is_resource;
use function is_writable;
use function mkdir;
use function rewind;
use function sha1;
use function sprintf;
use function stream_copy_to_stream;
use function stream_get_meta_data;

use const DIRECTORY_SEPARATOR;

final class DefaultVocabLoader implements VocabLoader
{
    public function __construct(private string|null $cacheDir = null)
    {
    }

    public function load(string $uri, string|null $checksum = null): Vocab
    {
        $cacheFile = $this->cacheDir !== null ? $this->cacheDir . DIRECTORY_SEPARATOR . sha1($uri) : null;

        if ($cacheFile !== null) {
            if (file_exists($cacheFile) && $this->checkHash($cacheFile, $checksum)) {
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
            if ($checksum !== null && $this->isRewindable($stream)) {
                if (! $this->checkHash($stream, $checksum)) {
                    throw new RuntimeException(sprintf(
                        'Checksum failed. Could not load vocab from URI: %s',
                        $uri,
                    ));
                }

                rewind($stream);
            }

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
    }

    /** @param string|resource $resource */
    private function checkHash($resource, string|null $expectedHash): bool
    {
        if ($expectedHash === null) {
            return true;
        }

        $ctx = hash_init('sha256');

        if (is_resource($resource)) {
            hash_update_stream($ctx, $resource);
        } else {
            hash_update_file($ctx, $resource);
        }

        $hash = hash_final($ctx);

        return hash_equals($hash, $expectedHash);
    }

    /** @param resource $stream */
    private function isRewindable($stream): bool
    {
        $meta = stream_get_meta_data($stream);

        return $meta['seekable'];
    }
}
