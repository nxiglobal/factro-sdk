<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\WorkRecord;

use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\WorkRecord\Input\NewWorkRecord;
use Nxi\Factro\Resource\WorkRecord\Input\WorkRecordChanges;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecord;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use Nxi\Factro\Resource\WorkRecord\WorkRecords;
use Nxi\Factro\Testing\Fixtures;
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
        $client = MockFactro::client(['GET /work-records/by-project/p1' => static function (string $m, string $url): MockResponse {
            self::assertSame(MockFactro::BASE_URL.'/work-records/by-project/p1?startDate=2026-01-01', $url);

            return JsonMockResponse::fromFile(Fixtures::path('work-records-by-project'));
        }]);

        self::assertCount(3, $client->workRecords()->listByProject('p1', CalendarDate::fromYmd('2026-01-01')));
    }

    public function testGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /work-records/w1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('work-record')),
            'GET /work-records/missing' => new MockResponse('not found', ['http_code' => 404]),
        ]);

        self::assertInstanceOf(WorkRecord::class, $client->workRecords()->get('w1'));
        self::assertInstanceOf(WorkRecord::class, $client->workRecords()->find('w1'));
        self::assertNull($client->workRecords()->find('missing'));
    }

    public function testCreateUpdateDelete(): void
    {
        $client = MockFactro::client([
            'POST /work-records' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"startDate":"2026-09-01","minutesWorked":60,"utcOffset":120,"bookedOnReferenceId":"t1","bookedOnReferenceType":"task"}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('work-record'));
            },
            'PUT /work-records/w1' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"minutesWorked":45}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('work-record'));
            },
            'DELETE /work-records/w1' => new MockResponse('', ['http_code' => 204]),
        ]);

        self::assertInstanceOf(WorkRecord::class, $client->workRecords()->create(new NewWorkRecord(CalendarDate::fromYmd('2026-09-01'), 60, 120, 't1', WorkRecordReferenceType::TASK)));
        self::assertInstanceOf(WorkRecord::class, $client->workRecords()->update('w1', new WorkRecordChanges(minutesWorked: 45)));
        $client->workRecords()->delete('w1');
        $this->addToAssertionCount(1);
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->workRecords()->update('w1', new WorkRecordChanges());
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->workRecords()->delete('w1');
    }
}
