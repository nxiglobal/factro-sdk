<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\Task\Output\ChecklistEntry;
use Nxi\Factro\Testing\Factory\ChecklistEntryFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChecklistEntryFactory::class)]
final class ChecklistEntryFactoryTest extends TestCase
{
    public function testMakeUsesTheFirstFixtureRowAndAppliesOverrides(): void
    {
        $row = ChecklistEntryFactory::make(['title' => 'Custom', 'assigneeId' => null]);

        self::assertSame('3b7a1c9e-5d2f-5a8b-9c4d-1e6f7a8b9c0d', $row['id']);
        self::assertSame('Custom', $row['title']);
        self::assertArrayHasKey('assigneeId', $row);
        self::assertNull($row['assigneeId']);
    }

    public function testDtoHydrates(): void
    {
        $entry = ChecklistEntryFactory::dto(['checked' => true]);

        self::assertInstanceOf(ChecklistEntry::class, $entry);
        self::assertTrue($entry->checked);
        self::assertSame('e53f64f5-6dcc-5990-af50-e315dc575d1a', $entry->taskId);
    }

    public function testManyProducesDistinctIds(): void
    {
        $rows = ChecklistEntryFactory::many(3);

        self::assertCount(3, $rows);
        $ids = array_map(static function (array $row): string {
            self::assertIsString($row['id']);

            return $row['id'];
        }, $rows);
        self::assertCount(3, array_unique($ids));
        self::assertContainsOnlyInstancesOf(ChecklistEntry::class, array_map(ChecklistEntry::fromArray(...), $rows));
    }
}
