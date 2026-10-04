<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Input;

use Nxi\Factro\Resource\Task\Input\ChecklistEntryChanges;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChecklistEntryChanges::class)]
final class ChecklistEntryChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSentAndEndDateIsACalendarDay(): void
    {
        $changes = new ChecklistEntryChanges(title: 'New', endDate: CalendarDate::fromYmd('2026-09-15'), assigneeId: 'u1');

        self::assertSame([
            'title' => 'New',
            'endDate' => '2026-09-14T22:00:00.000Z',
            'assigneeId' => 'u1',
        ], $changes->toPayload(new \DateTimeZone('Europe/Berlin')));
    }

    public function testFalseAndZeroAreSentAndIsEmptyWorks(): void
    {
        self::assertSame(['checked' => false], new ChecklistEntryChanges(checked: false)->toPayload(new \DateTimeZone('UTC')));
        self::assertSame(['position' => 0.0], new ChecklistEntryChanges(position: 0.0)->toPayload(new \DateTimeZone('UTC')));
        self::assertTrue(new ChecklistEntryChanges()->isEmpty());
        self::assertFalse(new ChecklistEntryChanges(checked: false)->isEmpty());
    }
}
