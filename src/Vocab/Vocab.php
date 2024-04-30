<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Vocab;

use Countable;
use InvalidArgumentException;
use OutOfBoundsException;
use RuntimeException;
use Yethee\Tiktoken\Exception\ParseError;
use Yethee\Tiktoken\Util\EncodeUtil;

use function array_flip;
use function array_map;
use function assert;
use function base64_decode;
use function count;
use function explode;
use function fclose;
use function fgets;
use function file_exists;
use function fopen;
use function implode;
use function rewind;
use function sprintf;
use function stream_get_meta_data;
use function strval;

/** @psalm-import-type NonEmptyByteVector from EncodeUtil */
final class Vocab implements Countable
{
    /** @var array<non-empty-string, int> */
    private array $tokenToRankMap;

    /** @var array<int, non-empty-string> */
    private array $rankToTokenMap;

    /** @param array<non-empty-string, int> $tokenRankMap */
    private function __construct(array $tokenRankMap)
    {
        $this->tokenToRankMap = $tokenRankMap;
        /** @psalm-suppress PropertyTypeCoercion */
        $this->rankToTokenMap = array_map(strval(...), array_flip($tokenRankMap));

        if (count($this->tokenToRankMap) !== count($this->rankToTokenMap)) {
            throw new InvalidArgumentException('The map of tokens and ranks has duplicates of rank');
        }
    }

    /** @param non-empty-string $bpeFile */
    public static function fromFile(string $bpeFile): self
    {
        if (! file_exists($bpeFile)) {
            throw new RuntimeException(sprintf('File "%s" does not exist', $bpeFile));
        }

        $stream = fopen($bpeFile, 'rb');

        if ($stream === false) {
            throw new RuntimeException(sprintf('Could not open file: %s', $bpeFile));
        }

        try {
            return self::fromStream($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param resource $stream
     *
     * @return static
     */
    public static function fromStream($stream): self
    {
        $meta = stream_get_meta_data($stream);

        if ($meta['seekable']) {
            rewind($stream);
        }

        $line = fgets($stream);
        $lineNo = 1;
        $map = [];

        while ($line !== false) {
            [$encodedToken, $rank] = explode(' ', $line);
            $token = base64_decode($encodedToken, true);

            if ($token === false) {
                throw new ParseError(sprintf('Could not decode token "%s" at line %d', $encodedToken, $lineNo));
            }

            assert($token !== '');

            $map[$token] = (int) $rank;

            $line = fgets($stream);
            $lineNo++;
        }

        return new self($map);
    }

    public function tryGetRank(string $binary): int|null
    {
        if ($binary === '') {
            throw new InvalidArgumentException('Argument $binary cannot be an empty string');
        }

        return $this->tokenToRankMap[$binary] ?? null;
    }

    /** @throws OutOfBoundsException */
    public function getRank(string $binary): int
    {
        if ($binary === '') {
            throw new InvalidArgumentException('Argument $binary cannot be an empty string');
        }

        return $this->tokenToRankMap[$binary] ?? throw new OutOfBoundsException(sprintf(
            'No rank for bytes vector: [%s]',
            implode(', ', EncodeUtil::toBytes($binary)),
        ));
    }

    /**
     * @return non-empty-string
     *
     * @throws OutOfBoundsException
     */
    public function getToken(int $rank): string
    {
        return $this->rankToTokenMap[$rank] ?? throw new OutOfBoundsException(sprintf('No token for rank: %d', $rank));
    }

    /** @psalm-api */
    public function count(): int
    {
        return count($this->tokenToRankMap);
    }
}
