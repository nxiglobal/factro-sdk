<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\TodoList\Output;

use Nxi\Factro\Mapping\Field;

/**
 * The task or note a todo-list element points at (ITodoListReferencedElementPayload).
 * taskState stays a string: notes have none and the API does not promise the task-state vocabulary here.
 */
final readonly class TodoListReferencedElement
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $taskState,
        public ?string $colorScheme,
        public ?string $projectId,
        public ?string $parentPackageId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            id: Field::string($data, 'id', $o),
            title: Field::string($data, 'title', $o),
            taskState: Field::nullableString($data, 'taskState', $o),
            colorScheme: Field::nullableString($data, 'colorScheme', $o),
            projectId: Field::nullableString($data, 'projectId', $o),
            parentPackageId: Field::nullableString($data, 'parentPackageId', $o),
        );
    }
}
