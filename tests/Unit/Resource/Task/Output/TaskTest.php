<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Resource\Task\TaskPriority;
use Nxi\Factro\Resource\Task\TaskState;
use Nxi\Factro\Resource\Task\Urgency;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\CalendarDate;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Task::class)]
final class TaskTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('tasks-by-project')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $task = Task::fromArray($row);

        self::assertSame($row['id'], $task->id);
        self::assertSame($row['title'], $task->title);
        self::assertSame($row['description'], $task->description);
        self::assertSame($row['projectId'], $task->projectId);
        self::assertSame($row['parentPackageId'], $task->parentPackageId);
        self::assertIsString($row['taskState']);
        self::assertSame(TaskState::from($row['taskState']), $task->taskState);
        self::assertNull($task->taskPriority);
        self::assertSame(Urgency::NORMAL, $task->urgency);
        self::assertSame($row['isMilestone'], $task->isMilestone);
        self::assertIsString($row['startDate']);
        self::assertSame($row['startDate'], FactroDateTime::toIsoUtc($task->startDate ?? throw new \LogicException()));
        self::assertIsString($row['endDate']);
        self::assertSame($row['endDate'], FactroDateTime::toIsoUtc($task->endDate ?? throw new \LogicException()));
        self::assertNull($task->pausedUntil);
        self::assertIsString($row['creationDate']);
        self::assertSame($row['creationDate'], FactroDateTime::toIsoUtc($task->creationDate));
        self::assertIsString($row['changeDate']);
        self::assertSame($row['changeDate'], FactroDateTime::toIsoUtc($task->changeDate ?? throw new \LogicException()));
        self::assertSame($row['creatorId'], $task->creatorId);
        self::assertSame($row['officerId'], $task->officerId);
        self::assertSame($row['executorId'], $task->executorId);
        self::assertSame($row['companyId'], $task->companyId);
        self::assertSame($row['companyContactId'], $task->companyContactId);
        self::assertSame(8.0, $task->plannedEffort);
        self::assertSame(2.5, $task->realizedEffort);
        self::assertSame(5.5, $task->remainingEffort);
        self::assertSame($row['number'], $task->number);
        self::assertSame($row['colorScheme'], $task->colorScheme);
        self::assertSame($row['customFields'], $task->customFields);
        self::assertSame($row['mandantId'], $task->mandantId);
    }

    public function testHydratesEveryFixtureRow(): void
    {
        $tasks = array_map(static function (mixed $row): Task {
            self::assertIsArray($row);

            /* @var array<string, mixed> $row */
            return Task::fromArray($row);
        }, Fixtures::json('tasks-by-project'));

        self::assertCount(5, $tasks);
        self::assertSame(TaskPriority::PRIORITY_50, $tasks[3]->taskPriority);
        self::assertNull($tasks[3]->executorId);
        self::assertNull($tasks[3]->startDate);
        self::assertTrue($tasks[4]->isMilestone);
        self::assertFalse($tasks[4]->taskState->isOpen());
    }

    public function testDateVariantsMapToTheSameCalendarDay(): void
    {
        $berlin = new \DateTimeZone('Europe/Berlin');
        $seen = 0;
        foreach (Fixtures::json('tasks-by-project') as $row) {
            self::assertIsArray($row);
            /** @var array<string, mixed> $row */
            $task = Task::fromArray($row);
            if (null === $task->endDate) {
                continue;
            }
            self::assertIsString($row['endDate']);
            $expected = CalendarDate::fromFactro($row['endDate'], $berlin);
            self::assertNotNull($expected);
            self::assertTrue($expected->equals(CalendarDate::fromDateTime($task->endDate, $berlin)));
            ++$seen;
        }
        self::assertSame(4, $seen);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'title', 'projectId', 'taskState', 'isMilestone', 'creationDate', 'creatorId', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Task::fromArray($row);
    }

    public function testUnknownTaskStateThrowsHydrationException(): void
    {
        $row = $this->row();
        $row['taskState'] = 'archived';

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('expected TaskState, got string "archived"');
        Task::fromArray($row);
    }

    public function testTargetParentIdIsIgnored(): void
    {
        $row = $this->row() + ['targetParentId' => 'anything'];
        $row['targetParentId'] = 'anything';

        self::assertInstanceOf(Task::class, Task::fromArray($row));
    }
}
