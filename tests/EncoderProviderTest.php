<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests;

use PHPUnit\Framework\TestCase;
use Yethee\Tiktoken\EncoderProvider;

use function dirname;

final class EncoderProviderTest extends TestCase
{
    public function testGetEncoderForModel(): void
    {
        $testcases = [['text-davinci-003', 'p50k_base'], ['text-davinci-edit-001', 'p50k_edit'], ['gpt-3.5-turbo-0301', 'cl100k_base']];

        foreach ($testcases as $testcase) {
            $modelName = $testcase[0];
            $encoding = $testcase[1];
            $provider = new EncoderProvider();
            $provider->setVocabCache(dirname(__DIR__) . '/.cache/vocab');

            $encoder = $provider->getForModel($modelName);

            self::assertSame($encoding, $encoder->name);
        }
    }

    public function testEncode(): void
    {
        $provider = new EncoderProvider();
        $provider->setVocabCache(dirname(__DIR__) . '/.cache/vocab');

        $encoder = $provider->get('p50k_base');
        self::assertSame([31373, 995], $encoder->encode('hello world'));

        $encoder = $provider->get('cl100k_base');
        self::assertSame([15339, 1917], $encoder->encode('hello world'));
    }
}
