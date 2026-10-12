<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Team;

use Nxi\Factro\Exception\OperationNotPermittedException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Team\Input\NewTeam;
use Nxi\Factro\Resource\Team\Input\TeamChanges;
use Nxi\Factro\Resource\Team\Output\Team;
use Nxi\Factro\Resource\Team\Output\TeamMember;
use Nxi\Factro\Resource\Team\Teams;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
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
            'GET /teams/'.Id::TM1 => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('team')),
            'GET /teams/'.Id::MISSING => new MockResponse('Team with id "missing" not found', ['http_code' => 404]),
        ]);

        $teams = $client->teams()->list();
        self::assertCount(3, $teams);
        self::assertContainsOnlyInstancesOf(Team::class, $teams);
        self::assertInstanceOf(Team::class, $client->teams()->get(Id::TM1));
        self::assertInstanceOf(Team::class, $client->teams()->find(Id::TM1));
        self::assertNull($client->teams()->find(Id::MISSING));
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
        $client = MockFactro::client(['PUT /teams/'.Id::TM1 => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"isActive":false}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('team'));
        }]);

        self::assertInstanceOf(Team::class, $client->teams()->update(Id::TM1, new TeamChanges(isActive: false)));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->teams()->update(Id::TM1, new TeamChanges());
    }

    public function testDeleteDiscardsResponseBody(): void
    {
        $client = MockFactro::client(['DELETE /teams/'.Id::TM1 => JsonMockResponse::fromFile(Fixtures::path('team'))]);

        $client->teams()->delete(Id::TM1);
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->teams()->delete(Id::TM1);
    }

    public function testMembersUsesMembersEndpoint(): void
    {
        $client = MockFactro::client(['GET /teams/'.Id::TM1.'/members' => JsonMockResponse::fromFile(Fixtures::path('team-members'))]);

        $members = $client->teams()->members(Id::TM1);

        self::assertCount(2, $members);
        self::assertContainsOnlyInstancesOf(TeamMember::class, $members);
        self::assertSame('a3e8eb42-6ada-519e-b57f-2f93743e7773', $members[0]->teamId);
        self::assertSame('464d0261-4ef7-5a89-8aaf-6b83f019eaeb', $members[0]->employeeId);
    }

    public function testAddMemberPutsEmployeeIdAndReturnsMember(): void
    {
        $client = MockFactro::client(['PUT /teams/'.Id::TM1.'/members' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"employeeId":"'.Id::E1.'"}', $o['body']);

            return new JsonMockResponse([
                'id' => Id::M1,
                'teamId' => Id::TM1,
                'employeeId' => Id::E1,
                'mandantId' => '9e30ce50-948c-50ae-9084-ffa402165b5b',
            ]);
        }]);

        $member = $client->teams()->addMember(Id::TM1, Id::E1);

        self::assertSame(Id::M1, $member->id);
        self::assertSame(Id::TM1, $member->teamId);
        self::assertSame(Id::E1, $member->employeeId);
    }

    public function testRemoveMemberDiscardsResponseBody(): void
    {
        $client = MockFactro::client(['DELETE /teams/'.Id::TM1.'/members/'.Id::M1 => new JsonMockResponse([
            'id' => Id::M1,
            'teamId' => Id::TM1,
            'employeeId' => Id::E1,
            'mandantId' => '9e30ce50-948c-50ae-9084-ffa402165b5b',
        ])]);

        $client->teams()->removeMember(Id::TM1, Id::M1);
        $this->addToAssertionCount(1);
    }

    public function testRemoveMemberIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->teams()->removeMember(Id::TM1, Id::M1);
    }
}
