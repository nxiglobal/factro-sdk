<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\WorkRecord;

use Nxi\Factro\Exception\OperationNotPermittedException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\WorkRecord\Input\NewWorkRecord;
use Nxi\Factro\Resource\WorkRecord\Input\WorkRecordChanges;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecord;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use Nxi\Factro\Resource\WorkRecord\WorkRecords;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
use Nxi\Factro\Tests\Support\MockFactro;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(WorkRecords::class)]
final class WorkRecordsTest extends TestCase
{
    public function testListWithoutFilterSendsNoQuery(): void
    {
        $client = MockFactro::client(['GET /work-records' => static function (string $m, string $url): MockResponse {
            self::assertSame(MockFactro::BASE_URL.'/work-records', $url);

            return JsonMockResponse::fromFile(Fixtures::path('work-records-by-project'));
        }]);

        $records = $client->workRecords()->list();
        self::assertCount(3, $records);
        self::assertContainsOnlyInstancesOf(WorkRecord::class, $records);
    }

    public function testListWithDateRange(): void
    {
        $client = MockFactro::client(['GET /work-records' => static function (string $m, string $url): MockResponse {
            self::assertSame(MockFactro::BASE_URL.'/work-records?startDate=2026-01-01&endDate=2026-01-31', $url);

            return new JsonMockResponse([]);
        }]);

        self::assertSame([], $client->workRecords()->list(CalendarDate::fromYmd('2026-01-01'), CalendarDate::fromYmd('2026-01-31')));
    }

    public function testListByProjectWithFromOnly(): void
    {
        $client = MockFactro::client(['GET /work-records/by-project/'.Id::P1 => static function (string $m, string $url): MockResponse {
            self::assertSame(MockFactro::BASE_URL.'/work-records/by-project/'.Id::P1.'?startDate=2026-01-01', $url);

            return JsonMockResponse::fromFile(Fixtures::path('work-records-by-project'));
        }]);

        self::assertCount(3, $client->workRecords()->listByProject(Id::P1, CalendarDate::fromYmd('2026-01-01')));
    }

    public function testGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /work-records/'.Id::W1 => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('work-record')),
            'GET /work-records/'.Id::MISSING => new MockResponse('not found', ['http_code' => 404]),
        ]);

        self::assertInstanceOf(WorkRecord::class, $client->workRecords()->get(Id::W1));
        self::assertInstanceOf(WorkRecord::class, $client->workRecords()->find(Id::W1));
        self::assertNull($client->workRecords()->find(Id::MISSING));
    }

    public function testCreateUpdateDelete(): void
    {
        $client = MockFactro::client([
            'POST /work-records' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"startDate":"2026-09-01","minutesWorked":60,"utcOffset":120,"bookedOnReferenceId":"'.Id::T1.'","bookedOnReferenceType":"task"}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('work-record'));
            },
            'PUT /work-records/'.Id::W1 => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"minutesWorked":45}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('work-record'));
            },
            'DELETE /work-records/'.Id::W1 => new MockResponse('', ['http_code' => 204]),
        ]);

        self::assertInstanceOf(WorkRecord::class, $client->workRecords()->create(new NewWorkRecord(CalendarDate::fromYmd('2026-09-01'), 60, 120, Id::T1, WorkRecordReferenceType::TASK)));
        self::assertInstanceOf(WorkRecord::class, $client->workRecords()->update(Id::W1, new WorkRecordChanges(minutesWorked: 45)));
        $client->workRecords()->delete(Id::W1);
        $this->addToAssertionCount(1);
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->workRecords()->update(Id::W1, new WorkRecordChanges());
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->workRecords()->delete(Id::W1);
    }
}
