<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Time\CalendarDate;

/**
 * Partial update for PUT /tasks/{id}/checklist/{entryId} (IUpdateChecklistEntryRequest).
 * Only non-null fields are sent; the end date is a calendar day converted like task dates.
 * Fields named in $clear are sent as explicit null, which empties them in factro. Tested live on 2026-10-02:
 * assigneeId and endDate accept null.
 */
final readonly class ChecklistEntryChanges
{
    /**
     * @param list<string> $clear payload keys to send as null, e.g. ['assigneeId']
     */
    public function __construct(
        public ?string $title = null,
        public ?bool $checked = null,
        public ?float $position = null,
        public ?CalendarDate $endDate = null,
        public ?string $assigneeId = null,
        public array $clear = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(\DateTimeZone $timezone): array
    {
        return Payload::withoutNullsExcept([
            'title' => $this->title,
            'checked' => $this->checked,
            'position' => $this->position,
            'endDate' => $this->endDate?->toFactro($timezone),
            'assigneeId' => $this->assigneeId,
        ], $this->clear);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload(new \DateTimeZone('UTC'));
    }
}
