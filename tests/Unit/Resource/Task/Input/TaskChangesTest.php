<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Input;

use Nxi\Factro\Resource\Task\Input\TaskChanges;
use Nxi\Factro\Resource\Task\TaskPriority;
use Nxi\Factro\Resource\Task\Urgency;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskChanges::class)]
final class TaskChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSent(): void
    {
        $changes = new TaskChanges(description: '<p>x</p>', startDate: CalendarDate::fromYmd('2026-09-01'), endDate: CalendarDate::fromYmd('2026-09-02'), taskPriority: TaskPriority::PRIORITY_20, targetParentId: 'pkg2', companyId: 'c', companyContactId: 'cc');

        self::assertSame([
            'description' => '<p>x</p>',
            'startDate' => '2026-08-31T22:00:00.000Z',
            'endDate' => '2026-09-01T22:00:00.000Z',
            'taskPriority' => 20,
            'targetParentId' => 'pkg2',
            'companyId' => 'c',
            'companyContactId' => 'cc',
        ], $changes->toPayload(new \DateTimeZone('Europe/Berlin')));
    }

    public function testFalseIsSentAndIsEmptyWorks(): void
    {
        self::assertSame(['isMilestone' => false], new TaskChanges(isMilestone: false)->toPayload(new \DateTimeZone('UTC')));
        self::assertSame(['plannedEffort' => 0.0], new TaskChanges(plannedEffort: 0.0)->toPayload(new \DateTimeZone('UTC')));
        self::assertTrue(new TaskChanges()->isEmpty());
        self::assertFalse(new TaskChanges(isMilestone: false)->isEmpty());
        self::assertFalse(new TaskChanges(customFields: [])->isEmpty());
    }

    public function testUrgencyIsAppendedAndSentAsBackingValue(): void
    {
        self::assertSame(['urgency' => 'overdue'], new TaskChanges(urgency: Urgency::OVERDUE)->toPayload(new \DateTimeZone('UTC')));
        self::assertFalse(new TaskChanges(urgency: Urgency::NORMAL)->isEmpty());
    }

    public function testClearedFieldsAreSentAsNull(): void
    {
        $changes = new TaskChanges(title: 'x', clear: ['colorScheme', 'executorId']);

        self::assertSame(['title' => 'x', 'colorScheme' => null, 'executorId' => null], $changes->toPayload(new \DateTimeZone('UTC')));
        self::assertFalse(new TaskChanges(clear: ['colorScheme'])->isEmpty());
    }

    public function testAnUnknownClearedFieldIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TaskChanges(clear: ['colour'])->toPayload(new \DateTimeZone('UTC'));
    }

    public function testAFieldSetAndClearedAtOnceIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TaskChanges(colorScheme: 'red', clear: ['colorScheme'])->toPayload(new \DateTimeZone('UTC'));
    }
}
