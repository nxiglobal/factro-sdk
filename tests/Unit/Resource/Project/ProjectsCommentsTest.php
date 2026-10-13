<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project;

use Nxi\Factro\Resource\Project\Input\NewProjectComment;
use Nxi\Factro\Resource\Project\Output\ProjectComment;
use Nxi\Factro\Resource\Project\Projects;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
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
            'GET /projects/'.Id::P1.'/comments' => JsonMockResponse::fromFile(Fixtures::path('project-comments')),
            'GET /projects/'.Id::P1.'/comments/'.Id::C1 => new JsonMockResponse($first),
        ]);

        $comments = $client->projects()->comments(Id::P1);
        self::assertCount(2, $comments);
        self::assertContainsOnlyInstancesOf(ProjectComment::class, $comments);

        $comment = $client->projects()->comment(Id::P1, Id::C1);
        self::assertSame($first['id'], $comment->id);
    }

    public function testAddCommentPostsPayload(): void
    {
        $first = Fixtures::json('project-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client(['POST /projects/'.Id::P1.'/comments' => static function (string $m, string $u, array $o) use ($first): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"text":"<p>Hi</p>","parentCommentId":"c0"}', $o['body']);

            return new JsonMockResponse($first);
        }]);

        $added = $client->projects()->addComment(Id::P1, new NewProjectComment('<p>Hi</p>', 'c0'));

        self::assertSame($first['id'], $added->id);
    }

    public function testDeleteCommentDiscardsTheResponse(): void
    {
        $first = Fixtures::json('project-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client(['DELETE /projects/'.Id::P1.'/comments/'.Id::C1 => new JsonMockResponse($first)]);

        $client->projects()->deleteComment(Id::P1, Id::C1);
        $this->addToAssertionCount(1);
    }
}
