<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Task\Output\ChecklistEntry;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChecklistEntry::class)]
final class ChecklistEntryTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('checklist')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $entry = ChecklistEntry::fromArray($row);

        self::assertSame($row['id'], $entry->id);
        self::assertSame($row['taskId'], $entry->taskId);
        self::assertSame($row['title'], $entry->title);
        self::assertFalse($entry->checked);
        self::assertSame(1.0, $entry->position);
        self::assertIsString($row['endDate']);
        self::assertSame($row['endDate'], FactroDateTime::toIsoUtc($entry->endDate ?? throw new \LogicException()));
        self::assertSame($row['assigneeId'], $entry->assigneeId);
        self::assertIsString($row['changeDate']);
        self::assertSame($row['changeDate'], FactroDateTime::toIsoUtc($entry->changeDate ?? throw new \LogicException()));
    }

    public function testHydratesEveryFixtureRow(): void
    {
        $entries = array_map(static function (mixed $row): ChecklistEntry {
            self::assertIsArray($row);

            /* @var array<string, mixed> $row */
            return ChecklistEntry::fromArray($row);
        }, Fixtures::json('checklist'));

        self::assertCount(3, $entries);
        self::assertTrue($entries[1]->checked);
        self::assertSame(2.5, $entries[1]->position);
        self::assertNull($entries[1]->endDate);
        self::assertNull($entries[1]->assigneeId);
        self::assertNull($entries[2]->position);
        self::assertNull($entries[2]->changeDate);
    }

    public function testOptionalKeysMayBeAbsent(): void
    {
        $entry = ChecklistEntry::fromArray(['id' => 'c', 'taskId' => 't', 'title' => 'x', 'checked' => true]);

        self::assertNull($entry->position);
        self::assertNull($entry->endDate);
        self::assertNull($entry->assigneeId);
        self::assertNull($entry->changeDate);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'taskId', 'title', 'checked'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        ChecklistEntry::fromArray($row);
    }
}
