<?php

declare(strict_types=1);

namespace Nxi\Factro\Mapping;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Time\CalendarDate;
use Nxi\Factro\Time\FactroDateTime;

/**
 * Typed accessors for decoded JSON rows. Every DTO hydrates exclusively through this class.
 *
 * A missing key is treated like null. Required accessors throw HydrationException on null or a
 * type mismatch; nullable accessors only on a type mismatch. Values are never normalised.
 *
 * @internal
 */
final class Field
{
    private function __construct()
    {
    }

    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key, string $owner): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : throw new HydrationException($owner, $key, 'string', $value);
    }

    /** @param array<string, mixed> $data */
    public static function nullableString(array $data, string $key, string $owner): ?string
    {
        $value = $data[$key] ?? null;

        return null === $value || is_string($value) ? $value : throw new HydrationException($owner, $key, '?string', $value);
    }

    /** @param array<string, mixed> $data */
    public static function bool(array $data, string $key, string $owner): bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : throw new HydrationException($owner, $key, 'bool', $value);
    }

    /** @param array<string, mixed> $data */
    public static function nullableBool(array $data, string $key, string $owner): ?bool
    {
        $value = $data[$key] ?? null;

        return null === $value || is_bool($value) ? $value : throw new HydrationException($owner, $key, '?bool', $value);
    }

    /**
     * Accepts int, or float with an integral value (JSON encoders may emit 3.0 for 3).
     *
     * @param array<string, mixed> $data
     */
    public static function int(array $data, string $key, string $owner): int
    {
        return self::nullableInt($data, $key, $owner) ?? throw new HydrationException($owner, $key, 'int', null);
    }

    /** @param array<string, mixed> $data */
    public static function nullableInt(array $data, string $key, string $owner): ?int
    {
        $value = $data[$key] ?? null;
        if (null === $value || is_int($value)) {
            return $value;
        }
        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        throw new HydrationException($owner, $key, isset($data[$key]) ? 'int' : '?int', $value);
    }

    /**
     * Accepts int or float; factro sends efforts as either.
     *
     * @param array<string, mixed> $data
     */
    public static function float(array $data, string $key, string $owner): float
    {
        return self::nullableFloat($data, $key, $owner) ?? throw new HydrationException($owner, $key, 'float', null);
    }

    /** @param array<string, mixed> $data */
    public static function nullableFloat(array $data, string $key, string $owner): ?float
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        throw new HydrationException($owner, $key, 'float', $value);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $default
     *
     * @return array<string, mixed>
     */
    public static function array(array $data, string $key, string $owner, array $default = []): array
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return $default;
        }
        if (!is_array($value)) {
            throw new HydrationException($owner, $key, 'array', $value);
        }
        $result = [];
        foreach ($value as $k => $v) {
            $result[(string) $k] = $v;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<string>
     */
    public static function stringList(array $data, string $key, string $owner): array
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return [];
        }
        if (!is_array($value)) {
            throw new HydrationException($owner, $key, 'list<string>', $value);
        }
        $list = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new HydrationException($owner, $key, 'list<string>', $value);
            }
            $list[] = $item;
        }

        return $list;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param array<string, mixed> $data
     * @param class-string<T>      $enum
     *
     * @return T
     */
    public static function enum(array $data, string $key, string $owner, string $enum): \BackedEnum
    {
        return self::tryEnum($data, $key, $owner, $enum, false) ?? throw new HydrationException($owner, $key, self::shortName($enum), null);
    }

    /**
     * @template T of \BackedEnum
     *
     * @param array<string, mixed> $data
     * @param class-string<T>      $enum
     *
     * @return T|null
     */
    public static function nullableEnum(array $data, string $key, string $owner, string $enum): ?\BackedEnum
    {
        return self::tryEnum($data, $key, $owner, $enum, true);
    }

    /** @param array<string, mixed> $data */
    public static function isoUtc(array $data, string $key, string $owner): \DateTimeImmutable
    {
        return self::nullableIsoUtc($data, $key, $owner) ?? throw new HydrationException($owner, $key, 'ISO-8601 UTC timestamp', null);
    }

    /** @param array<string, mixed> $data */
    public static function nullableIsoUtc(array $data, string $key, string $owner): ?\DateTimeImmutable
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return null;
        }
        if (!is_string($value)) {
            throw new HydrationException($owner, $key, 'ISO-8601 UTC timestamp', $value);
        }
        try {
            return FactroDateTime::fromIsoUtc($value);
        } catch (\InvalidArgumentException) {
            throw new HydrationException($owner, $key, 'ISO-8601 UTC timestamp', $value);
        }
    }

    /** @param array<string, mixed> $data */
    public static function jsTimestamp(array $data, string $key, string $owner): \DateTimeImmutable
    {
        return self::nullableJsTimestamp($data, $key, $owner) ?? throw new HydrationException($owner, $key, 'JavaScript timestamp', null);
    }

    /** @param array<string, mixed> $data */
    public static function nullableJsTimestamp(array $data, string $key, string $owner): ?\DateTimeImmutable
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return null;
        }
        if (!is_int($value) && !is_float($value)) {
            throw new HydrationException($owner, $key, 'JavaScript timestamp', $value);
        }

        return FactroDateTime::fromJsTimestamp($value);
    }

    /** @param array<string, mixed> $data */
    public static function ymd(array $data, string $key, string $owner): CalendarDate
    {
        return self::nullableYmd($data, $key, $owner) ?? throw new HydrationException($owner, $key, 'Y-m-d date', null);
    }

    /** @param array<string, mixed> $data */
    public static function nullableYmd(array $data, string $key, string $owner): ?CalendarDate
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return null;
        }
        if (!is_string($value)) {
            throw new HydrationException($owner, $key, 'Y-m-d date', $value);
        }
        try {
            return CalendarDate::fromYmd($value);
        } catch (\InvalidArgumentException) {
            throw new HydrationException($owner, $key, 'Y-m-d date', $value);
        }
    }

    /**
     * @template T
     *
     * @param array<string, mixed>              $data
     * @param \Closure(array<string, mixed>): T $hydrator
     *
     * @return list<T>
     */
    public static function objectList(array $data, string $key, string $owner, \Closure $hydrator): array
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return [];
        }
        if (!is_array($value)) {
            throw new HydrationException($owner, $key, 'list<array>', $value);
        }
        $list = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                throw new HydrationException($owner, $key, 'list<array>', $value);
            }
            $row = [];
            foreach ($item as $k => $v) {
                $row[(string) $k] = $v;
            }
            $list[] = $hydrator($row);
        }

        return $list;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param array<string, mixed> $data
     * @param class-string<T>      $enum
     *
     * @return T|null
     */
    private static function tryEnum(array $data, string $key, string $owner, string $enum, bool $nullable): ?\BackedEnum
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return null;
        }
        $expected = ($nullable ? '?' : '').self::shortName($enum);
        if (!is_int($value) && !is_string($value)) {
            throw new HydrationException($owner, $key, $expected, $value);
        }
        try {
            $case = $enum::tryFrom($value);
        } catch (\TypeError) {
            $case = null;
        }

        return $case ?? throw new HydrationException($owner, $key, $expected, $value);
    }

    private static function shortName(string $class): string
    {
        $pos = strrpos($class, '\\');

        return false === $pos ? $class : substr($class, $pos + 1);
    }
}
