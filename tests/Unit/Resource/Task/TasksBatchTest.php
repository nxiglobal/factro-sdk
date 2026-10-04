<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Resource\Task\Input\NewTask;
use Nxi\Factro\Resource\Task\Input\TaskChanges;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Resource\Task\Urgency;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Tasks::class)]
final class TasksBatchTest extends TestCase
{
    public function testCreateManyPostsAListOfPayloads(): void
    {
        $task = Fixtures::json('task');
        $client = MockFactro::client(['POST /tasks/tasks' => static function (string $m, string $u, array $o) use ($task): MockResponse {
            self::assertIsString($o['body']);
            $body = json_decode($o['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($body);
            self::assertSame([0, 1], array_keys($body));
            self::assertIsArray($body[0]);
            self::assertIsArray($body[1]);
            self::assertSame('A', $body[0]['title']);
            self::assertSame('pkg', $body[0]['targetParentId']);
            self::assertArrayHasKey('creationDate', $body[0]);
            self::assertSame('B', $body[1]['title']);

            return new JsonMockResponse([$task, $task]);
        }]);

        $tasks = $client->tasks()->createMany([new NewTask('A', 'pkg'), new NewTask('B', 'pkg')]);

        self::assertCount(2, $tasks);
        self::assertContainsOnlyInstancesOf(Task::class, $tasks);
    }

    public function testCreateManyWithEmptyListIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->tasks()->createMany([]);
    }

    public function testUpdateManyPutsAListWithIds(): void
    {
        $task = Fixtures::json('task');
        $client = MockFactro::client(['PUT /tasks/tasks' => static function (string $m, string $u, array $o) use ($task): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"id":"t1","title":"New"},{"id":"t2","description":"<p>x</p>","urgency":"due"}]', $o['body']);

            return new JsonMockResponse([$task, $task]);
        }]);

        $tasks = $client->tasks()->updateMany([
            't1' => new TaskChanges(title: 'New'),
            't2' => new TaskChanges(description: '<p>x</p>', urgency: Urgency::DUE),
        ]);

        self::assertCount(2, $tasks);
        self::assertContainsOnlyInstancesOf(Task::class, $tasks);
    }

    public function testUpdateManyWithEmptyMapIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->tasks()->updateMany([]);
    }

    public function testUpdateManyWithAnEmptyChangeSetIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('t2');
        MockFactro::client([])->tasks()->updateMany(['t1' => new TaskChanges(title: 'x'), 't2' => new TaskChanges()]);
    }
}
