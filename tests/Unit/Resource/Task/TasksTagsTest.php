<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Resource\Task\Output\TaskTag;
use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Tasks::class)]
final class TasksTagsTest extends TestCase
{
    public function testTaskTagsListsTheTenantTags(): void
    {
        $client = MockFactro::client(['GET /tasks/tags' => JsonMockResponse::fromFile(Fixtures::path('task-tags'))]);

        $tags = $client->tasks()->taskTags();

        self::assertCount(3, $tags);
        self::assertContainsOnlyInstancesOf(TaskTag::class, $tags);
        self::assertSame('Tag 10', $tags[0]->name);
    }

    public function testCreateAndDeleteTaskTag(): void
    {
        $first = Fixtures::json('task-tags')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'POST /tasks/tags' => static function (string $m, string $u, array $o) use ($first): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"name":"Tag 10"}', $o['body']);

                return new JsonMockResponse($first);
            },
            'DELETE /tasks/tags/tag1' => new JsonMockResponse($first),
        ]);

        $tag = $client->tasks()->createTaskTag('Tag 10');
        self::assertSame($first['id'], $tag->id);

        $client->tasks()->deleteTaskTag('tag1');
        $this->addToAssertionCount(1);
    }

    public function testTagsOfATaskAndAddAndRemove(): void
    {
        $client = MockFactro::client([
            'GET /tasks/t1/tags' => JsonMockResponse::fromFile(Fixtures::path('task-tags')),
            'PUT /tasks/t1/tags' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"tagId":"tag1"}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /tasks/t1/tags/tag1' => new MockResponse('', ['http_code' => 204]),
        ]);

        $tags = $client->tasks()->tags('t1');
        self::assertCount(3, $tags);
        self::assertContainsOnlyInstancesOf(TaskTag::class, $tags);

        $client->tasks()->addTag('t1', 'tag1');
        $client->tasks()->removeTag('t1', 'tag1');
        $this->addToAssertionCount(2);
    }
}
