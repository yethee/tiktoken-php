<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Vocab;

use PHPUnit\Framework\TestCase;
use Yethee\Tiktoken\Vocab\Vocab;

use function chr;

final class VocabTest extends TestCase
{
    public function testLoadFromFile(): void
    {
        $vocab = Vocab::fromFile(__DIR__ . '/Fixtures/test.tiktoken');

        self::assertCount(47, $vocab);
        self::assertSame(285, $vocab->getRank('is'));
        self::assertSame('is', $vocab->getToken(285));
        self::assertSame(18, $vocab->getRank(chr(51)));
        self::assertSame('3', $vocab->getToken(18));
    }
}
