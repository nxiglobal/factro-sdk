<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\TodoList;

use Nxi\Factro\Resource\TodoList\ListElementReferenceType;
use Nxi\Factro\Resource\TodoList\Output\TodoList;
use Nxi\Factro\Resource\TodoList\TodoLists;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(TodoLists::class)]
#[CoversClass(ListElementReferenceType::class)]
final class TodoListsTest extends TestCase
{
    public function testListUsesTodoListEndpoint(): void
    {
        $client = MockFactro::client(['GET /todo-list' => JsonMockResponse::fromFile(Fixtures::path('todo-lists'))]);

        $lists = $client->todoLists()->list();

        self::assertCount(2, $lists);
        self::assertContainsOnlyInstancesOf(TodoList::class, $lists);
        self::assertCount(2, $lists[0]->listElements);
        self::assertSame([], $lists[1]->listElements);
    }

    public function testEmptyBodyYieldsEmptyList(): void
    {
        $client = MockFactro::client(['GET /todo-list' => new MockResponse('[]', ['http_code' => 200])]);

        self::assertSame([], $client->todoLists()->list());
    }

    public function testEnumValuesMatchTheApi(): void
    {
        self::assertSame(['task', 'note'], array_column(ListElementReferenceType::cases(), 'value'));
    }
}
