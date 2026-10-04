<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project;

use Nxi\Factro\Resource\Project\Output\ProjectTag;
use Nxi\Factro\Resource\Project\Projects;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Projects::class)]
final class ProjectsTagsTest extends TestCase
{
    public function testProjectTagsAndTagsOfAProject(): void
    {
        $client = MockFactro::client([
            'GET /projects/tags' => JsonMockResponse::fromFile(Fixtures::path('project-tags')),
            'GET /projects/p1/tags' => JsonMockResponse::fromFile(Fixtures::path('project-tags')),
        ]);

        $all = $client->projects()->projectTags();
        self::assertCount(3, $all);
        self::assertContainsOnlyInstancesOf(ProjectTag::class, $all);

        $assigned = $client->projects()->tags('p1');
        self::assertCount(3, $assigned);
        self::assertContainsOnlyInstancesOf(ProjectTag::class, $assigned);
    }

    public function testCreateAndDeleteProjectTag(): void
    {
        $first = Fixtures::json('project-tags')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'POST /projects/tags' => static function (string $m, string $u, array $o) use ($first): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"name":"Tag 1"}', $o['body']);

                return new JsonMockResponse($first);
            },
            'DELETE /projects/tags/tag%2F1' => new JsonMockResponse($first),
        ]);

        $tag = $client->projects()->createProjectTag('Tag 1');
        self::assertSame($first['id'], $tag->id);

        $client->projects()->deleteProjectTag('tag/1');
        $this->addToAssertionCount(1);
    }

    public function testAddAndRemoveTag(): void
    {
        $client = MockFactro::client([
            'PUT /projects/p1/tags' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"tagId":"tag1"}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /projects/p1/tags/tag1' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->projects()->addTag('p1', 'tag1');
        $client->projects()->removeTag('p1', 'tag1');
        $this->addToAssertionCount(2);
    }
}
