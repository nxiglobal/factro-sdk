<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Input;

use Nxi\Factro\Resource\Task\Input\NewTask;
use Nxi\Factro\Resource\Task\TaskPriority;
use Nxi\Factro\Resource\Task\Urgency;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

#[CoversClass(NewTask::class)]
final class NewTaskTest extends TestCase
{
    public function testPayloadWithCreationDateFromClock(): void
    {
        $clock = new MockClock('2026-09-11 10:00:00', 'UTC');
        $task = new NewTask(title: 'T', targetParentId: 'pkg', endDate: CalendarDate::fromYmd('2026-09-01'), taskPriority: TaskPriority::PRIORITY_50);

        self::assertSame([
            'title' => 'T',
            'targetParentId' => 'pkg',
            'isMilestone' => false,
            'creationDate' => '2026-09-11T10:00:00.000Z',
            'endDate' => '2026-08-31T22:00:00.000Z',
            'taskPriority' => 50,
        ], $task->toPayload(new \DateTimeZone('Europe/Berlin'), $clock, sendCreationDate: true));
    }

    public function testPayloadWithoutCreationDate(): void
    {
        $payload = new NewTask('T', 'pkg')->toPayload(new \DateTimeZone('UTC'), new MockClock(), sendCreationDate: false);

        self::assertSame(['title' => 'T', 'targetParentId' => 'pkg', 'isMilestone' => false], $payload);
    }

    public function testAllOptionalFields(): void
    {
        $task = new NewTask(
            title: 'T', targetParentId: 'pkg', description: '<p>d</p>', startDate: CalendarDate::fromYmd('2026-09-01'),
            officerId: 'o', executorId: 'e', isMilestone: true, plannedEffort: 8.0, remainingEffort: 2.5,
            colorScheme: 'red', customFields: ['subproject_number' => '4711'],
        );

        self::assertSame([
            'title' => 'T',
            'targetParentId' => 'pkg',
            'isMilestone' => true,
            'description' => '<p>d</p>',
            'startDate' => '2026-09-01T00:00:00.000Z',
            'officerId' => 'o',
            'executorId' => 'e',
            'plannedEffort' => 8.0,
            'remainingEffort' => 2.5,
            'colorScheme' => 'red',
            'customFields' => ['subproject_number' => '4711'],
        ], $task->toPayload(new \DateTimeZone('UTC'), new MockClock(), sendCreationDate: false));
    }

    public function testUrgencyIsAppendedAndSentAsBackingValue(): void
    {
        $payload = new NewTask('T', 'pkg', urgency: Urgency::DUE)->toPayload(new \DateTimeZone('UTC'), new MockClock(), sendCreationDate: false);

        self::assertSame(['title' => 'T', 'targetParentId' => 'pkg', 'isMilestone' => false, 'urgency' => 'due'], $payload);
    }

    public function testAClearedExecutorIsSentAsNull(): void
    {
        $payload = new NewTask('T', 'pkg', clear: ['executorId'])->toPayload(new \DateTimeZone('UTC'), new MockClock(), sendCreationDate: false);

        self::assertSame(['title' => 'T', 'targetParentId' => 'pkg', 'isMilestone' => false, 'executorId' => null], $payload);
    }

    public function testARequiredFieldCannotBeCleared(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new NewTask('T', 'pkg', clear: ['title'])->toPayload(new \DateTimeZone('UTC'), new MockClock(), sendCreationDate: false);
    }
}
