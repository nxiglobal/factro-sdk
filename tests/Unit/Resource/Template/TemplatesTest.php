<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Template;

use Nxi\Factro\Resource\AccessRight\AccessRights;
use Nxi\Factro\Resource\Project\Output\Project;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Resource\Template\Input\AdditionalAccessRights;
use Nxi\Factro\Resource\Template\Input\ApplyStructureTemplate;
use Nxi\Factro\Resource\Template\Input\ProjectPropertyOverwrites;
use Nxi\Factro\Resource\Template\Templates;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Templates::class)]
final class TemplatesTest extends TestCase
{
    public function testStructureTemplateAccessRightsUseTheStructurePath(): void
    {
        $client = MockFactro::client([
            'GET /templates/structure/st1/read_rights' => JsonMockResponse::fromFile(Fixtures::path('access-rights')),
            'PUT /templates/structure/st1/team_write_rights' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"teamId":"team1"}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('team-access-right'));
            },
        ]);

        $rights = $client->templates()->structureTemplateAccessRights('st1');

        self::assertInstanceOf(AccessRights::class, $rights);
        self::assertCount(3, $rights->readRights());
        self::assertSame('c4d5e6f7-a8b9-5c0d-9e1f-3a4b5c6d7e8f', $rights->grantTeamWrite('team1')->teamId);
    }

    public function testTaskTemplateAccessRightsUseTheTaskPath(): void
    {
        $client = MockFactro::client([
            'GET /templates/task/tp1/read_rights' => JsonMockResponse::fromFile(Fixtures::path('access-rights')),
            'DELETE /templates/task/tp1/team_read_rights/team1' => new MockResponse('', ['http_code' => 204]),
        ]);

        $rights = $client->templates()->taskTemplateAccessRights('tp1');

        self::assertInstanceOf(AccessRights::class, $rights);
        self::assertCount(3, $rights->readRights());
        $rights->revokeTeamRead('team1');
        $this->addToAssertionCount(1);
    }

    public function testTemplateIdsAreUrlEncodedInAccessRightPaths(): void
    {
        $client = MockFactro::client(['GET /templates/task/a%2Fb/write_rights' => new JsonMockResponse([])]);

        self::assertSame([], $client->templates()->taskTemplateAccessRights('a/b')->writeRights());
    }

    public function testApplyStructureTemplatePostsFullBodyAndReturnsProject(): void
    {
        $client = MockFactro::client(['POST /templates/structure/st1/apply_template' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(json_encode([
                'targetParentId' => 'p1',
                'additionalAccessRights' => [
                    'directWriteTeamIds' => ['team1'],
                    'directReadTeamIds' => [],
                    'directWriteEmployeeIds' => ['e1', 'e2'],
                    'directReadEmployeeIds' => [],
                ],
                'additionalCustomFieldAssignments' => ['cf1'],
                'projectPropertyOverwrites' => [
                    'title' => 'New',
                    'priority' => 50,
                    'projectState' => 'planned',
                    'startDate' => '2026-09-30T22:00:00.000Z',
                ],
                'customFieldsToClone' => ['cf2'],
                'cloneDocuments' => true,
                'clonePeriods' => false,
                'cloneTags' => true,
                'packageCloneOptions' => ['cloneDocuments' => false],
                'taskCloneOptions' => ['cloneTags' => true],
            ], JSON_THROW_ON_ERROR), $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('project'));
        }]);

        $project = $client->templates()->applyStructureTemplate('st1', new ApplyStructureTemplate(
            targetParentId: 'p1',
            additionalAccessRights: new AdditionalAccessRights(directWriteTeamIds: ['team1'], directWriteEmployeeIds: ['e1', 'e2']),
            additionalCustomFieldAssignments: ['cf1'],
            projectPropertyOverwrites: new ProjectPropertyOverwrites(
                title: 'New',
                priority: ProjectPriority::PRIORITY_50,
                projectState: ProjectState::PLANNED,
                startDate: CalendarDate::fromYmd('2026-10-01'),
            ),
            customFieldsToClone: ['cf2'],
            cloneDocuments: true,
            clonePeriods: false,
            cloneTags: true,
            packageCloneOptions: ['cloneDocuments' => false],
            taskCloneOptions: ['cloneTags' => true],
        ));

        self::assertInstanceOf(Project::class, $project);
        self::assertSame('d61385c7-c0ca-5faa-a628-09430d2b096a', $project->id);
    }

    public function testApplyStructureTemplateWithoutOptionsSendsEmptyObject(): void
    {
        $client = MockFactro::client(['POST /templates/structure/st1/apply_template' => static function (string $m, string $u, array $o): MockResponse {
            self::assertSame('{}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('project'));
        }]);

        self::assertInstanceOf(Project::class, $client->templates()->applyStructureTemplate('st1', new ApplyStructureTemplate()));
    }

    public function testApplyTaskTemplatePostsTargetParentIdAndReturnsTask(): void
    {
        $client = MockFactro::client(['POST /templates/task/tp1/apply_template' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"targetParentId":"pkg1"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('task'));
        }]);

        $task = $client->templates()->applyTaskTemplate('tp1', 'pkg1');

        self::assertInstanceOf(Task::class, $task);
        self::assertSame('e53f64f5-6dcc-5990-af50-e315dc575d1a', $task->id);
    }

    public function testApplyTaskTemplateWithoutTargetSendsEmptyObject(): void
    {
        $client = MockFactro::client(['POST /templates/task/tp1/apply_template' => static function (string $m, string $u, array $o): MockResponse {
            self::assertSame('{}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('task'));
        }]);

        self::assertInstanceOf(Task::class, $client->templates()->applyTaskTemplate('tp1'));
    }
}
