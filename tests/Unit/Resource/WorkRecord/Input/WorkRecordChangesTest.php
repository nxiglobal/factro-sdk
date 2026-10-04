<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\WorkRecord\Input;

use Nxi\Factro\Resource\WorkRecord\Input\WorkRecordChanges;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecordLocation;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkRecordChanges::class)]
final class WorkRecordChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSent(): void
    {
        $changes = new WorkRecordChanges(startDate: CalendarDate::fromYmd('2026-09-03'), minutesWorked: 45, utcOffsetMinutes: 60, bookedOnReferenceType: WorkRecordReferenceType::PACKAGE, isBilled: false);

        self::assertSame(['startDate' => '2026-09-03', 'minutesWorked' => 45, 'utcOffset' => 60, 'bookedOnReferenceType' => 'package', 'isBilled' => false], $changes->toPayload());
        self::assertFalse($changes->isEmpty());
    }

    public function testLocationIsSentAsNestedObjectWithAllFiveKeys(): void
    {
        $changes = new WorkRecordChanges(location: new WorkRecordLocation(null, null, null, null, null));

        self::assertSame(['location' => ['name' => null, 'street' => null, 'city' => null, 'zipCode' => null, 'country' => null]], $changes->toPayload());
        self::assertFalse($changes->isEmpty());
    }

    public function testIsEmpty(): void
    {
        self::assertTrue(new WorkRecordChanges()->isEmpty());
        self::assertSame([], new WorkRecordChanges()->toPayload());
    }

    public function testRejectsMalformedTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new WorkRecordChanges(startTime: '900');
    }
}
