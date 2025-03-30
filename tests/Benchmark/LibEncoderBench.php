<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Tests\Benchmark;

use Override;
use PhpBench\Attributes as Bench;
use RuntimeException;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\EncoderProvider;

use function dirname;
use function sprintf;

/** @psalm-api */
#[Bench\Groups(['lib'])]
final class LibEncoderBench extends EncoderBench
{
    #[Override]
    protected function getEncoder(string $encoding): Encoder
    {
        Encoder\LibEncoder::init(dirname(__DIR__, 2) . '/target/release');

        $provider = new EncoderProvider(true);
        $provider->setVocabCache(dirname(__DIR__, 2) . '/.cache/vocab');

        $encoder = $provider->get($encoding);

        if (! $encoder instanceof Encoder\LibEncoder) {
            throw new RuntimeException(sprintf(
                'Was expected an instance of %s but got %s.',
                Encoder\LibEncoder::class,
                $encoder::class,
            ));
        }

        return $encoder;
    }
}
