<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\CustomView\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\CustomView\Output\SortEntry;

/**
 * Partial update for PUT /custom-views/{id} (IUpdateCustomViewRequest). Only non-null fields are sent.
 */
final readonly class CustomViewChanges
{
    /**
     * @param array<mixed>|null         $columnOrder
     * @param array<string, mixed>|null $filters
     * @param list<SortEntry>|null      $sorting
     * @param list<string>|null         $grouping
     * @param array<string, mixed>|null $config
     */
    public function __construct(
        public ?string $description = null,
        public ?array $columnOrder = null,
        public ?string $title = null,
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
        return Payload::withoutNulls([
            'description' => $this->description,
            'columnOrder' => $this->columnOrder,
            'title' => $this->title,
            'filters' => $this->filters,
            'sorting' => null === $this->sorting ? null : array_map(static fn (SortEntry $entry): array => $entry->toPayload(), $this->sorting),
            'grouping' => $this->grouping,
            'config' => $this->config,
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload();
    }
}
