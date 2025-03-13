<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Benchmark;

use PhpBench\Attributes as Bench;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\EncoderProvider;

use function dirname;
use function file_get_contents;

/** @psalm-api */
#[Bench\Iterations(5)]
#[Bench\Revs(100)]
#[Bench\Warmup(3)]
#[Bench\BeforeMethods('initialize')]
#[Bench\ParamProviders([
    'provideEncodings',
    'provideFixtures',
])]
final class EncoderBench
{
    private const TEXTS = ['baconipsum', 'cyrillic', 'latin'];
    private const ENCODINGS = ['p50k_base', 'cl100k_base', 'o200k_base'];

    private Encoder $encoder;
    private string $text;

    /** @var list<int> */
    private array $tokens;

    /** @param array{fixture: non-empty-string, encoding: non-empty-string} $params */
    public function initialize(array $params): void
    {
        $provider = new EncoderProvider();
        $provider->setVocabCache(dirname(__DIR__, 2) . '/.cache/vocab');

        $this->encoder = $provider->get($params['encoding']);

        $this->text = file_get_contents(__DIR__ . '/fixtures/' . $params['fixture'] . '.txt');
        $this->tokens = $this->encoder->encode($this->text);
    }

    #[Bench\Subject]
    public function encode(): void
    {
        $this->encoder->encode($this->text);
    }

    #[Bench\Subject]
    public function decode(): void
    {
        $this->encoder->decode($this->tokens);
    }

    /** @return iterable<array{encoding: non-empty-string}> */
    public static function provideEncodings(): iterable
    {
        foreach (self::ENCODINGS as $encoding) {
            yield $encoding => ['encoding' => $encoding];
        }
    }

    /** @return iterable<array{fixture: non-empty-string}> */
    public static function provideFixtures(): iterable
    {
        foreach (self::TEXTS as $text) {
            yield $text => ['fixture' => $text];
        }
    }
}
