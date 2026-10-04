<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project;

use Nxi\Factro\Resource\Project\Input\NewProjectComment;
use Nxi\Factro\Resource\Project\Output\ProjectComment;
use Nxi\Factro\Resource\Project\Projects;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Projects::class)]
final class ProjectsCommentsTest extends TestCase
{
    public function testCommentsAndComment(): void
    {
        $first = Fixtures::json('project-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'GET /projects/p1/comments' => JsonMockResponse::fromFile(Fixtures::path('project-comments')),
            'GET /projects/p1/comments/c%2F1' => new JsonMockResponse($first),
        ]);

        $comments = $client->projects()->comments('p1');
        self::assertCount(2, $comments);
        self::assertContainsOnlyInstancesOf(ProjectComment::class, $comments);

        $comment = $client->projects()->comment('p1', 'c/1');
        self::assertSame($first['id'], $comment->id);
    }

    public function testAddCommentPostsPayload(): void
    {
        $first = Fixtures::json('project-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client(['POST /projects/p1/comments' => static function (string $m, string $u, array $o) use ($first): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"text":"<p>Hi</p>","parentCommentId":"c0"}', $o['body']);

            return new JsonMockResponse($first);
        }]);

        $added = $client->projects()->addComment('p1', new NewProjectComment('<p>Hi</p>', 'c0'));

        self::assertSame($first['id'], $added->id);
    }

    public function testDeleteCommentDiscardsTheResponse(): void
    {
        $first = Fixtures::json('project-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client(['DELETE /projects/p1/comments/c1' => new JsonMockResponse($first)]);

        $client->projects()->deleteComment('p1', 'c1');
        $this->addToAssertionCount(1);
    }
}
