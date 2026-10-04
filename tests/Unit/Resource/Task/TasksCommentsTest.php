<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Resource\Task\Input\NewTaskComment;
use Nxi\Factro\Resource\Task\Input\TaskConnectionInput;
use Nxi\Factro\Resource\Task\Output\TaskComment;
use Nxi\Factro\Resource\Task\Output\TaskConnection;
use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Tasks::class)]
final class TasksCommentsTest extends TestCase
{
    public function testCommentsAndAddComment(): void
    {
        $first = Fixtures::json('task-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'GET /tasks/t1/comments' => JsonMockResponse::fromFile(Fixtures::path('task-comments')),
            'POST /tasks/t1/comments' => static function (string $m, string $u, array $o) use ($first): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"text":"<p>Hi</p>"}', $o['body']);

                return new JsonMockResponse($first);
            },
        ]);

        $comments = $client->tasks()->comments('t1');
        self::assertCount(2, $comments);
        self::assertContainsOnlyInstancesOf(TaskComment::class, $comments);

        $added = $client->tasks()->addComment('t1', new NewTaskComment('<p>Hi</p>'));
        self::assertSame($first['id'], $added->id);
    }

    public function testCommentByIdAndDeleteComment(): void
    {
        $first = Fixtures::json('task-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'GET /tasks/t1/comments/c1' => new JsonMockResponse($first),
            'DELETE /tasks/t1/comments/c1' => new JsonMockResponse($first),
        ]);

        $comment = $client->tasks()->comment('t1', 'c1');
        self::assertSame($first['id'], $comment->id);

        $client->tasks()->deleteComment('t1', 'c1');
        $this->addToAssertionCount(1);
    }

    public function testConnectionsAndAddConnection(): void
    {
        $client = MockFactro::client([
            'GET /tasks/t1/task_connections' => JsonMockResponse::fromFile(Fixtures::path('task-connections')),
            'GET /tasks/t2/task_connections' => JsonMockResponse::fromFile(Fixtures::path('task-connections-synthetic')),
            'PUT /tasks/t1/task_connections' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"connectedTaskId":"t2","isPredecessor":true,"isSuccessor":false}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
        ]);

        self::assertSame([], $client->tasks()->connections('t1'));
        $connections = $client->tasks()->connections('t2');
        self::assertCount(1, $connections);
        self::assertContainsOnlyInstancesOf(TaskConnection::class, $connections);

        $client->tasks()->addConnection('t1', new TaskConnectionInput('t2', isPredecessor: true));
        $this->addToAssertionCount(1);
    }
}
