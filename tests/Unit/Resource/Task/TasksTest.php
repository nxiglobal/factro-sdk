<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Exception\AuthenticationException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Task\Input\NewTask;
use Nxi\Factro\Resource\Task\Input\TaskChanges;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Resource\Task\TaskState;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Tasks::class)]
final class TasksTest extends TestCase
{
    public function testListByProjectUsesByProjectEndpoint(): void
    {
        $client = MockFactro::client(['GET /tasks/by-project/p1' => JsonMockResponse::fromFile(Fixtures::path('tasks-by-project'))]);

        $tasks = $client->tasks()->listByProject('p1');

        self::assertCount(5, $tasks);
        self::assertContainsOnlyInstancesOf(Task::class, $tasks);
    }

    public function testGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /tasks/t1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('task')),
            'GET /tasks/missing' => new MockResponse(Fixtures::raw('errors/404-task.txt'), ['http_code' => 404]),
        ]);

        self::assertInstanceOf(Task::class, $client->tasks()->get('t1'));
        self::assertInstanceOf(Task::class, $client->tasks()->find('t1'));
        self::assertNull($client->tasks()->find('missing'));
    }

    public function testCreatePostsPayloadAndReturnsTask(): void
    {
        $client = MockFactro::client(['POST /tasks' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            $body = json_decode($o['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($body);
            self::assertSame('T', $body['title']);
            self::assertSame('pkg', $body['targetParentId']);
            self::assertArrayHasKey('creationDate', $body);

            return JsonMockResponse::fromFile(Fixtures::path('task'));
        }]);

        self::assertInstanceOf(Task::class, $client->tasks()->create(new NewTask('T', 'pkg')));
    }

    public function testCreateRespectsSendCreationDateOption(): void
    {
        $client = MockFactro::client(['POST /tasks' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertStringNotContainsString('creationDate', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('task'));
        }], new FactroOptions(baseUrl: MockFactro::BASE_URL, maxRetries: 0, sendCreationDate: false));

        $client->tasks()->create(new NewTask('T', 'pkg'));
    }

    public function testSetStateUsesStateEndpointAndAccepts204(): void
    {
        $client = MockFactro::client(['PUT /tasks/t1/state' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"state":"closed"}', $o['body']);

            return new MockResponse('', ['http_code' => 204]);
        }]);

        $client->tasks()->setState('t1', TaskState::CLOSED);
        $this->addToAssertionCount(1);
    }

    public function testSetStateWithPausedUntil(): void
    {
        $client = MockFactro::client(['PUT /tasks/t1/state' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"state":"pushedBack","pausedUntil":"2026-10-01T00:00:00.000Z"}', $o['body']);

            return new MockResponse('', ['http_code' => 204]);
        }]);

        $client->tasks()->setState('t1', TaskState::PUSHED_BACK, new \DateTimeImmutable('2026-10-01T00:00:00Z'));
        $this->addToAssertionCount(1);
    }

    public function testUpdateSendsOnlyChangedFields(): void
    {
        $client = MockFactro::client(['PUT /tasks/t1' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"description":"<p>x</p>"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('task'));
        }]);

        self::assertInstanceOf(Task::class, $client->tasks()->update('t1', new TaskChanges(description: '<p>x</p>')));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->tasks()->update('t1', new TaskChanges());
    }

    public function testClosedTaskYieldsAuthenticationExceptionWithFactroMessage(): void
    {
        $client = MockFactro::client(['PUT /tasks/t1' => static fn (): MockResponse => MockResponse::fromFile(Fixtures::path('errors/403-task-closed'), ['http_code' => 403])]);

        try {
            $client->tasks()->update('t1', new TaskChanges(title: 'x'));
            self::fail('expected AuthenticationException');
        } catch (AuthenticationException $e) {
            self::assertSame('Task is closed', $e->getMessage());
            self::assertSame(403, $e->statusCode);
        }
    }

    public function testDeleteAccepts204(): void
    {
        $client = MockFactro::client(['DELETE /tasks/t1' => new MockResponse('', ['http_code' => 204])]);

        $client->tasks()->delete('t1');
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->tasks()->delete('t1');
    }
}
