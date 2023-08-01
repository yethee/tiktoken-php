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
    /** @var array<string, int> */
    private $tokenToRankMap;

    /** @var array<int, non-empty-string> */
    private $rankToTokenMap;

    /** @param array<non-empty-string, int> $tokenRankMap */
    private function __construct(array $tokenRankMap)
    {
        $this->tokenToRankMap = $tokenRankMap;
        /** @psalm-suppress PropertyTypeCoercion */
        $this->rankToTokenMap = [];
        foreach (array_flip($tokenRankMap) as $rank => $token) {
            $this->rankToTokenMap[$rank] = $token;
        }

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
            $token = base64_decode($encodedToken);

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

    /** @psalm-param NonEmptyByteVector $bytes */
    public function tryGetRank(array $bytes): ?int
    {
        return $this->tokenToRankMap[EncodeUtil::fromBytes($bytes)] ?? null;
    }

    /**
     * @psalm-param NonEmptyByteVector $bytes
     *
     * @throws OutOfBoundsException
     */
    public function getRank(array $bytes): int
    {
        $rank = $this->tokenToRankMap[EncodeUtil::fromBytes($bytes)] ?? null;
        if ($rank === null) {
            throw new OutOfBoundsException(sprintf(
                'No rank for bytes vector: [%s]',
                implode(', ', $bytes),
            ));
        }

        return $rank;
    }

    /** @throws OutOfBoundsException */
    public function getToken(int $rank): string
    {
        $tokenMap = $this->rankToTokenMap[$rank] ?? null;
        if ($tokenMap === null) {
            throw new OutOfBoundsException(sprintf('No token for rank: %d', $rank));
        }

        return strval($tokenMap);
    }

    /** @psalm-api */
    public function count(): int
    {
        return count($this->tokenToRankMap);
    }
}
