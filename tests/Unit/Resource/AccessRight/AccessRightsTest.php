<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\AccessRight;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Http\Transport;
use Nxi\Factro\Resource\AccessRight\AccessRights;
use Nxi\Factro\Resource\AccessRight\Output\AccessRightReason;
use Nxi\Factro\Resource\AccessRight\Output\EmployeeAccessRight;
use Nxi\Factro\Resource\AccessRight\Output\TeamAccessRight;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(AccessRights::class)]
#[CoversClass(AccessRightReason::class)]
#[CoversClass(EmployeeAccessRight::class)]
#[CoversClass(TeamAccessRight::class)]
final class AccessRightsTest extends TestCase
{
    /**
     * @param array<string, \Symfony\Contracts\HttpClient\ResponseInterface|\Closure(string, string, array<string, mixed>): \Symfony\Contracts\HttpClient\ResponseInterface> $routes
     */
    private function rights(array $routes, string $employeePath = '/tasks/t1', string $teamPath = '/tasks/t1'): AccessRights
    {
        $options = new FactroOptions(baseUrl: MockFactro::BASE_URL, maxRetries: 0);

        return new AccessRights(new Transport(MockFactro::http($routes), $options), $options, $employeePath, $teamPath);
    }

    public function testReadAndWriteRightsMapEmployeeIdsToReasons(): void
    {
        $rights = $this->rights([
            'GET /tasks/t1/read_rights' => JsonMockResponse::fromFile(Fixtures::path('access-rights')),
            'GET /tasks/t1/write_rights' => new JsonMockResponse([]),
        ]);

        $read = $rights->readRights();
        self::assertCount(3, $read);
        self::assertEquals([
            new AccessRightReason('IsProjectOfficer', projectId: 'd61385c7-c0ca-5faa-a628-09430d2b096a'),
            new AccessRightReason('HasDirectProjectWriteRight', projectId: 'd61385c7-c0ca-5faa-a628-09430d2b096a'),
        ], $read['354912c9-4579-5834-ba5f-f039bf82268c']);
        self::assertEquals(
            new AccessRightReason('HasDirectPackageTeamWriteRight', packageId: '453190d4-b4cb-5516-a449-e682d35fdbdc', teamId: 'c4d5e6f7-a8b9-5c0d-9e1f-3a4b5c6d7e8f'),
            $read['f5f10d08-5b3a-5c46-8d91-9dd049f5d2e5'][0],
        );
        self::assertEquals(
            new AccessRightReason('IsTaskExecutor', taskId: 'e53f64f5-6dcc-5990-af50-e315dc575d1a'),
            $read['464d0261-4ef7-5a89-8aaf-6b83f019eaeb'][0],
        );
        self::assertSame([], $rights->writeRights());
    }

    public function testUnknownReasonsAndExtraKeysHydrateUnchanged(): void
    {
        $rights = $this->rights(['GET /tasks/t1/write_rights' => new JsonMockResponse([
            'u1' => [['reason' => 'HasSomethingNew', 'roomId' => 'r1', 'listId' => 'l1']],
        ])]);

        self::assertEquals(['u1' => [new AccessRightReason('HasSomethingNew')]], $rights->writeRights());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function malformedReasons(): iterable
    {
        yield 'list is not an array' => ['IsProjectOfficer'];
        yield 'entry is a plain string' => [['IsProjectOfficer']];
        yield 'entry without reason' => [[['projectId' => 'p1']]];
        yield 'id is not a string' => [[['reason' => 'IsTaskExecutor', 'taskId' => 1]]];
    }

    #[DataProvider('malformedReasons')]
    public function testMalformedReasonsAreRejected(mixed $reasons): void
    {
        $rights = $this->rights(['GET /tasks/t1/read_rights' => new JsonMockResponse(['u1' => $reasons])]);

        $this->expectException(HydrationException::class);
        $rights->readRights();
    }

    public function testGrantAndRevokeForEmployees(): void
    {
        $rights = $this->rights([
            'PUT /tasks/t1/read_rights' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"employeeId":"e1"}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('employee-access-right'));
            },
            'PUT /tasks/t1/write_rights' => JsonMockResponse::fromFile(Fixtures::path('employee-access-right')),
            'DELETE /tasks/t1/read_rights/e1' => new MockResponse('', ['http_code' => 204]),
            'DELETE /tasks/t1/write_rights/e1' => new MockResponse('', ['http_code' => 204]),
        ]);

        $granted = $rights->grantRead('e1');
        self::assertSame('f5f10d08-5b3a-5c46-8d91-9dd049f5d2e5', $granted->employeeId);
        self::assertFalse($granted->canEdit);
        self::assertInstanceOf(EmployeeAccessRight::class, $rights->grantWrite('e1'));
        $rights->revokeRead('e1');
        $rights->revokeWrite('e1');
        $this->addToAssertionCount(2);
    }

    public function testTeamRightsUseTheTeamPath(): void
    {
        $rights = $this->rights([
            'PUT /projects/p1/pk1/team_read_rights' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"teamId":"team1"}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('team-access-right'));
            },
            'PUT /projects/p1/pk1/team_write_rights' => JsonMockResponse::fromFile(Fixtures::path('team-access-right')),
            'DELETE /projects/p1/pk1/team_read_rights/team1' => new MockResponse('', ['http_code' => 204]),
            'DELETE /projects/p1/pk1/team_write_rights/team1' => new MockResponse('', ['http_code' => 204]),
        ], '/projects/p1/packages/pk1', '/projects/p1/pk1');

        self::assertSame('c4d5e6f7-a8b9-5c0d-9e1f-3a4b5c6d7e8f', $rights->grantTeamRead('team1')->teamId);
        self::assertInstanceOf(TeamAccessRight::class, $rights->grantTeamWrite('team1'));
        $rights->revokeTeamRead('team1');
        $rights->revokeTeamWrite('team1');
        $this->addToAssertionCount(2);
    }
}
