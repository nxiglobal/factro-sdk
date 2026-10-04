<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\WorkRecord\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecord;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecordLocation;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkRecord::class)]
#[CoversClass(WorkRecordLocation::class)]
final class WorkRecordTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('work-records-by-project')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    /** @return array<string, mixed> */
    private function minimal(): array
    {
        return ['id' => 'w', 'title' => 't', 'workEmployeeId' => 'u', 'startDate' => '2026-09-01', 'startTime' => '09:30', 'minutesWorked' => 60,
            'isBillable' => true, 'isBilled' => false, 'bookedOnReferenceId' => 'task', 'bookedOnReferenceType' => 'task', 'createdAt' => 1756713600000, 'mandantId' => 'm'];
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $record = WorkRecord::fromArray($row);

        self::assertSame($row['id'], $record->id);
        self::assertSame($row['title'], $record->title);
        self::assertSame($row['description'], $record->description);
        self::assertSame($row['workEmployeeId'], $record->workEmployeeId);
        self::assertSame($row['creatorEmployeeId'], $record->creatorEmployeeId);
        self::assertSame($row['startDate'], $record->startDate->toYmd());
        self::assertSame($row['startTime'], $record->startTime);
        self::assertSame($row['endDate'], $record->endDate?->toYmd());
        self::assertSame($row['endTime'], $record->endTime);
        self::assertSame(120, $record->utcOffsetMinutes);
        self::assertSame($row['minutesWorked'], $record->minutesWorked);
        self::assertSame($row['isBillable'], $record->isBillable);
        self::assertSame($row['isBilled'], $record->isBilled);
        self::assertSame($row['internalBookingDetails'], $record->internalBookingDetails);
        self::assertNull($record->externalBookingDetails);
        self::assertSame($row['bookedOnReferenceId'], $record->bookedOnReferenceId);
        self::assertIsString($row['bookedOnReferenceType']);
        self::assertSame(WorkRecordReferenceType::from($row['bookedOnReferenceType']), $record->bookedOnReferenceType);
        self::assertNull($record->createdInContextOfReferenceId);
        self::assertNull($record->createdInContextOfReferenceType);
        self::assertNull($record->createdInContextOfReferenceTitle);
        self::assertNull($record->travelledDistanceKm);
        self::assertInstanceOf(WorkRecordLocation::class, $record->location);
        self::assertNull($record->location->name);
        self::assertNull($record->location->street);
        self::assertNull($record->location->city);
        self::assertNull($record->location->zipCode);
        self::assertNull($record->location->country);
        self::assertIsInt($row['createdAt']);
        self::assertSame(intdiv($row['createdAt'], 1000), $record->createdAt->getTimestamp());
        self::assertIsInt($row['updatedAt']);
        self::assertSame(intdiv($row['updatedAt'], 1000), $record->updatedAt?->getTimestamp());
        self::assertSame($row['mandantId'], $record->mandantId);
    }

    public function testContextAndDistanceRows(): void
    {
        $second = WorkRecord::fromArray($this->row(1));
        self::assertNull($second->utcOffsetMinutes);
        self::assertNotNull($second->createdInContextOfReferenceId);
        self::assertSame('project', $second->createdInContextOfReferenceType);
        self::assertSame(12.5, $second->travelledDistanceKm);

        $third = WorkRecord::fromArray($this->row(2));
        self::assertTrue($third->isBilled);
        self::assertNull($third->updatedAt);
        self::assertNull($third->creatorEmployeeId);
    }

    public function testStartsAtUsesOffsetWhenPresentElseFallbackZone(): void
    {
        $base = $this->minimal();
        $berlin = new \DateTimeZone('Europe/Berlin');

        self::assertSame('2026-09-01T07:30:00.000Z', FactroDateTime::toIsoUtc(WorkRecord::fromArray($base)->startsAt($berlin)));
        self::assertSame('2026-09-01T09:30:00.000Z', FactroDateTime::toIsoUtc(WorkRecord::fromArray($base + ['utcOffset' => 0])->startsAt($berlin)));
        self::assertNull(WorkRecord::fromArray($base)->endsAt($berlin));
        self::assertSame('2026-09-01T08:30:00.000Z', FactroDateTime::toIsoUtc(WorkRecord::fromArray($base + ['endDate' => '2026-09-01', 'endTime' => '10:30'])->endsAt($berlin) ?? throw new \LogicException()));
        self::assertSame('2026-08-31T22:00:00.000Z', FactroDateTime::toIsoUtc(WorkRecord::fromArray(array_replace($base, ['startTime' => null]))->startsAt($berlin)));
    }

    public function testLocationMayBeMissing(): void
    {
        self::assertNull(WorkRecord::fromArray($this->minimal())->location);
        self::assertNull(WorkRecord::fromArray($this->minimal() + ['location' => null])->location);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'title', 'workEmployeeId', 'startDate', 'minutesWorked', 'isBillable', 'isBilled', 'bookedOnReferenceId', 'bookedOnReferenceType', 'createdAt', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        WorkRecord::fromArray($row);
    }
}
