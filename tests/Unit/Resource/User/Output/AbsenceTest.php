<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\User\AbsenceType;
use Nxi\Factro\Resource\User\Output\Absence;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Absence::class)]
final class AbsenceTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('absences')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $absence = Absence::fromArray($row);

        self::assertSame($row['id'], $absence->id);
        self::assertSame($row['employeeId'], $absence->employeeId);
        self::assertSame($row['startDate'], FactroDateTime::toIsoUtc($absence->startDate));
        self::assertSame($row['endDate'], FactroDateTime::toIsoUtc($absence->endDate));
        self::assertSame(AbsenceType::PLANNED, $absence->type);
        self::assertSame($row['mandantId'], $absence->mandantId);
    }

    public function testEveryTypeIsCoveredByTheListFixture(): void
    {
        $types = array_map(static function (mixed $row): string {
            self::assertIsArray($row);

            /* @var array<string, mixed> $row */
            return Absence::fromArray($row)->type->value;
        }, Fixtures::json('absences'));

        self::assertEqualsCanonicalizing(array_column(AbsenceType::cases(), 'value'), array_values(array_unique($types)));
    }

    public function testSingleFixtureHydrates(): void
    {
        $absence = Absence::fromArray(Fixtures::json('absence'));

        self::assertSame(AbsenceType::PLANNED, $absence->type);
        self::assertSame('UTC', $absence->startDate->getTimezone()->getName());
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'employeeId', 'startDate', 'endDate', 'type', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Absence::fromArray($row);
    }

    public function testUnknownTypeThrows(): void
    {
        $row = $this->row();
        $row['type'] = 'Sick';

        $this->expectException(HydrationException::class);
        Absence::fromArray($row);
    }
}
