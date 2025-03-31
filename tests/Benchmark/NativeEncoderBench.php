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
#[Bench\Groups(['native'])]
final class NativeEncoderBench extends EncoderBench
{
    #[Override]
    protected function getEncoder(string $encoding): Encoder
    {
        $provider = new EncoderProvider();
        $provider->setVocabCache(dirname(__DIR__, 2) . '/.cache/vocab');

        $encoder = $provider->get($encoding);

        if (! $encoder instanceof Encoder\NativeEncoder) {
            throw new RuntimeException(sprintf(
                'Was expected an instance of %s but got %s.',
                Encoder\NativeEncoder::class,
                $encoder::class,
            ));
        }

        return $encoder;
    }
}
