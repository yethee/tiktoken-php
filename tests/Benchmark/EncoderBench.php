<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Benchmark;

use PhpBench\Attributes as Bench;
use RuntimeException;
use Yethee\Tiktoken\Encoder;

use function file_get_contents;
use function sprintf;

/** @psalm-api */
#[Bench\Iterations(3)]
#[Bench\Revs(5)]
#[Bench\BeforeMethods('initialize')]
#[Bench\ParamProviders([
    'provideEncodings',
    'provideFixtures',
])]
abstract class EncoderBench
{
    private const TEXTS = [
        'baconipsum',
        'cyrillic',
        'latin',
        'without-whitespaces',
    ];
    private const ENCODINGS = ['p50k_base', 'cl100k_base', 'o200k_base'];

    private Encoder $encoder;
    private string $text;

    /** @var list<int> */
    private array $tokens;

    /** @param non-empty-string $encoding */
    abstract protected function getEncoder(string $encoding): Encoder;

    /** @param array{fixture: non-empty-string, encoding: non-empty-string} $params */
    public function initialize(array $params): void
    {
        $encoder = $this->getEncoder($params['encoding']);

        $content = file_get_contents(__DIR__ . '/fixtures/' . $params['fixture'] . '.txt');

        if ($content === false) {
            throw new RuntimeException(sprintf('Fixture "%s" does not exist', $params['fixture']));
        }

        $this->encoder = $encoder;
        $this->text = $content;
        $this->tokens = $encoder->encode($this->text);
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
