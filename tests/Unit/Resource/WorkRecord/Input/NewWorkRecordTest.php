<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\WorkRecord\Input;

use Nxi\Factro\Resource\WorkRecord\Input\NewWorkRecord;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecordLocation;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewWorkRecord::class)]
final class NewWorkRecordTest extends TestCase
{
    public function testPayload(): void
    {
        $record = new NewWorkRecord(
            startDate: CalendarDate::fromYmd('2026-09-01'), minutesWorked: 90, utcOffsetMinutes: 120,
            bookedOnReferenceId: 't1', bookedOnReferenceType: WorkRecordReferenceType::TASK, description: 'Review',
            startTime: '09:00', endTime: '10:30', isBillable: true,
        );

        self::assertSame([
            'startDate' => '2026-09-01', 'minutesWorked' => 90, 'utcOffset' => 120,
            'bookedOnReferenceId' => 't1', 'bookedOnReferenceType' => 'task',
            'description' => 'Review', 'startTime' => '09:00', 'endTime' => '10:30', 'isBillable' => true,
        ], $record->toPayload());
    }

    public function testLocationAndAllOptionalFields(): void
    {
        $record = new NewWorkRecord(
            startDate: CalendarDate::fromYmd('2026-09-01'), minutesWorked: 30, utcOffsetMinutes: 0,
            bookedOnReferenceId: 'p1', bookedOnReferenceType: WorkRecordReferenceType::PROJECT,
            endDate: CalendarDate::fromYmd('2026-09-02'), isBilled: false,
            internalBookingDetails: 'i', externalBookingDetails: 'e', workEmployeeId: 'u', travelledDistanceKm: 3.5,
            createdInContextOfReferenceId: 't9', createdInContextOfReferenceType: 'task', remainingTaskEffort: 1.5,
            location: new WorkRecordLocation(name: 'Office', street: null, city: 'Hamburg', zipCode: null, country: 'DE'),
        );

        self::assertSame([
            'startDate' => '2026-09-01', 'minutesWorked' => 30, 'utcOffset' => 0,
            'bookedOnReferenceId' => 'p1', 'bookedOnReferenceType' => 'project',
            'endDate' => '2026-09-02', 'isBilled' => false,
            'internalBookingDetails' => 'i', 'externalBookingDetails' => 'e', 'workEmployeeId' => 'u', 'travelledDistanceKm' => 3.5,
            'createdInContextOfReferenceId' => 't9', 'createdInContextOfReferenceType' => 'task', 'remainingTaskEffort' => 1.5,
            'location' => ['name' => 'Office', 'street' => null, 'city' => 'Hamburg', 'zipCode' => null, 'country' => 'DE'],
        ], $record->toPayload());
    }

    public function testRejectsMalformedTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NewWorkRecord(CalendarDate::fromYmd('2026-09-01'), 1, 0, 'x', WorkRecordReferenceType::TASK, startTime: '9:00');
    }

    public function testRejectsMalformedEndTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NewWorkRecord(CalendarDate::fromYmd('2026-09-01'), 1, 0, 'x', WorkRecordReferenceType::TASK, endTime: '10:30:00');
    }
}
