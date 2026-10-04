<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Task\Output\TaskComment;

/**
 * Rows and DTOs for TaskComment, based on fixtures/task-comments.json.
 */
final class TaskCommentFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['task-comments', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): TaskComment
    {
        return TaskComment::fromArray(self::make($overrides));
    }
}
