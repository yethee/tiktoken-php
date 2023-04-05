<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Vocab;

use PHPUnit\Framework\TestCase;
use Yethee\Tiktoken\Util\EncodeUtil;
use Yethee\Tiktoken\Vocab\Vocab;

final class VocabTest extends TestCase
{
    public function testLoadFromFile(): void
    {
        $vocab = Vocab::fromFile(__DIR__ . '/Fixtures/test.tiktoken');

        self::assertCount(41, $vocab);
        self::assertSame(285, $vocab->getRank(EncodeUtil::toBytes('is')));
        self::assertSame('is', $vocab->getToken(285));
    }
}
