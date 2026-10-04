<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\TodoList\Output\TodoList;

/**
 * Rows and DTOs for TodoList, based on fixtures/todo-lists.json.
 */
final class TodoListFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['todo-lists', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): TodoList
    {
        return TodoList::fromArray(self::make($overrides));
    }
}
