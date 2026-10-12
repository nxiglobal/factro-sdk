<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User;

use Nxi\Factro\Resource\User\AbsenceType;
use Nxi\Factro\Resource\User\Input\AbsenceChanges;
use Nxi\Factro\Resource\User\Input\NewAbsence;
use Nxi\Factro\Resource\User\Input\NewUser;
use Nxi\Factro\Resource\User\Input\UserChanges;
use Nxi\Factro\Resource\User\Output\Absence;
use Nxi\Factro\Resource\User\Output\EmployeeTag;
use Nxi\Factro\Resource\User\Output\User;
use Nxi\Factro\Resource\User\Output\UserQuota;
use Nxi\Factro\Resource\User\SecurityGroup;
use Nxi\Factro\Resource\User\Users;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Write, batch, tag, substitute, quota and absence operations. Read operations live in UsersTest.
 */
#[CoversClass(Users::class)]
final class UsersWriteTest extends TestCase
{
    public function testCreatePostsPayloadAndReturnsUser(): void
    {
        $client = MockFactro::client(['POST /users' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '{"emailAddress":"new@example.invalid","firstName":"New","lastName":"User","securityGroup":"BasicRights","city":"Hamburg"}',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('user'));
        }]);

        $user = $client->users()->create(new NewUser('new@example.invalid', 'New', 'User', SecurityGroup::BASIC_RIGHTS, city: 'Hamburg'));

        self::assertInstanceOf(User::class, $user);
    }

    public function testUpdateSendsOnlyChangedFields(): void
    {
        $client = MockFactro::client(['PUT /users/'.Id::U1 => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"lastName":"New"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('user'));
        }]);

        self::assertInstanceOf(User::class, $client->users()->update(Id::U1, new UserChanges(lastName: 'New')));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->update(Id::U1, new UserChanges());
    }

    public function testDeleteDiscardsResponse(): void
    {
        $client = MockFactro::client(['DELETE /users/'.Id::U1 => JsonMockResponse::fromFile(Fixtures::path('user'))]);

        $client->users()->delete(Id::U1);
        $this->addToAssertionCount(1);
    }

    public function testCreateManyPostsListToUsersUsers(): void
    {
        $client = MockFactro::client(['POST /users/users' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '[{"emailAddress":"a@example.invalid","firstName":"A","lastName":"A","securityGroup":"BasicRights"},'
                .'{"emailAddress":"b@example.invalid","firstName":"B","lastName":"B","securityGroup":"GuestRights"}]',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('users'));
        }]);

        $users = $client->users()->createMany([
            new NewUser('a@example.invalid', 'A', 'A', SecurityGroup::BASIC_RIGHTS),
            new NewUser('b@example.invalid', 'B', 'B', SecurityGroup::GUEST_RIGHTS),
        ]);

        self::assertCount(5, $users);
        self::assertContainsOnlyInstancesOf(User::class, $users);
    }

    public function testCreateManyWithEmptyInputIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->createMany([]);
    }

    public function testUpdateManyPutsListWithIds(): void
    {
        $client = MockFactro::client(['PUT /users/users' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"id":"'.Id::U1.'","city":"Hamburg"},{"id":"'.Id::U2.'","securityGroup":"AllRights"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('users'));
        }]);

        $users = $client->users()->updateMany([
            Id::U1 => new UserChanges(city: 'Hamburg'),
            Id::U2 => new UserChanges(securityGroup: SecurityGroup::ALL_RIGHTS),
        ]);

        self::assertContainsOnlyInstancesOf(User::class, $users);
    }

    public function testUpdateManyWithEmptyInputIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->updateMany([]);
    }

    public function testUpdateManyWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->updateMany([Id::U1 => new UserChanges()]);
    }

    public function testCreateEmployeeTagPostsName(): void
    {
        $client = MockFactro::client(['POST /users/tags' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"name":"Tag 1"}', $o['body']);

            return new JsonMockResponse(Fixtures::json('employee-tags')[0]);
        }]);

        $tag = $client->users()->createEmployeeTag('Tag 1');

        self::assertInstanceOf(EmployeeTag::class, $tag);
        self::assertSame('Tag 1', $tag->name);
    }

    public function testDeleteEmployeeTagAccepts200WithBody(): void
    {
        $client = MockFactro::client(['DELETE /users/tags/'.Id::T1 => new JsonMockResponse(Fixtures::json('employee-tags')[0])]);

        $client->users()->deleteEmployeeTag(Id::T1);
        $this->addToAssertionCount(1);
    }

    public function testAddAndRemoveTag(): void
    {
        $client = MockFactro::client([
            'PUT /users/'.Id::U1.'/tags' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"tagId":"'.Id::T1.'"}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /users/'.Id::U1.'/tags/'.Id::T1 => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->users()->addTag(Id::U1, Id::T1);
        $client->users()->removeTag(Id::U1, Id::T1);
        $this->addToAssertionCount(1);
    }

    public function testSubstitutesAndSubstitutedUsers(): void
    {
        $client = MockFactro::client([
            'GET /users/'.Id::U1.'/substitutes' => JsonMockResponse::fromFile(Fixtures::path('users')),
            'GET /users/'.Id::U1.'/substituted' => new JsonMockResponse([Fixtures::json('user')]),
        ]);

        $substitutes = $client->users()->substitutes(Id::U1);
        self::assertCount(5, $substitutes);
        self::assertContainsOnlyInstancesOf(User::class, $substitutes);

        $substituted = $client->users()->substitutedUsers(Id::U1);
        self::assertCount(1, $substituted);
        self::assertContainsOnlyInstancesOf(User::class, $substituted);
    }

    public function testAddAndRemoveSubstitute(): void
    {
        $client = MockFactro::client([
            'PUT /users/'.Id::U1.'/substitutes' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"substituteId":"'.Id::U2.'"}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /users/'.Id::U1.'/substitutes/'.Id::U2 => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->users()->addSubstitute(Id::U1, Id::U2);
        $client->users()->removeSubstitute(Id::U1, Id::U2);
        $this->addToAssertionCount(1);
    }

    public function testQuotaUsesLiteralPath(): void
    {
        $client = MockFactro::client(['GET /users/quota' => JsonMockResponse::fromFile(Fixtures::path('user-quota'))]);

        $quota = $client->users()->quota();

        self::assertInstanceOf(UserQuota::class, $quota);
        self::assertSame(25, $quota->maxPaidUserCount);
    }

    public function testAbsencesAndAbsencesOf(): void
    {
        $client = MockFactro::client([
            'GET /users/absences' => JsonMockResponse::fromFile(Fixtures::path('absences')),
            'GET /users/'.Id::U1.'/absences' => new JsonMockResponse([Fixtures::json('absence')]),
        ]);

        $all = $client->users()->absences();
        self::assertCount(3, $all);
        self::assertContainsOnlyInstancesOf(Absence::class, $all);

        $ofUser = $client->users()->absencesOf(Id::U1);
        self::assertCount(1, $ofUser);
        self::assertContainsOnlyInstancesOf(Absence::class, $ofUser);
    }

    public function testCreateAbsencePostsToUserPathWithoutEmployeeId(): void
    {
        $client = MockFactro::client(['POST /users/'.Id::U1.'/absences' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '{"startDate":"2026-10-04T22:00:00.000Z","endDate":"2026-10-09T22:00:00.000Z","type":"Planned"}',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('absence'));
        }]);

        $absence = $client->users()->createAbsence(Id::U1, new NewAbsence(
            new \DateTimeImmutable('2026-10-05T00:00:00+02:00'),
            new \DateTimeImmutable('2026-10-10T00:00:00+02:00'),
            AbsenceType::PLANNED,
            employeeId: 'ignored-in-single-create',
        ));

        self::assertInstanceOf(Absence::class, $absence);
    }

    public function testCreateAbsencesPostsListWithEmployeeIds(): void
    {
        $client = MockFactro::client(['POST /users/absences' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '[{"startDate":"2026-10-04T22:00:00.000Z","endDate":"2026-10-09T22:00:00.000Z","type":"Planned","employeeId":"'.Id::U1.'"},'
                .'{"startDate":"2026-09-13T22:00:00.000Z","endDate":"2026-09-14T22:00:00.000Z","type":"Unplanned","employeeId":"'.Id::U2.'"}]',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('absences'));
        }]);

        $absences = $client->users()->createAbsences([
            new NewAbsence(new \DateTimeImmutable('2026-10-04T22:00:00Z'), new \DateTimeImmutable('2026-10-09T22:00:00Z'), AbsenceType::PLANNED, Id::U1),
            new NewAbsence(new \DateTimeImmutable('2026-09-13T22:00:00Z'), new \DateTimeImmutable('2026-09-14T22:00:00Z'), AbsenceType::UNPLANNED, Id::U2),
        ]);

        self::assertCount(3, $absences);
        self::assertContainsOnlyInstancesOf(Absence::class, $absences);
    }

    public function testCreateAbsencesWithoutEmployeeIdIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->createAbsences([
            new NewAbsence(new \DateTimeImmutable('2026-10-04T22:00:00Z'), new \DateTimeImmutable('2026-10-09T22:00:00Z'), AbsenceType::PLANNED),
        ]);
    }

    public function testCreateAbsencesWithEmptyInputIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->createAbsences([]);
    }

    public function testUpdateAbsencePutsChanges(): void
    {
        $client = MockFactro::client(['PUT /users/absences/'.Id::A1 => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"endDate":"2026-10-11T22:00:00.000Z","type":"Unplanned"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('absence'));
        }]);

        $absence = $client->users()->updateAbsence(Id::A1, new AbsenceChanges(endDate: new \DateTimeImmutable('2026-10-11T22:00:00Z'), type: AbsenceType::UNPLANNED));

        self::assertInstanceOf(Absence::class, $absence);
    }

    public function testUpdateAbsenceWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->updateAbsence(Id::A1, new AbsenceChanges());
    }

    public function testUpdateAbsencesPutsListWithIds(): void
    {
        $client = MockFactro::client(['PUT /users/absences' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"id":"'.Id::A1.'","type":"Planned"},{"id":"a2","startDate":"2026-09-01T00:00:00.000Z"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('absences'));
        }]);

        $absences = $client->users()->updateAbsences([
            Id::A1 => new AbsenceChanges(type: AbsenceType::PLANNED),
            'a2' => new AbsenceChanges(startDate: new \DateTimeImmutable('2026-09-01T00:00:00Z')),
        ]);

        self::assertContainsOnlyInstancesOf(Absence::class, $absences);
    }

    public function testUpdateAbsencesWithEmptyInputIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->updateAbsences([]);
    }

    public function testDeleteAbsenceAccepts204(): void
    {
        $client = MockFactro::client(['DELETE /users/absences/'.Id::A1 => new MockResponse('', ['http_code' => 204])]);

        $client->users()->deleteAbsence(Id::A1);
        $this->addToAssertionCount(1);
    }

    public function testNonUuidIdsAreRejectedBeforeSending(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->deleteAbsence('x/y');
    }
}
