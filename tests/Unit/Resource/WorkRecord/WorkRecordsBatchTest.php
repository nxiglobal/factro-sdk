<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\WorkRecord;

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
final class WorkRecordsBatchTest extends TestCase
{
    public function testCreateManyPostsListOfPayloads(): void
    {
        $client = MockFactro::client(['POST /work-records/work-records' => static function (string $m, string $u, array $o): MockResponse {
            self::assertSame('POST', $m);
            self::assertSame(MockFactro::BASE_URL.'/work-records/work-records', $u);
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '[{"startDate":"2026-09-01","minutesWorked":60,"utcOffset":120,"bookedOnReferenceId":"t1","bookedOnReferenceType":"task"},'
                .'{"startDate":"2026-09-02","minutesWorked":30,"utcOffset":120,"bookedOnReferenceId":"p1","bookedOnReferenceType":"project","description":"x"}]',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('work-records-by-project'));
        }]);

        $records = $client->workRecords()->createMany([
            new NewWorkRecord(CalendarDate::fromYmd('2026-09-01'), 60, 120, 't1', WorkRecordReferenceType::TASK),
            new NewWorkRecord(CalendarDate::fromYmd('2026-09-02'), 30, 120, 'p1', WorkRecordReferenceType::PROJECT, description: 'x'),
        ]);

        self::assertCount(3, $records);
        self::assertContainsOnlyInstancesOf(WorkRecord::class, $records);
    }

    public function testCreateManyWithEmptyListIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->workRecords()->createMany([]);
    }

    public function testUpdateManyPutsListWithIds(): void
    {
        $client = MockFactro::client(['PUT /work-records/work-records' => static function (string $m, string $u, array $o): MockResponse {
            self::assertSame('PUT', $m);
            self::assertSame(MockFactro::BASE_URL.'/work-records/work-records', $u);
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"id":"w1","minutesWorked":45},{"id":"w2","isBilled":true,"description":"y"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('work-records-by-project'));
        }]);

        $records = $client->workRecords()->updateMany([
            'w1' => new WorkRecordChanges(minutesWorked: 45),
            'w2' => new WorkRecordChanges(description: 'y', isBilled: true),
        ]);

        self::assertCount(3, $records);
        self::assertContainsOnlyInstancesOf(WorkRecord::class, $records);
    }

    public function testUpdateManyWithEmptyMapIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->workRecords()->updateMany([]);
    }

    public function testUpdateManyWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->workRecords()->updateMany(['w1' => new WorkRecordChanges()]);
    }
}
