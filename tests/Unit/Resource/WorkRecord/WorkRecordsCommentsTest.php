<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\WorkRecord;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\WorkRecord\Input\NewWorkRecordComment;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecordComment;
use Nxi\Factro\Resource\WorkRecord\WorkRecords;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(WorkRecords::class)]
final class WorkRecordsCommentsTest extends TestCase
{
    public function testCommentsListsFixture(): void
    {
        $client = MockFactro::client(['GET /work-records/w1/comments' => static function (string $m, string $u): MockResponse {
            self::assertSame(MockFactro::BASE_URL.'/work-records/w1/comments', $u);

            return JsonMockResponse::fromFile(Fixtures::path('work-record-comments'));
        }]);

        $comments = $client->workRecords()->comments('w1');

        self::assertCount(2, $comments);
        self::assertContainsOnlyInstancesOf(WorkRecordComment::class, $comments);
    }

    public function testCommentFetchesSingleComment(): void
    {
        $first = Fixtures::json('work-record-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'GET /work-records/w1/comments/c1' => static function (string $m, string $u) use ($first): MockResponse {
                self::assertSame(MockFactro::BASE_URL.'/work-records/w1/comments/c1', $u);

                return new JsonMockResponse($first);
            },
            'GET /work-records/w1/comments/missing' => new MockResponse('not found', ['http_code' => 404]),
        ]);

        self::assertSame($first['id'], $client->workRecords()->comment('w1', 'c1')->id);

        $this->expectException(NotFoundException::class);
        $client->workRecords()->comment('w1', 'missing');
    }

    public function testAddCommentPostsPayload(): void
    {
        $first = Fixtures::json('work-record-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client(['POST /work-records/w1/comments' => static function (string $m, string $u, array $o) use ($first): MockResponse {
            self::assertSame('POST', $m);
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"text":"<p>Hi</p>","parentCommentId":"c0"}', $o['body']);

            return new JsonMockResponse($first);
        }]);

        $added = $client->workRecords()->addComment('w1', new NewWorkRecordComment('<p>Hi</p>', 'c0'));

        self::assertSame($first['id'], $added->id);
    }
}
