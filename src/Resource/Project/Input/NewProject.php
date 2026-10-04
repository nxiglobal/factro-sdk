<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Time\CalendarDate;

/**
 * Body of POST /projects (ICreateProjectRequest). Only the title is required; unset fields are omitted.
 */
final readonly class NewProject
{
    /**
     * @param array<string, mixed>|null $customFields
     */
    public function __construct(
        public string $title,
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
        return ['title' => $this->title] + Payload::withoutNulls([
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
}
