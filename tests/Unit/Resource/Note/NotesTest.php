<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Note;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\Note\Input\NewNoteComment;
use Nxi\Factro\Resource\Note\Notes;
use Nxi\Factro\Resource\Note\Output\NoteComment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Notes::class)]
final class NotesTest extends TestCase
{
    public function testCommentsUsesSingularNotePath(): void
    {
        $client = MockFactro::client(['GET /note/'.Id::N1.'/comments' => static function (string $m, string $u): MockResponse {
            self::assertSame('GET', $m);
            self::assertSame(MockFactro::BASE_URL.'/note/'.Id::N1.'/comments', $u);

            return JsonMockResponse::fromFile(Fixtures::path('note-comments'));
        }]);

        $comments = $client->notes()->comments(Id::N1);

        self::assertCount(2, $comments);
        self::assertContainsOnlyInstancesOf(NoteComment::class, $comments);
    }

    public function testCommentFetchesSingleComment(): void
    {
        $first = Fixtures::json('note-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'GET /note/'.Id::N1.'/comments/'.Id::C1 => static function (string $m, string $u) use ($first): MockResponse {
                self::assertSame(MockFactro::BASE_URL.'/note/'.Id::N1.'/comments/'.Id::C1, $u);

                return new JsonMockResponse($first);
            },
            'GET /note/'.Id::N1.'/comments/'.Id::MISSING => new MockResponse('not found', ['http_code' => 404]),
        ]);

        self::assertSame($first['id'], $client->notes()->comment(Id::N1, Id::C1)->id);

        $this->expectException(NotFoundException::class);
        $client->notes()->comment(Id::N1, Id::MISSING);
    }

    public function testAddCommentPostsPayload(): void
    {
        $first = Fixtures::json('note-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client(['POST /note/'.Id::N1.'/comments' => static function (string $m, string $u, array $o) use ($first): MockResponse {
            self::assertSame('POST', $m);
            self::assertSame(MockFactro::BASE_URL.'/note/'.Id::N1.'/comments', $u);
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"text":"<p>Hi</p>"}', $o['body']);

            return new JsonMockResponse($first);
        }]);

        $added = $client->notes()->addComment(Id::N1, new NewNoteComment('<p>Hi</p>'));

        self::assertSame($first['id'], $added->id);
    }
}
