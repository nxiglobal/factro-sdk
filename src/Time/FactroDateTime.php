<?php

declare(strict_types=1);

namespace Nxi\Factro\Time;

/**
 * Conversions between the timestamp formats factro uses and DateTimeImmutable in UTC.
 */
final class FactroDateTime
{
    public const string ISO_UTC = 'Y-m-d\TH:i:s.v\Z';

    private function __construct()
    {
    }

    /**
     * Accepts "2026-08-31T22:00:00.000Z" with milliseconds, microseconds or no fraction.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromIsoUtc(string $value): \DateTimeImmutable
    {
        if (1 !== preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?Z$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Expected an ISO-8601 UTC timestamp, got "%s".', $value));
        }

        // The trailing "Z" wins over the constructor zone and yields a zone named "Z"; normalise to "UTC".
        return new \DateTimeImmutable($value)->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * JavaScript timestamps are milliseconds since the Unix epoch.
     */
    public static function fromJsTimestamp(int|float $milliseconds): \DateTimeImmutable
    {
        $milliseconds = (int) round($milliseconds);
        $seconds = intdiv($milliseconds, 1000);
        $micro = ($milliseconds % 1000) * 1000;

        return new \DateTimeImmutable('@'.$seconds)
            ->modify(sprintf('%+d microseconds', $micro))
            ->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * Combines a work-record date with its "HH:MM" time. A missing time means midnight; a missing
     * offset means the fallback zone; an offset in minutes yields a fixed "+HH:MM" zone.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromDateAndTime(CalendarDate $date, ?string $time, ?int $utcOffsetMinutes, \DateTimeZone $fallback): \DateTimeImmutable
    {
        $time ??= '00:00';
        if (1 !== preg_match('/^\d{2}:\d{2}$/', $time)) {
            throw new \InvalidArgumentException(sprintf('Expected a time as HH:MM, got "%s".', $time));
        }
        $zone = null === $utcOffsetMinutes
            ? $fallback
            : new \DateTimeZone(sprintf('%s%02d:%02d', $utcOffsetMinutes < 0 ? '-' : '+', intdiv(abs($utcOffsetMinutes), 60), abs($utcOffsetMinutes) % 60));

        return new \DateTimeImmutable($date->toYmd().' '.$time.':00', $zone);
    }

    /**
     * Converts to UTC first, then formats with millisecond precision.
     */
    public static function toIsoUtc(\DateTimeInterface $value): string
    {
        return \DateTimeImmutable::createFromInterface($value)->setTimezone(new \DateTimeZone('UTC'))->format(self::ISO_UTC);
    }
}
