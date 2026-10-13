<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

/**
 * A response field is missing or has an unexpected type.
 * actualType is get_debug_type() of the received value, safe to log without the value itself.
 */
final class HydrationException extends \UnexpectedValueException implements FactroException
{
    public readonly string $actualType;

    public function __construct(
        public readonly string $owner,
        public readonly string $field,
        public readonly string $expected,
        mixed $actual,
    ) {
        $this->actualType = get_debug_type($actual);
        parent::__construct(sprintf(
            'Cannot hydrate %s::$%s: expected %s, got %s.',
            $owner,
            $field,
            $expected,
            $this->describe($actual),
        ));
    }

    private function describe(mixed $value): string
    {
        return match (true) {
            null === $value => 'null',
            is_array($value) => 'array',
            is_object($value) => 'object '.$value::class,
            is_string($value) => 'string '.json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            is_int($value) => 'int '.$value,
            is_float($value) => 'float '.json_encode($value),
            is_bool($value) => 'bool '.($value ? 'true' : 'false'),
            default => get_debug_type($value),
        };
    }
}
