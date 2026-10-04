<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Time\CalendarDate;

/**
 * Partial update for PUT /projects/{id}. Only non-null fields are sent.
 */
final readonly class ProjectChanges
{
    /**
     * @param array<string, mixed>|null $customFields
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $colorScheme = null,
        public ?string $officerId = null,
        public ?array $customFields = null,
        public ?bool $isArchived = null,
        public ?bool $isDraft = null,
        public ?ProjectPriority $priority = null,
        public ?ProjectState $projectState = null,
        public ?CalendarDate $plannedStartDate = null,
        public ?CalendarDate $plannedEndDate = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(\DateTimeZone $timezone): array
    {
        return Payload::withoutNulls([
            'title' => $this->title,
            'description' => $this->description,
            'colorScheme' => $this->colorScheme,
            'officerId' => $this->officerId,
            'customFields' => $this->customFields,
            'isArchived' => $this->isArchived,
            'isDraft' => $this->isDraft,
            'priority' => $this->priority?->value,
            'projectState' => $this->projectState?->value,
            'plannedStartDate' => $this->plannedStartDate?->toFactro($timezone),
            'plannedEndDate' => $this->plannedEndDate?->toFactro($timezone),
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload(new \DateTimeZone('UTC'));
    }
}
