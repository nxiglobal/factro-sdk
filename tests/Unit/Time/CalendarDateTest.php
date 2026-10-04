<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Time;

use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CalendarDate::class)]
final class CalendarDateTest extends TestCase
{
    private function berlin(): \DateTimeZone
    {
        return new \DateTimeZone('Europe/Berlin');
    }

    /** @return iterable<string, array{string, string}> */
    public static function factroVariants(): iterable
    {
        yield 'T22 summer (UI)' => ['2026-08-31T22:00:00.000Z', '2026-09-01'];
        yield 'T23 winter (UI)' => ['2026-01-31T23:00:00.000Z', '2026-02-01'];
        yield 'T23 summer (legacy)' => ['2026-08-31T23:00:00.000Z', '2026-09-01'];
        yield 'T00 (API with plain date)' => ['2026-09-01T00:00:00.000Z', '2026-09-01'];
        yield 'real time of day' => ['2026-09-01T09:01:39.444Z', '2026-09-01'];
        yield 'late evening crossing midnight' => ['2026-09-01T22:30:00.000Z', '2026-09-02'];
        yield 'without millis' => ['2026-09-01T00:00:00Z', '2026-09-01'];
    }

    #[DataProvider('factroVariants')]
    public function testFromFactroMapsToCalendarDay(string $utc, string $expected): void
    {
        self::assertSame($expected, CalendarDate::fromFactro($utc, $this->berlin())?->toYmd());
    }

    public function testFromFactroNullStaysNull(): void
    {
        self::assertNull(CalendarDate::fromFactro(null, $this->berlin()));
    }

    public function testToFactroIsLocalMidnightInUtc(): void
    {
        self::assertSame('2026-08-31T22:00:00.000Z', CalendarDate::fromYmd('2026-09-01')->toFactro($this->berlin()));
        self::assertSame('2026-01-31T23:00:00.000Z', CalendarDate::fromYmd('2026-02-01')->toFactro($this->berlin()));
        self::assertSame('2026-02-01T00:00:00.000Z', CalendarDate::fromYmd('2026-02-01')->toFactro(new \DateTimeZone('UTC')));
    }

    public function testRoundTrip(): void
    {
        $tz = $this->berlin();
        foreach (['2024-02-29', '2026-03-29', '2026-10-25', '2026-12-31'] as $day) {
            self::assertSame($day, CalendarDate::fromFactro(CalendarDate::fromYmd($day)->toFactro($tz), $tz)?->toYmd());
        }
    }

    public function testFromYmdIsStrict(): void
    {
        $date = CalendarDate::fromYmd('2026-09-01');
        self::assertSame('2026-09-01', $date->toYmd());
        self::assertSame(2026, $date->year);
        self::assertSame(9, $date->month);
        self::assertSame(1, $date->day);
        foreach (['2026-9-1', '2026-02-30', '2026-09-01T00:00', 'yesterday', ''] as $bad) {
            try {
                CalendarDate::fromYmd($bad);
                self::fail("expected rejection of {$bad}");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testComparisonAndArithmetic(): void
    {
        $a = CalendarDate::fromYmd('2026-02-28');
        self::assertTrue($a->isBefore(CalendarDate::fromYmd('2026-03-01')));
        self::assertFalse(CalendarDate::fromYmd('2026-03-01')->isBefore($a));
        self::assertTrue($a->addDays(1)->equals(CalendarDate::fromYmd('2026-03-01')));
        self::assertFalse($a->equals(CalendarDate::fromYmd('2026-03-01')));
        self::assertSame('2026-02-27', (string) $a->addDays(-1));
        self::assertSame('2026-02-28', CalendarDate::fromDateTime(new \DateTimeImmutable('2026-02-27T23:30:00Z'), $this->berlin())->toYmd());
    }
}
