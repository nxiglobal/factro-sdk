<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Resource\Task\Input\NewTaskComment;
use Nxi\Factro\Resource\Task\Input\TaskConnectionInput;
use Nxi\Factro\Resource\Task\Output\TaskComment;
use Nxi\Factro\Resource\Task\Output\TaskConnection;
use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
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
            'GET /tasks/'.Id::T1.'/comments' => JsonMockResponse::fromFile(Fixtures::path('task-comments')),
            'POST /tasks/'.Id::T1.'/comments' => static function (string $m, string $u, array $o) use ($first): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"text":"<p>Hi</p>"}', $o['body']);

                return new JsonMockResponse($first);
            },
        ]);

        $comments = $client->tasks()->comments(Id::T1);
        self::assertCount(3, $comments);
        self::assertContainsOnlyInstancesOf(TaskComment::class, $comments);

        $added = $client->tasks()->addComment(Id::T1, new NewTaskComment('<p>Hi</p>'));
        self::assertSame($first['id'], $added->id);
    }

    public function testCommentByIdAndDeleteComment(): void
    {
        $first = Fixtures::json('task-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'GET /tasks/'.Id::T1.'/comments/'.Id::C1 => new JsonMockResponse($first),
            'DELETE /tasks/'.Id::T1.'/comments/'.Id::C1 => new JsonMockResponse($first),
        ]);

        $comment = $client->tasks()->comment(Id::T1, Id::C1);
        self::assertSame($first['id'], $comment->id);

        $client->tasks()->deleteComment(Id::T1, Id::C1);
        $this->addToAssertionCount(1);
    }

    public function testConnectionsAndAddConnection(): void
    {
        $client = MockFactro::client([
            'GET /tasks/'.Id::T1.'/task_connections' => JsonMockResponse::fromFile(Fixtures::path('task-connections')),
            'GET /tasks/'.Id::T2.'/task_connections' => JsonMockResponse::fromFile(Fixtures::path('task-connections-synthetic')),
            'PUT /tasks/'.Id::T1.'/task_connections' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"connectedTaskId":"'.Id::T2.'","isPredecessor":true,"isSuccessor":false}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
        ]);

        self::assertSame([], $client->tasks()->connections(Id::T1));
        $connections = $client->tasks()->connections(Id::T2);
        self::assertCount(1, $connections);
        self::assertContainsOnlyInstancesOf(TaskConnection::class, $connections);

        $client->tasks()->addConnection(Id::T1, new TaskConnectionInput(Id::T2, isPredecessor: true));
        $this->addToAssertionCount(1);
    }
}
