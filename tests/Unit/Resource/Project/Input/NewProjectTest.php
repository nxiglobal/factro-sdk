<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project\Input;

use Nxi\Factro\Resource\Project\Input\NewProject;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewProject::class)]
final class NewProjectTest extends TestCase
{
    public function testTitleIsTheOnlyRequiredField(): void
    {
        self::assertSame(['title' => 'P'], new NewProject('P')->toPayload(new \DateTimeZone('UTC')));
    }

    public function testCalendarDaysAreSentAsMidnightOfTheTimezone(): void
    {
        $project = new NewProject('P', projectState: ProjectState::PLANNED, plannedStartDate: CalendarDate::fromYmd('2026-10-01'), plannedEndDate: CalendarDate::fromYmd('2026-12-31'));

        self::assertSame([
            'title' => 'P',
            'projectState' => 'planned',
            'plannedStartDate' => '2026-09-30T22:00:00.000Z',
            'plannedEndDate' => '2026-12-30T23:00:00.000Z',
        ], $project->toPayload(new \DateTimeZone('Europe/Berlin')));
    }

    public function testAllOptionalFieldsIncludingFalseAndEmptyValues(): void
    {
        $project = new NewProject(
            title: 'P', description: '<p>d</p>', colorScheme: 'blue', officerId: 'o', customFields: [],
            isArchived: false, isDraft: true, priority: ProjectPriority::PRIORITY_50, projectState: ProjectState::IN_PROCESS,
        );

        self::assertSame([
            'title' => 'P',
            'description' => '<p>d</p>',
            'colorScheme' => 'blue',
            'officerId' => 'o',
            'customFields' => [],
            'isArchived' => false,
            'isDraft' => true,
            'priority' => 50,
            'projectState' => 'inProcess',
        ], $project->toPayload(new \DateTimeZone('UTC')));
    }
}
