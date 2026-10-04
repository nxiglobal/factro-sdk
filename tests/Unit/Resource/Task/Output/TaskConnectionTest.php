<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Task\Output\TaskConnection;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskConnection::class)]
final class TaskConnectionTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(): array
    {
        $row = Fixtures::json('task-connections-synthetic')[0];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromSyntheticFixture(): void
    {
        $connection = TaskConnection::fromArray($this->row());

        self::assertSame('c1', $connection->id);
        self::assertSame('t1', $connection->taskId);
        self::assertSame('t2', $connection->referenceId);
        self::assertTrue($connection->isPredecessor);
        self::assertFalse($connection->isSuccessor);
        self::assertSame('2026-09-01T10:00:00+00:00', $connection->changeDate?->format(DATE_ATOM));
    }

    public function testLiveFixtureIsAnEmptyList(): void
    {
        self::assertSame([], Fixtures::json('task-connections'));
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'taskId', 'referenceId', 'isPredecessor', 'isSuccessor'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        TaskConnection::fromArray($row);
    }
}
