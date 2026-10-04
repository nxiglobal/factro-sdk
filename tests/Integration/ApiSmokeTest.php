<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Integration;

use Nxi\Factro\FactroClientFactory;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Resource\Package\Output\Package;
use Nxi\Factro\Resource\Task\Input\NewTask;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Resource\Task\TaskState;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Walks the core flow "find project, read packages, read tasks, create task, close task" through the whole chain.
 */
#[CoversNothing]
final class ApiSmokeTest extends TestCase
{
    public function testCoreFlowAgainstFixtures(): void
    {
        $projectRow = Fixtures::json('projects')[0];
        self::assertIsArray($projectRow);
        $projectId = $projectRow['id'];
        self::assertIsString($projectId);
        $taskRow = Fixtures::json('task');
        $taskId = $taskRow['id'];
        self::assertIsString($taskId);

        $mock = MockFactro::http([
            'GET /projects' => JsonMockResponse::fromFile(Fixtures::path('projects')),
            "GET /projects/{$projectId}/packages" => JsonMockResponse::fromFile(Fixtures::path('packages')),
            "GET /tasks/by-project/{$projectId}" => JsonMockResponse::fromFile(Fixtures::path('tasks-by-project')),
            'POST /tasks' => JsonMockResponse::fromFile(Fixtures::path('task')),
            "PUT /tasks/{$taskId}/state" => new MockResponse('', ['http_code' => 204]),
        ]);
        $client = FactroClientFactory::create('t', new FactroOptions(baseUrl: MockFactro::BASE_URL, maxRetries: 0), $mock);

        $project = null;
        foreach ($client->projects()->list() as $candidate) {
            if ($candidate->id === $projectId) {
                $project = $candidate;
            }
        }
        self::assertNotNull($project);

        $packages = $client->packages()->listByProject($project->id);
        self::assertContainsOnlyInstancesOf(Package::class, $packages);
        $root = array_values(array_filter($packages, static fn (Package $p): bool => null === $p->parentPackageId));
        self::assertCount(1, $root);

        $tasks = $client->tasks()->listByProject($project->id);
        self::assertContainsOnlyInstancesOf(Task::class, $tasks);
        self::assertNotEmpty($tasks);

        $created = $client->tasks()->create(new NewTask('Smoke', $root[0]->id));
        self::assertSame($taskId, $created->id);

        $client->tasks()->setState($created->id, TaskState::CLOSED);

        self::assertSame(5, $mock->getRequestsCount());
    }
}
