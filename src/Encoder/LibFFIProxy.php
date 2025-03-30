<?php

declare(strict_types=1);

namespace Yethee\Tiktoken\Encoder;

use FFI;
use FFI\CData;
use FFI\CType;
use RuntimeException;

use function sprintf;

/**
 * @method CData|null init(string $pattern, string $bpeFile)
 * @method void destroy(CData $ptr)
 * @method void free_tokens(CData $tokens)
 * @method CData|null encode(CData $ptr, string $text)
 * @method string|null decode(CData $ptr, CData $tokens, int $len)
 * @method string|null last_error_message()
 */
final class LibFFIProxy
{
    public function __construct(private FFI $ffi)
    {
    }

    /** @param array<mixed> $arguments */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->ffi->$name(...$arguments);
    }

    public function new(CType|string $type, bool $owned = true, bool $persistent = false): CData
    {
        $data = $this->ffi->new($type, $owned, $persistent);

        if ($data === null) {
            throw new RuntimeException(sprintf(
                'Could not create a new struct: %s',
                $type instanceof CType ? $type->getName() : $type,
            ));
        }

        return $data;
    }
}
