<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\TodoList\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A todo list (IGetTodoListPayload). Only id is required by the API; isArchived defaults to false.
 */
final readonly class TodoList
{
    /**
     * @param list<TodoListElement> $listElements
     */
    public function __construct(
        public string $id,
        public ?string $title,
        public ?string $creatorId,
        public ?string $officerId,
        public bool $isArchived,
        public ?\DateTimeImmutable $createdAt,
        public ?string $mandantId,
        public array $listElements,
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
            title: Field::nullableString($data, 'title', $o),
            creatorId: Field::nullableString($data, 'creatorId', $o),
            officerId: Field::nullableString($data, 'officerId', $o),
            isArchived: Field::nullableBool($data, 'isArchived', $o) ?? false,
            createdAt: Field::nullableIsoUtc($data, 'createdAt', $o),
            mandantId: Field::nullableString($data, 'mandantId', $o),
            listElements: Field::objectList($data, 'listElements', $o, TodoListElement::fromArray(...)),
        );
    }
}
