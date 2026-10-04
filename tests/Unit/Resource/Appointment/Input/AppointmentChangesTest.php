<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Appointment\Input;

use Nxi\Factro\Resource\Appointment\Input\AppointmentChanges;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppointmentChanges::class)]
final class AppointmentChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSentAndDatesAreUtc(): void
    {
        $changes = new AppointmentChanges(
            subject: 'New',
            referencedTaskId: 't1',
            description: '<p>x</p>',
            location: 'Berlin',
            distance: 3.25,
            startDate: new \DateTimeImmutable('2026-09-15T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-09-15T11:00:00+02:00'),
        );

        self::assertSame([
            'subject' => 'New',
            'referencedTaskId' => 't1',
            'description' => '<p>x</p>',
            'location' => 'Berlin',
            'distance' => 3.25,
            'startDate' => '2026-09-15T08:00:00.000Z',
            'endDate' => '2026-09-15T09:00:00.000Z',
        ], $changes->toPayload());
    }

    public function testZeroDistanceIsSentAndIsEmptyWorks(): void
    {
        self::assertSame(['distance' => 0.0], new AppointmentChanges(distance: 0.0)->toPayload());
        self::assertTrue(new AppointmentChanges()->isEmpty());
        self::assertFalse(new AppointmentChanges(distance: 0.0)->isEmpty());
        self::assertFalse(new AppointmentChanges(subject: 'x')->isEmpty());
    }
}
