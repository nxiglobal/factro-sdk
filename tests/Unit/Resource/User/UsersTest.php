<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User;

use Nxi\Factro\Resource\User\Output\EmployeeTag;
use Nxi\Factro\Resource\User\Output\User;
use Nxi\Factro\Resource\User\Users;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Users::class)]
final class UsersTest extends TestCase
{
    public function testListHydratesAll(): void
    {
        $client = MockFactro::client(['GET /users' => JsonMockResponse::fromFile(Fixtures::path('users'))]);

        $users = $client->users()->list();

        self::assertCount(5, $users);
        self::assertContainsOnlyInstancesOf(User::class, $users);
    }

    public function testGetFindAndTags(): void
    {
        $client = MockFactro::client([
            'GET /users/'.Id::U1 => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('user')),
            'GET /users/'.Id::MISSING => new MockResponse('User with id "missing" not found', ['http_code' => 404]),
            'GET /users/'.Id::U1.'/tags' => JsonMockResponse::fromFile(Fixtures::path('user-tags')),
        ]);

        self::assertInstanceOf(User::class, $client->users()->get(Id::U1));
        self::assertInstanceOf(User::class, $client->users()->find(Id::U1));
        self::assertNull($client->users()->find(Id::MISSING));
        $tags = $client->users()->tags(Id::U1);
        self::assertCount(2, $tags);
        self::assertContainsOnlyInstancesOf(EmployeeTag::class, $tags);
    }

    public function testTagsForManyRunsConcurrentlyAndKeysByUserId(): void
    {
        $calls = [];
        $client = MockFactro::client([
            'GET /users/'.Id::U1.'/tags' => static function () use (&$calls): MockResponse {
                $calls[] = 'a';

                return JsonMockResponse::fromFile(Fixtures::path('user-tags'));
            },
            'GET /users/'.Id::U2.'/tags' => static function () use (&$calls): MockResponse {
                $calls[] = 'b';

                return new JsonMockResponse([]);
            },
            'GET /users/'.Id::MISSING.'/tags' => static function () use (&$calls): MockResponse {
                $calls[] = 'c';

                return new MockResponse('not found', ['http_code' => 404]);
            },
        ]);

        $result = $client->users()->tagsForMany([Id::U1, Id::U2, Id::MISSING], concurrency: 2);

        self::assertSame([Id::U1, Id::U2, Id::MISSING], array_keys($result));
        self::assertContainsOnlyInstancesOf(EmployeeTag::class, $result[Id::U1]);
        self::assertCount(2, $result[Id::U1]);
        self::assertSame([], $result[Id::U2]);
        self::assertSame([], $result[Id::MISSING]);
        self::assertCount(3, $calls);
    }

    public function testTagsForManyWithEmptyInput(): void
    {
        self::assertSame([], MockFactro::client([])->users()->tagsForMany([]));
    }

    public function testTagsForManyRejectsNonUuidIdsBeforeSending(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->users()->tagsForMany([Id::U1, 'a/b']);
    }

    public function testEmployeeTagsEndpoint(): void
    {
        $client = MockFactro::client(['GET /users/tags' => JsonMockResponse::fromFile(Fixtures::path('employee-tags'))]);

        $tags = $client->users()->employeeTags();
        self::assertCount(5, $tags);
        self::assertContainsOnlyInstancesOf(EmployeeTag::class, $tags);
    }
}
