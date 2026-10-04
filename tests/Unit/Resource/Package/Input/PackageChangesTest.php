<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Package\Input;

use Nxi\Factro\Resource\Package\Input\PackageChanges;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageChanges::class)]
final class PackageChangesTest extends TestCase
{
    public function testDatesAreSentAsFactroMidnight(): void
    {
        $changes = new PackageChanges(startDate: CalendarDate::fromYmd('2026-09-01'), endDate: CalendarDate::fromYmd('2026-09-30'), officerId: 'u');

        self::assertSame(
            ['startDate' => '2026-08-31T22:00:00.000Z', 'endDate' => '2026-09-29T22:00:00.000Z', 'officerId' => 'u'],
            $changes->toPayload(new \DateTimeZone('Europe/Berlin')),
        );
        self::assertFalse($changes->isEmpty());
    }

    public function testIsEmpty(): void
    {
        self::assertTrue(new PackageChanges()->isEmpty());
        self::assertFalse(new PackageChanges(title: 'x')->isEmpty());
        self::assertSame(['title' => 'x'], new PackageChanges(title: 'x')->toPayload(new \DateTimeZone('UTC')));
    }
}
