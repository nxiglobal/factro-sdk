<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Appointment\Input;

use Nxi\Factro\Resource\Appointment\Input\NewAppointment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewAppointment::class)]
final class NewAppointmentTest extends TestCase
{
    public function testMinimalPayloadWithSubjectConvertsDatesToUtc(): void
    {
        $appointment = new NewAppointment(
            employeeId: 'e1',
            startDate: new \DateTimeImmutable('2026-09-15T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-09-15T11:30:00+02:00'),
            subject: 'Meeting',
        );

        self::assertSame([
            'employeeId' => 'e1',
            'startDate' => '2026-09-15T08:00:00.000Z',
            'endDate' => '2026-09-15T09:30:00.000Z',
            'subject' => 'Meeting',
        ], $appointment->toPayload());
    }

    public function testReferencedTaskAloneIsSufficient(): void
    {
        $appointment = new NewAppointment('e1', new \DateTimeImmutable('2026-09-15T08:00:00Z'), new \DateTimeImmutable('2026-09-15T09:00:00Z'), referencedTaskId: 't1');

        self::assertSame([
            'employeeId' => 'e1',
            'startDate' => '2026-09-15T08:00:00.000Z',
            'endDate' => '2026-09-15T09:00:00.000Z',
            'referencedTaskId' => 't1',
        ], $appointment->toPayload());
    }

    public function testAllOptionalFields(): void
    {
        $appointment = new NewAppointment(
            employeeId: 'e1',
            startDate: new \DateTimeImmutable('2026-09-15T08:00:00Z'),
            endDate: new \DateTimeImmutable('2026-09-15T09:00:00Z'),
            subject: 'Meeting',
            referencedTaskId: 't1',
            description: '<p>d</p>',
            location: 'Hamburg',
            distance: 12.5,
        );

        self::assertSame([
            'employeeId' => 'e1',
            'startDate' => '2026-09-15T08:00:00.000Z',
            'endDate' => '2026-09-15T09:00:00.000Z',
            'subject' => 'Meeting',
            'referencedTaskId' => 't1',
            'description' => '<p>d</p>',
            'location' => 'Hamburg',
            'distance' => 12.5,
        ], $appointment->toPayload());
    }

    public function testWithoutSubjectAndReferencedTaskIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('subject or referencedTaskId');
        new NewAppointment('e1', new \DateTimeImmutable('2026-09-15T08:00:00Z'), new \DateTimeImmutable('2026-09-15T09:00:00Z'));
    }
}
