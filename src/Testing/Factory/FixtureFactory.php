<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Testing\Fixtures;

/**
 * Base of the testing factories: rows come from the shipped fixtures, overrides are merged with
 * array_replace so that an override of null sets the field to null.
 *
 * @internal subclasses are the public API
 */
abstract class FixtureFactory
{
    /**
     * Name of the fixture file and index of the row used as template.
     *
     * @return array{string, int}
     */
    abstract protected static function source(): array;

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function make(array $overrides = []): array
    {
        [$fixture, $index] = static::source();
        $row = Fixtures::json($fixture)[$index] ?? null;
        if (!is_array($row)) {
            throw new \UnexpectedValueException(sprintf('Fixture "%s" has no row %d.', $fixture, $index));
        }
        $template = [];
        foreach ($row as $key => $value) {
            $template[(string) $key] = $value;
        }

        return array_replace($template, $overrides);
    }

    /**
     * $count rows with distinct ids ("<fixture-id>-<n>") and, when present, increasing numbers.
     *
     * @param array<string, mixed> $overrides
     *
     * @return list<array<string, mixed>>
     */
    public static function many(int $count, array $overrides = []): array
    {
        $base = static::make($overrides);
        $rows = [];
        for ($n = 1; $n <= $count; ++$n) {
            $row = $base;
            $row['id'] = (is_string($base['id'] ?? null) ? $base['id'] : 'id').'-'.$n;
            if (is_int($base['number'] ?? null)) {
                $row['number'] = $base['number'] + $n - 1;
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
