<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project\Input;

use Nxi\Factro\Resource\Project\Input\ProjectChanges;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectChanges::class)]
final class ProjectChangesTest extends TestCase
{
    public function testPayloadContainsOnlySetFields(): void
    {
        $changes = new ProjectChanges(title: 'New', projectState: ProjectState::CLOSED, plannedEndDate: CalendarDate::fromYmd('2026-09-01'));

        self::assertSame(['title' => 'New', 'projectState' => 'closed', 'plannedEndDate' => '2026-08-31T22:00:00.000Z'], $changes->toPayload(new \DateTimeZone('Europe/Berlin')));
        self::assertFalse($changes->isEmpty());
        self::assertTrue(new ProjectChanges()->isEmpty());
    }

    public function testFalseAndEmptyValuesAreSent(): void
    {
        $changes = new ProjectChanges(customFields: [], isArchived: false, isDraft: true, priority: ProjectPriority::PRIORITY_30, plannedStartDate: CalendarDate::fromYmd('2026-02-01'));

        self::assertSame([
            'customFields' => [],
            'isArchived' => false,
            'isDraft' => true,
            'priority' => 30,
            'plannedStartDate' => '2026-01-31T23:00:00.000Z',
        ], $changes->toPayload(new \DateTimeZone('Europe/Berlin')));
        self::assertFalse($changes->isEmpty());
    }
}
