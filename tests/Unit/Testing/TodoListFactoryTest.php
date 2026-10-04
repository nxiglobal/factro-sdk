<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\TodoList\Output\TodoList;
use Nxi\Factro\Testing\Factory\TodoListFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TodoListFactory::class)]
final class TodoListFactoryTest extends TestCase
{
    public function testMakeAppliesOverridesAndDtoHydrates(): void
    {
        $row = TodoListFactory::make(['title' => 'Custom', 'officerId' => null]);

        self::assertSame('Custom', $row['title']);
        self::assertArrayHasKey('officerId', $row);
        self::assertNull($row['officerId']);

        $dto = TodoListFactory::dto(['isArchived' => true]);
        self::assertInstanceOf(TodoList::class, $dto);
        self::assertTrue($dto->isArchived);
        self::assertCount(2, $dto->listElements);
    }

    public function testManyProducesDistinctIds(): void
    {
        $rows = TodoListFactory::many(3);

        self::assertCount(3, $rows);
        $ids = [];
        foreach ($rows as $row) {
            self::assertIsString($row['id']);
            $ids[] = $row['id'];
        }
        self::assertCount(3, array_unique($ids));
        self::assertContainsOnlyInstancesOf(TodoList::class, array_map(TodoList::fromArray(...), $rows));
    }
}
