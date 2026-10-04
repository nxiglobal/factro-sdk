<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\CustomView\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\CustomView\CustomViewReferenceType;
use Nxi\Factro\Resource\CustomView\CustomViewViewType;

/**
 * A custom view or template (IGetCustomViewResponse / Partial_ICustomViewPayload_). Every field
 * except id is optional in the API; isTemplate defaults to false. columnOrder, filters and config
 * are passed through untouched because their shape is view-type specific and undocumented.
 * createdAt and updatedAt are JavaScript timestamps.
 */
final readonly class CustomView
{
    /**
     * @param array<mixed>         $columnOrder
     * @param array<string, mixed> $filters
     * @param list<SortEntry>      $sorting
     * @param list<string>         $grouping
     * @param array<string, mixed> $config
     */
    public function __construct(
        public string $id,
        public ?string $title,
        public ?string $description,
        public ?string $referenceId,
        public ?CustomViewReferenceType $referenceType,
        public ?CustomViewViewType $viewType,
        public array $columnOrder,
        public array $filters,
        public array $sorting,
        public array $grouping,
        public array $config,
        public bool $isTemplate,
        public ?string $sourceTemplateId,
        public ?int $position,
        public ?\DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?string $mandantId,
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
            description: Field::nullableString($data, 'description', $o),
            referenceId: Field::nullableString($data, 'referenceId', $o),
            referenceType: Field::nullableEnum($data, 'referenceType', $o, CustomViewReferenceType::class),
            viewType: Field::nullableEnum($data, 'viewType', $o, CustomViewViewType::class),
            columnOrder: Field::array($data, 'columnOrder', $o),
            filters: Field::array($data, 'filters', $o),
            sorting: Field::objectList($data, 'sorting', $o, SortEntry::fromArray(...)),
            grouping: Field::stringList($data, 'grouping', $o),
            config: Field::array($data, 'config', $o),
            isTemplate: Field::nullableBool($data, 'isTemplate', $o) ?? false,
            sourceTemplateId: Field::nullableString($data, 'sourceTemplateId', $o),
            position: Field::nullableInt($data, 'position', $o),
            createdAt: Field::nullableJsTimestamp($data, 'createdAt', $o),
            updatedAt: Field::nullableJsTimestamp($data, 'updatedAt', $o),
            mandantId: Field::nullableString($data, 'mandantId', $o),
        );
    }
}
