<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\TodoList\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\TodoList\ListElementReferenceType;

/**
 * An element of a todo list (ITodoListElementPayload). Elements form a linked list via nextListElementId.
 */
final readonly class TodoListElement
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $createdAt,
        public bool $checked,
        public ListElementReferenceType $elementReferenceType,
        public ?string $nextListElementId,
        public TodoListReferencedElement $referencedElement,
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
            createdAt: Field::isoUtc($data, 'createdAt', $o),
            checked: Field::bool($data, 'checked', $o),
            elementReferenceType: Field::enum($data, 'elementReferenceType', $o, ListElementReferenceType::class),
            nextListElementId: Field::nullableString($data, 'nextListElementId', $o),
            // A missing object yields [] and the nested required-field check reports the gap.
            referencedElement: TodoListReferencedElement::fromArray(Field::array($data, 'referencedElement', $o)),
        );
    }
}
