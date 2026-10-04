<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\CustomView\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\CustomView\CustomViewReferenceType;
use Nxi\Factro\Resource\CustomView\Output\SortEntry;

/**
 * Body of POST /custom-views (ICreateCustomViewRequest): a view on a reference, derived from a template.
 * The optional arrays override the template's settings; an empty array is sent as such.
 */
final readonly class NewCustomView
{
    /**
     * @param array<mixed>|null         $columnOrder
     * @param array<string, mixed>|null $filters
     * @param list<SortEntry>|null      $sorting
     * @param list<string>|null         $grouping
     * @param array<string, mixed>|null $config
     */
    public function __construct(
        public string $referenceId,
        public CustomViewReferenceType $referenceType,
        public string $title,
        public string $sourceTemplateId,
        public ?array $columnOrder = null,
        public ?array $filters = null,
        public ?array $sorting = null,
        public ?array $grouping = null,
        public ?array $config = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'referenceId' => $this->referenceId,
            'referenceType' => $this->referenceType->value,
            'title' => $this->title,
            'sourceTemplateId' => $this->sourceTemplateId,
        ] + Payload::withoutNulls([
            'columnOrder' => $this->columnOrder,
            'filters' => $this->filters,
            'sorting' => null === $this->sorting ? null : array_map(static fn (SortEntry $entry): array => $entry->toPayload(), $this->sorting),
            'grouping' => $this->grouping,
            'config' => $this->config,
        ]);
    }
}
