<?php

declare(strict_types=1);

namespace Nxi\Factro\Time;

/**
 * A calendar day without time of day.
 *
 * factro stores dates as UTC timestamps in three variants (T22:00Z / T23:00Z from the UI,
 * T00:00Z from the API); fromFactro() maps all of them onto the calendar day of the target zone.
 */
final readonly class CalendarDate implements \Stringable
{
    private function __construct(public int $year, public int $month, public int $day)
    {
    }

    /**
     * Strict "Y-m-d", validated with checkdate().
     *
     * @throws \InvalidArgumentException
     */
    public static function fromYmd(string $ymd): self
    {
        if (1 !== preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            throw new \InvalidArgumentException(sprintf('Expected a calendar date as Y-m-d, got "%s".', $ymd));
        }

        return new self((int) $m[1], (int) $m[2], (int) $m[3]);
    }

    public static function fromDateTime(\DateTimeInterface $value, \DateTimeZone $timezone): self
    {
        return self::fromYmd(\DateTimeImmutable::createFromInterface($value)->setTimezone($timezone)->format('Y-m-d'));
    }

    /**
     * Parses the UTC timestamp, converts it to $timezone and takes the calendar day there.
     */
    public static function fromFactro(?string $isoUtc, \DateTimeZone $timezone): ?self
    {
        return null === $isoUtc ? null : self::fromDateTime(FactroDateTime::fromIsoUtc($isoUtc), $timezone);
    }

    /**
     * Midnight in $timezone converted to UTC, the storage form the factro UI uses.
     */
    public function toFactro(\DateTimeZone $timezone): string
    {
        return FactroDateTime::toIsoUtc(new \DateTimeImmutable($this->toYmd().' 00:00:00', $timezone));
    }

    public function toYmd(): string
    {
        return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
    }

    public function __toString(): string
    {
        return $this->toYmd();
    }

    public function equals(self $other): bool
    {
        return $this->toYmd() === $other->toYmd();
    }

    public function isBefore(self $other): bool
    {
        return $this->toYmd() < $other->toYmd();
    }

    public function addDays(int $days): self
    {
        return self::fromYmd(new \DateTimeImmutable($this->toYmd(), new \DateTimeZone('UTC'))->modify(sprintf('%+d days', $days))->format('Y-m-d'));
    }
}
