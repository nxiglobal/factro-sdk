<?php

declare(strict_types=1);

namespace Nxi\Factro\Mapping;

/**
 * Drops null entries so that PUT bodies stay partial objects and POST bodies omit unset fields.
 *
 * @internal
 */
final class Payload
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    public static function withoutNulls(array $fields): array
    {
        return array_filter($fields, static fn (mixed $value): bool => null !== $value);
    }
}
