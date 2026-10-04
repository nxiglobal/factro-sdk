<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Appointment\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Appointment\Output\Appointment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Appointment::class)]
final class AppointmentTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('appointments')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $appointment = Appointment::fromArray($row);

        self::assertSame($row['id'], $appointment->id);
        self::assertSame($row['subject'], $appointment->subject);
        self::assertSame($row['description'], $appointment->description);
        self::assertSame($row['location'], $appointment->location);
        self::assertSame(12.5, $appointment->distance);
        self::assertSame(1.5, $appointment->duration);
        self::assertSame($row['employeeId'], $appointment->employeeId);
        self::assertNull($appointment->referencedTaskId);
        self::assertIsString($row['startDate']);
        self::assertSame($row['startDate'], FactroDateTime::toIsoUtc($appointment->startDate));
        self::assertIsString($row['endDate']);
        self::assertSame($row['endDate'], FactroDateTime::toIsoUtc($appointment->endDate));
        self::assertSame($row['creatorId'], $appointment->creatorId);
        self::assertIsString($row['creationDate']);
        self::assertSame($row['creationDate'], FactroDateTime::toIsoUtc($appointment->creationDate ?? throw new \LogicException()));
        self::assertIsString($row['changeDate']);
        self::assertSame($row['changeDate'], FactroDateTime::toIsoUtc($appointment->changeDate ?? throw new \LogicException()));
        self::assertSame($row['mandantId'], $appointment->mandantId);
    }

    public function testHydratesEveryFixtureRow(): void
    {
        $appointments = array_map(static function (mixed $row): Appointment {
            self::assertIsArray($row);

            /* @var array<string, mixed> $row */
            return Appointment::fromArray($row);
        }, Fixtures::json('appointments'));

        self::assertCount(3, $appointments);
        self::assertSame('e53f64f5-6dcc-5990-af50-e315dc575d1a', $appointments[1]->referencedTaskId);
        self::assertSame(2.0, $appointments[1]->duration);
        self::assertNull($appointments[1]->distance);
        self::assertNull($appointments[1]->changeDate);
        self::assertNull($appointments[2]->description);
        self::assertNull($appointments[2]->creatorId);
        self::assertNull($appointments[2]->creationDate);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'employeeId', 'startDate', 'endDate'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Appointment::fromArray($row);
    }

    public function testInvalidDistanceThrows(): void
    {
        $row = $this->row();
        $row['distance'] = '12 km';

        $this->expectException(HydrationException::class);
        Appointment::fromArray($row);
    }
}
