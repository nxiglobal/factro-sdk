<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\TodoList\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\TodoList\Output\TodoList;
use Nxi\Factro\Resource\TodoList\Output\TodoListElement;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TodoList::class)]
final class TodoListTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('todo-lists')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $list = TodoList::fromArray($row);

        self::assertSame($row['id'], $list->id);
        self::assertSame($row['title'], $list->title);
        self::assertSame($row['creatorId'], $list->creatorId);
        self::assertSame($row['officerId'], $list->officerId);
        self::assertFalse($list->isArchived);
        self::assertIsString($row['createdAt']);
        self::assertNotNull($list->createdAt);
        self::assertSame($row['createdAt'], FactroDateTime::toIsoUtc($list->createdAt));
        self::assertSame($row['mandantId'], $list->mandantId);
        self::assertCount(2, $list->listElements);
        self::assertContainsOnlyInstancesOf(TodoListElement::class, $list->listElements);
        self::assertSame($list->listElements[1]->id, $list->listElements[0]->nextListElementId);
    }

    public function testArchivedListWithoutElements(): void
    {
        $list = TodoList::fromArray($this->row(1));

        self::assertTrue($list->isArchived);
        self::assertNull($list->officerId);
        self::assertSame([], $list->listElements);
    }

    public function testOnlyIdIsRequiredAndDefaultsApply(): void
    {
        $list = TodoList::fromArray(['id' => 'l']);

        self::assertSame('l', $list->id);
        self::assertNull($list->title);
        self::assertNull($list->creatorId);
        self::assertNull($list->officerId);
        self::assertFalse($list->isArchived);
        self::assertNull($list->createdAt);
        self::assertNull($list->mandantId);
        self::assertSame([], $list->listElements);
    }

    public function testMissingIdThrows(): void
    {
        $row = $this->row();
        unset($row['id']);

        $this->expectException(HydrationException::class);
        TodoList::fromArray($row);
    }
}
