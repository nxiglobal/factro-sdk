<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Resource\Task\Input\ChecklistEntryChanges;
use Nxi\Factro\Resource\Task\Input\NewChecklistEntry;
use Nxi\Factro\Resource\Task\Output\ChecklistEntry;
use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Tasks::class)]
final class TasksChecklistTest extends TestCase
{
    public function testChecklistHydratesFixture(): void
    {
        $client = MockFactro::client(['GET /tasks/t1/checklist' => JsonMockResponse::fromFile(Fixtures::path('checklist'))]);

        $entries = $client->tasks()->checklist('t1');

        self::assertCount(3, $entries);
        self::assertContainsOnlyInstancesOf(ChecklistEntry::class, $entries);
        self::assertSame('Title 40', $entries[0]->title);
    }

    public function testAddChecklistEntryPostsTitleAndChecked(): void
    {
        $first = Fixtures::json('checklist')[0];
        self::assertIsArray($first);
        $client = MockFactro::client(['POST /tasks/t1/checklist' => static function (string $m, string $u, array $o) use ($first): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"title":"Title 40","checked":false}', $o['body']);

            return new JsonMockResponse($first);
        }]);

        $entry = $client->tasks()->addChecklistEntry('t1', new NewChecklistEntry('Title 40'));

        self::assertSame($first['id'], $entry->id);
    }

    public function testUpdateChecklistEntrySendsOnlyChangedFields(): void
    {
        $first = Fixtures::json('checklist')[0];
        self::assertIsArray($first);
        $client = MockFactro::client(['PUT /tasks/t1/checklist/c1' => static function (string $m, string $u, array $o) use ($first): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"checked":true,"endDate":"2026-09-14T22:00:00.000Z"}', $o['body']);

            return new JsonMockResponse($first);
        }]);

        $entry = $client->tasks()->updateChecklistEntry('t1', 'c1', new ChecklistEntryChanges(checked: true, endDate: CalendarDate::fromYmd('2026-09-15')));

        self::assertInstanceOf(ChecklistEntry::class, $entry);
    }

    public function testUpdateChecklistEntryWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->tasks()->updateChecklistEntry('t1', 'c1', new ChecklistEntryChanges());
    }

    public function testRemoveChecklistEntryDiscardsTheResponse(): void
    {
        $first = Fixtures::json('checklist')[0];
        $client = MockFactro::client(['DELETE /tasks/t1/checklist/c1' => new JsonMockResponse($first)]);

        $client->tasks()->removeChecklistEntry('t1', 'c1');
        $this->addToAssertionCount(1);
    }
}
