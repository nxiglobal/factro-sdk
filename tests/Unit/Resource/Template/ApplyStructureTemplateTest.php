<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Template;

use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Resource\Template\Input\AdditionalAccessRights;
use Nxi\Factro\Resource\Template\Input\ApplyStructureTemplate;
use Nxi\Factro\Resource\Template\Input\ProjectPropertyOverwrites;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApplyStructureTemplate::class)]
#[CoversClass(AdditionalAccessRights::class)]
#[CoversClass(ProjectPropertyOverwrites::class)]
final class ApplyStructureTemplateTest extends TestCase
{
    public function testEmptyInputYieldsEmptyPayload(): void
    {
        self::assertSame([], new ApplyStructureTemplate()->toPayload(new \DateTimeZone('Europe/Berlin')));
    }

    public function testNestedPayloadsAreEmbedded(): void
    {
        $payload = new ApplyStructureTemplate(
            additionalAccessRights: new AdditionalAccessRights(directReadEmployeeIds: ['e1']),
            projectPropertyOverwrites: new ProjectPropertyOverwrites(isDraft: true),
        )->toPayload(new \DateTimeZone('Europe/Berlin'));

        self::assertSame([
            'additionalAccessRights' => [
                'directWriteTeamIds' => [],
                'directReadTeamIds' => [],
                'directWriteEmployeeIds' => [],
                'directReadEmployeeIds' => ['e1'],
            ],
            'projectPropertyOverwrites' => ['isDraft' => true],
        ], $payload);
    }

    public function testAdditionalAccessRightsAlwaysSendAllFourLists(): void
    {
        self::assertSame([
            'directWriteTeamIds' => [],
            'directReadTeamIds' => [],
            'directWriteEmployeeIds' => [],
            'directReadEmployeeIds' => [],
        ], new AdditionalAccessRights()->toPayload());
    }

    public function testProjectPropertyOverwritesConvertStartDateToTimezoneMidnight(): void
    {
        $payload = new ProjectPropertyOverwrites(
            title: 'T',
            description: 'D',
            colorScheme: 'blue',
            officerId: 'o1',
            isArchived: false,
            isDraft: true,
            priority: ProjectPriority::PRIORITY_99,
            projectState: ProjectState::IN_PROCESS,
            startDate: CalendarDate::fromYmd('2026-01-15'),
        )->toPayload(new \DateTimeZone('Europe/Berlin'));

        self::assertSame([
            'title' => 'T',
            'description' => 'D',
            'colorScheme' => 'blue',
            'officerId' => 'o1',
            'isArchived' => false,
            'isDraft' => true,
            'priority' => 99,
            'projectState' => 'inProcess',
            'startDate' => '2026-01-14T23:00:00.000Z',
        ], $payload);
    }

    public function testProjectPropertyOverwritesWithoutFieldsAreEmpty(): void
    {
        self::assertSame([], new ProjectPropertyOverwrites()->toPayload(new \DateTimeZone('UTC')));
    }
}
