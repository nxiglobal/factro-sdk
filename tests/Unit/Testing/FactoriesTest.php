<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\Project\Output\Project;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Testing\Factory\AbsenceFactory;
use Nxi\Factro\Testing\Factory\AppointmentFactory;
use Nxi\Factro\Testing\Factory\ChecklistEntryFactory;
use Nxi\Factro\Testing\Factory\CompanyFactory;
use Nxi\Factro\Testing\Factory\ContactFactory;
use Nxi\Factro\Testing\Factory\CustomViewFactory;
use Nxi\Factro\Testing\Factory\DocumentFactory;
use Nxi\Factro\Testing\Factory\EmployeeTagFactory;
use Nxi\Factro\Testing\Factory\PackageFactory;
use Nxi\Factro\Testing\Factory\ProjectFactory;
use Nxi\Factro\Testing\Factory\TaskCommentFactory;
use Nxi\Factro\Testing\Factory\TaskFactory;
use Nxi\Factro\Testing\Factory\TeamFactory;
use Nxi\Factro\Testing\Factory\TodoListFactory;
use Nxi\Factro\Testing\Factory\UserFactory;
use Nxi\Factro\Testing\Factory\WorkRecordFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskFactory::class)]
#[CoversClass(ProjectFactory::class)]
#[CoversClass(PackageFactory::class)]
#[CoversClass(UserFactory::class)]
#[CoversClass(CompanyFactory::class)]
#[CoversClass(TaskCommentFactory::class)]
#[CoversClass(WorkRecordFactory::class)]
#[CoversClass(EmployeeTagFactory::class)]
#[CoversClass(AbsenceFactory::class)]
#[CoversClass(AppointmentFactory::class)]
#[CoversClass(ChecklistEntryFactory::class)]
#[CoversClass(ContactFactory::class)]
#[CoversClass(CustomViewFactory::class)]
#[CoversClass(DocumentFactory::class)]
#[CoversClass(TeamFactory::class)]
#[CoversClass(TodoListFactory::class)]
final class FactoriesTest extends TestCase
{
    public function testEveryFactoryOfTheNewResourcesHydratesItsDto(): void
    {
        foreach ([AbsenceFactory::class, AppointmentFactory::class, ChecklistEntryFactory::class, ContactFactory::class, CustomViewFactory::class, DocumentFactory::class, TeamFactory::class, TodoListFactory::class] as $factory) {
            self::assertIsObject($factory::dto());
            self::assertCount(2, $factory::many(2));
        }
    }

    public function testMakeAppliesOverridesAndDtoHydrates(): void
    {
        $row = TaskFactory::make(['title' => 'Custom', 'executorId' => null]);

        self::assertSame('Custom', $row['title']);
        self::assertArrayHasKey('executorId', $row);
        self::assertNull($row['executorId']);
        self::assertSame('Custom', TaskFactory::dto(['title' => 'Custom'])->title);
        self::assertInstanceOf(Task::class, TaskFactory::dto());
    }

    public function testManyProducesDistinctIdsAndNumbers(): void
    {
        $rows = ProjectFactory::many(3, ['title' => 'Same']);

        self::assertCount(3, $rows);
        self::assertCount(3, array_unique($this->column($rows, 'id')));
        self::assertCount(3, array_unique($this->column($rows, 'number')));
        self::assertSame(['Same', 'Same', 'Same'], $this->column($rows, 'title'));
        self::assertIsString($rows[0]['id']);
        self::assertStringEndsWith('-1', $rows[0]['id']);
        self::assertContainsOnlyInstancesOf(Project::class, array_map(Project::fromArray(...), $rows));
    }

    public function testManyWithoutNumberField(): void
    {
        $rows = UserFactory::many(2);

        self::assertCount(2, array_unique($this->column($rows, 'id')));
        self::assertArrayNotHasKey('number', $rows[0]);
    }

    /** @return iterable<string, array{class-string}> */
    public static function factories(): iterable
    {
        foreach ([TaskFactory::class, ProjectFactory::class, PackageFactory::class, UserFactory::class, CompanyFactory::class, TaskCommentFactory::class, WorkRecordFactory::class, EmployeeTagFactory::class] as $f) {
            yield $f => [$f];
        }
    }

    /**
     * @param class-string $factory
     */
    #[DataProvider('factories')]
    public function testEveryFactoryHydrates(string $factory): void
    {
        $dto = $factory::dto();
        self::assertIsObject($dto);
        $rows = $factory::many(2);
        self::assertCount(2, $rows);
        self::assertNotSame($rows[0]['id'], $rows[1]['id']);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<int|string>
     */
    private function column(array $rows, string $key): array
    {
        return array_map(static function (array $row) use ($key): int|string {
            $value = $row[$key] ?? null;
            self::assertTrue(is_int($value) || is_string($value));

            return $value;
        }, $rows);
    }
}
