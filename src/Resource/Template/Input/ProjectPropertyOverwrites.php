<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Template\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Time\CalendarDate;

/**
 * Project properties that replace the template's values when a structure template is applied
 * (IProjectPropertyOverwrites). Only non-null fields are sent.
 */
final readonly class ProjectPropertyOverwrites
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $colorScheme = null,
        public ?string $officerId = null,
        public ?bool $isArchived = null,
        public ?bool $isDraft = null,
        public ?ProjectPriority $priority = null,
        public ?ProjectState $projectState = null,
        public ?CalendarDate $startDate = null,
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
            'isArchived' => $this->isArchived,
            'isDraft' => $this->isDraft,
            'priority' => $this->priority?->value,
            'projectState' => $this->projectState?->value,
            'startDate' => $this->startDate?->toFactro($timezone),
        ]);
    }
}
