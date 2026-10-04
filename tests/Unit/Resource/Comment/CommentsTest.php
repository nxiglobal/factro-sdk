<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Comment;

use Nxi\Factro\Resource\Comment\CommentReferenceType;
use Nxi\Factro\Resource\Comment\Comments;
use Nxi\Factro\Resource\Comment\Output\Comment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Comments::class)]
#[CoversClass(CommentReferenceType::class)]
final class CommentsTest extends TestCase
{
    public function testGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /comments/c1' => static function (string $m, string $u): MockResponse {
                self::assertSame('GET', $m);
                self::assertSame(MockFactro::BASE_URL.'/comments/c1', $u);

                return JsonMockResponse::fromFile(Fixtures::path('comment'));
            },
            'GET /comments/missing' => new MockResponse('not found', ['http_code' => 404]),
        ]);

        $comment = $client->comments()->get('c1');
        self::assertInstanceOf(Comment::class, $comment);
        self::assertSame(CommentReferenceType::TASK, $comment->referenceType);
        self::assertInstanceOf(Comment::class, $client->comments()->find('c1'));
        self::assertNull($client->comments()->find('missing'));
    }

    public function testReferenceTypeValuesMatchApi(): void
    {
        self::assertSame(
            ['task', 'note', 'package', 'project', 'workRecord'],
            array_map(static fn (CommentReferenceType $t): string => $t->value, CommentReferenceType::cases()),
        );
        self::assertSame(CommentReferenceType::WORK_RECORD, CommentReferenceType::from('workRecord'));
    }
}
