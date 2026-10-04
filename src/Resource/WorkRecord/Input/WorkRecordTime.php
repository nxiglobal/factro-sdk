<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord\Input;

/**
 * Validates the "HH:MM" times of work-record write bodies.
 *
 * @internal
 */
final class WorkRecordTime
{
    private function __construct()
    {
    }

    public static function assertValid(?string $time, string $field): void
    {
        if (null !== $time && 1 !== preg_match('/^\d{2}:\d{2}$/', $time)) {
            throw new \InvalidArgumentException(sprintf('%s must be given as HH:MM, got "%s".', $field, $time));
        }
    }
}
