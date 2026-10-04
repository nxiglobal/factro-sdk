<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User;

use Nxi\Factro\Resource\User\Output\EmployeeTag;
use Nxi\Factro\Resource\User\Output\User;
use Nxi\Factro\Resource\User\Users;
use Nxi\Factro\Testing\Fixtures;
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
            'GET /users/u1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('user')),
            'GET /users/missing' => new MockResponse('User with id "missing" not found', ['http_code' => 404]),
            'GET /users/u1/tags' => JsonMockResponse::fromFile(Fixtures::path('user-tags')),
        ]);

        self::assertInstanceOf(User::class, $client->users()->get('u1'));
        self::assertInstanceOf(User::class, $client->users()->find('u1'));
        self::assertNull($client->users()->find('missing'));
        $tags = $client->users()->tags('u1');
        self::assertCount(2, $tags);
        self::assertContainsOnlyInstancesOf(EmployeeTag::class, $tags);
    }

    public function testTagsForManyRunsConcurrentlyAndKeysByUserId(): void
    {
        $calls = [];
        $client = MockFactro::client([
            'GET /users/a/tags' => static function () use (&$calls): MockResponse {
                $calls[] = 'a';

                return JsonMockResponse::fromFile(Fixtures::path('user-tags'));
            },
            'GET /users/b/tags' => static function () use (&$calls): MockResponse {
                $calls[] = 'b';

                return new JsonMockResponse([]);
            },
            'GET /users/c/tags' => static function () use (&$calls): MockResponse {
                $calls[] = 'c';

                return new MockResponse('not found', ['http_code' => 404]);
            },
        ]);

        $result = $client->users()->tagsForMany(['a', 'b', 'c'], concurrency: 2);

        self::assertSame(['a', 'b', 'c'], array_keys($result));
        self::assertContainsOnlyInstancesOf(EmployeeTag::class, $result['a']);
        self::assertCount(2, $result['a']);
        self::assertSame([], $result['b']);
        self::assertSame([], $result['c']);
        self::assertCount(3, $calls);
    }

    public function testTagsForManyWithEmptyInput(): void
    {
        self::assertSame([], MockFactro::client([])->users()->tagsForMany([]));
    }

    public function testTagsForManyEncodesIds(): void
    {
        $client = MockFactro::client(['GET /users/a%2Fb/tags' => new JsonMockResponse([])]);

        self::assertSame(['a/b' => []], $client->users()->tagsForMany(['a/b']));
    }

    public function testEmployeeTagsEndpoint(): void
    {
        $client = MockFactro::client(['GET /users/tags' => JsonMockResponse::fromFile(Fixtures::path('employee-tags'))]);

        $tags = $client->users()->employeeTags();
        self::assertCount(5, $tags);
        self::assertContainsOnlyInstancesOf(EmployeeTag::class, $tags);
    }
}
