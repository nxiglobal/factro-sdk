<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Project\Output\Project;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Project::class)]
final class ProjectTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('projects')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $project = Project::fromArray($row);

        self::assertSame($row['id'], $project->id);
        self::assertSame($row['title'], $project->title);
        self::assertSame($row['description'], $project->description);
        self::assertIsString($row['projectState']);
        self::assertSame(ProjectState::from($row['projectState']), $project->projectState);
        self::assertNull($project->priority);
        self::assertSame($row['isArchived'], $project->isArchived);
        self::assertSame($row['isDraft'], $project->isDraft);
        self::assertIsString($row['startDate']);
        self::assertSame($row['startDate'], FactroDateTime::toIsoUtc($project->startDate ?? throw new \LogicException()));
        self::assertIsString($row['endDate']);
        self::assertSame($row['endDate'], FactroDateTime::toIsoUtc($project->endDate ?? throw new \LogicException()));
        self::assertIsString($row['plannedStartDate']);
        self::assertSame($row['plannedStartDate'], FactroDateTime::toIsoUtc($project->plannedStartDate ?? throw new \LogicException()));
        self::assertIsString($row['plannedEndDate']);
        self::assertSame($row['plannedEndDate'], FactroDateTime::toIsoUtc($project->plannedEndDate ?? throw new \LogicException()));
        self::assertIsString($row['creationDate']);
        self::assertSame($row['creationDate'], FactroDateTime::toIsoUtc($project->creationDate));
        self::assertIsString($row['changeDate']);
        self::assertSame($row['changeDate'], FactroDateTime::toIsoUtc($project->changeDate ?? throw new \LogicException()));
        self::assertSame($row['creatorId'], $project->creatorId);
        self::assertSame($row['officerId'], $project->officerId);
        self::assertSame($row['companyId'], $project->companyId);
        self::assertSame($row['companyContactId'], $project->companyContactId);
        self::assertSame(120.0, $project->plannedEffort);
        self::assertSame(64.5, $project->realizedEffort);
        self::assertSame(55.5, $project->remainingEffort);
        self::assertSame($row['number'], $project->number);
        self::assertSame($row['colorScheme'], $project->colorScheme);
        self::assertSame($row['customFields'], $project->customFields);
        self::assertSame($row['mandantId'], $project->mandantId);
    }

    public function testHydratesEveryFixtureRow(): void
    {
        foreach (Fixtures::json('projects') as $row) {
            self::assertIsArray($row);
            /* @var array<string, mixed> $row */
            self::assertInstanceOf(Project::class, Project::fromArray($row));
        }
        self::assertNull(Project::fromArray($this->row(2))->startDate);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'title', 'projectState', 'isArchived', 'isDraft', 'creationDate', 'creatorId', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Project::fromArray($row);
    }

    public function testOptionalKeysMayBeMissing(): void
    {
        $project = Project::fromArray([
            'id' => 'p', 'title' => 't', 'projectState' => 'planned', 'isArchived' => false, 'isDraft' => false,
            'creationDate' => '2026-01-01T00:00:00.000Z', 'creatorId' => 'u', 'mandantId' => 'm',
        ]);

        self::assertNull($project->description);
        self::assertNull($project->plannedEffort);
        self::assertNull($project->number);
        self::assertSame([], $project->customFields);
    }
}
