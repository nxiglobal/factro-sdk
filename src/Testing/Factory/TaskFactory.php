<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Task\Output\Task;

/**
 * Rows and DTOs for Task, based on fixtures/tasks-by-project.json.
 */
final class TaskFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['tasks-by-project', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Task
    {
        return Task::fromArray(self::make($overrides));
    }
}
