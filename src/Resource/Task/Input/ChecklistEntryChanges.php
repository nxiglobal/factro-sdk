<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Time\CalendarDate;

/**
 * Partial update for PUT /tasks/{id}/checklist/{entryId} (IUpdateChecklistEntryRequest).
 * Only non-null fields are sent; the end date is a calendar day converted like task dates.
 */
final readonly class ChecklistEntryChanges
{
    public function __construct(
        public ?string $title = null,
        public ?bool $checked = null,
        public ?float $position = null,
        public ?CalendarDate $endDate = null,
        public ?string $assigneeId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(\DateTimeZone $timezone): array
    {
        return Payload::withoutNulls([
            'title' => $this->title,
            'checked' => $this->checked,
            'position' => $this->position,
            'endDate' => $this->endDate?->toFactro($timezone),
            'assigneeId' => $this->assigneeId,
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload(new \DateTimeZone('UTC'));
    }
}
