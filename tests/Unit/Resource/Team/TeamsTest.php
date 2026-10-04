<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Team;

use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Team\Input\NewTeam;
use Nxi\Factro\Resource\Team\Input\TeamChanges;
use Nxi\Factro\Resource\Team\Output\Team;
use Nxi\Factro\Resource\Team\Output\TeamMember;
use Nxi\Factro\Resource\Team\Teams;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Teams::class)]
final class TeamsTest extends TestCase
{
    public function testListGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /teams' => JsonMockResponse::fromFile(Fixtures::path('teams')),
            'GET /teams/tm1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('team')),
            'GET /teams/missing' => new MockResponse('Team with id "missing" not found', ['http_code' => 404]),
        ]);

        $teams = $client->teams()->list();
        self::assertCount(3, $teams);
        self::assertContainsOnlyInstancesOf(Team::class, $teams);
        self::assertInstanceOf(Team::class, $client->teams()->get('tm1'));
        self::assertInstanceOf(Team::class, $client->teams()->find('tm1'));
        self::assertNull($client->teams()->find('missing'));
    }

    public function testCreatePostsPayloadAndReturnsTeam(): void
    {
        $client = MockFactro::client(['POST /teams' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"name":"Support","color":"#1e90ff","isActive":false}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('team'));
        }]);

        self::assertInstanceOf(Team::class, $client->teams()->create(new NewTeam('Support', '#1e90ff', isActive: false)));
    }

    public function testUpdateSendsOnlyChangedFields(): void
    {
        $client = MockFactro::client(['PUT /teams/tm1' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"isActive":false}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('team'));
        }]);

        self::assertInstanceOf(Team::class, $client->teams()->update('tm1', new TeamChanges(isActive: false)));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->teams()->update('tm1', new TeamChanges());
    }

    public function testDeleteDiscardsResponseBody(): void
    {
        $client = MockFactro::client(['DELETE /teams/tm1' => JsonMockResponse::fromFile(Fixtures::path('team'))]);

        $client->teams()->delete('tm1');
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->teams()->delete('tm1');
    }

    public function testMembersUsesMembersEndpoint(): void
    {
        $client = MockFactro::client(['GET /teams/tm1/members' => JsonMockResponse::fromFile(Fixtures::path('team-members'))]);

        $members = $client->teams()->members('tm1');

        self::assertCount(2, $members);
        self::assertContainsOnlyInstancesOf(TeamMember::class, $members);
        self::assertSame('a3e8eb42-6ada-519e-b57f-2f93743e7773', $members[0]->teamId);
        self::assertSame('464d0261-4ef7-5a89-8aaf-6b83f019eaeb', $members[0]->employeeId);
    }

    public function testAddMemberPutsEmployeeIdAndReturnsMember(): void
    {
        $client = MockFactro::client(['PUT /teams/tm1/members' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"employeeId":"e1"}', $o['body']);

            return new JsonMockResponse([
                'id' => 'm1',
                'teamId' => 'tm1',
                'employeeId' => 'e1',
                'mandantId' => '9e30ce50-948c-50ae-9084-ffa402165b5b',
            ]);
        }]);

        $member = $client->teams()->addMember('tm1', 'e1');

        self::assertSame('m1', $member->id);
        self::assertSame('tm1', $member->teamId);
        self::assertSame('e1', $member->employeeId);
    }

    public function testRemoveMemberDiscardsResponseBody(): void
    {
        $client = MockFactro::client(['DELETE /teams/tm1/members/m1' => new JsonMockResponse([
            'id' => 'm1',
            'teamId' => 'tm1',
            'employeeId' => 'e1',
            'mandantId' => '9e30ce50-948c-50ae-9084-ffa402165b5b',
        ])]);

        $client->teams()->removeMember('tm1', 'm1');
        $this->addToAssertionCount(1);
    }

    public function testRemoveMemberIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->teams()->removeMember('tm1', 'm1');
    }
}
