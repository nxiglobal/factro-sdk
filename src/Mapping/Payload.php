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

    /**
     * Like withoutNulls(), but sends every field named in $clear as an explicit null so that factro empties it.
     *
     * @param array<string, mixed> $fields
     * @param list<string>         $clear
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when a cleared field is unknown or also set
     */
    public static function withoutNullsExcept(array $fields, array $clear): array
    {
        foreach ($clear as $field) {
            if (!\array_key_exists($field, $fields)) {
                throw new \InvalidArgumentException(\sprintf('Cannot clear unknown field "%s".', $field));
            }

            if (null !== $fields[$field]) {
                throw new \InvalidArgumentException(\sprintf('Field "%s" cannot be set and cleared at once.', $field));
            }
        }

        return [...self::withoutNulls($fields), ...array_fill_keys($clear, null)];
    }
}
