<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Time;

use Nxi\Factro\Time\CalendarDate;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FactroDateTime::class)]
final class FactroDateTimeTest extends TestCase
{
    public function testFromIsoUtcAcceptsMillisMicrosAndNone(): void
    {
        self::assertSame('2026-08-31 22:00:00.000000 UTC', FactroDateTime::fromIsoUtc('2026-08-31T22:00:00.000Z')->format('Y-m-d H:i:s.u e'));
        self::assertSame('2026-08-31 22:00:00.123456 UTC', FactroDateTime::fromIsoUtc('2026-08-31T22:00:00.123456Z')->format('Y-m-d H:i:s.u e'));
        self::assertSame('2026-08-31 22:00:00.000000 UTC', FactroDateTime::fromIsoUtc('2026-08-31T22:00:00Z')->format('Y-m-d H:i:s.u e'));
    }

    public function testFromIsoUtcRejectsOtherFormats(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FactroDateTime::fromIsoUtc('2026-08-31 22:00:00');
    }

    public function testFromJsTimestamp(): void
    {
        self::assertSame('2025-09-01T08:00:00.000Z', FactroDateTime::toIsoUtc(FactroDateTime::fromJsTimestamp(1756713600000)));
        self::assertSame('2025-09-01T08:00:00.500Z', FactroDateTime::toIsoUtc(FactroDateTime::fromJsTimestamp(1756713600500.0)));
    }

    public function testFromDateAndTime(): void
    {
        $berlin = new \DateTimeZone('Europe/Berlin');
        $date = CalendarDate::fromYmd('2026-09-01');

        self::assertSame('2026-09-01T07:30:00.000Z', FactroDateTime::toIsoUtc(FactroDateTime::fromDateAndTime($date, '09:30', null, $berlin)));
        self::assertSame('2026-09-01T07:30:00.000Z', FactroDateTime::toIsoUtc(FactroDateTime::fromDateAndTime($date, '09:30', 120, $berlin)));
        self::assertSame('2026-09-01T09:30:00.000Z', FactroDateTime::toIsoUtc(FactroDateTime::fromDateAndTime($date, '09:30', 0, $berlin)));
        self::assertSame('2026-09-01T14:30:00.000Z', FactroDateTime::toIsoUtc(FactroDateTime::fromDateAndTime($date, '09:30', -300, $berlin)));
        self::assertSame('2026-08-31T22:00:00.000Z', FactroDateTime::toIsoUtc(FactroDateTime::fromDateAndTime($date, null, null, $berlin)));
    }

    public function testFromDateAndTimeRejectsMalformedTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FactroDateTime::fromDateAndTime(CalendarDate::fromYmd('2026-09-01'), '9:30', null, new \DateTimeZone('UTC'));
    }

    public function testToIsoUtcConvertsZone(): void
    {
        self::assertSame('2026-09-01T07:30:00.000Z', FactroDateTime::toIsoUtc(new \DateTimeImmutable('2026-09-01 09:30:00', new \DateTimeZone('Europe/Berlin'))));
        self::assertSame('2026-09-01T07:30:00.000Z', FactroDateTime::toIsoUtc(new \DateTime('2026-09-01 09:30:00', new \DateTimeZone('Europe/Berlin'))));
    }
}
