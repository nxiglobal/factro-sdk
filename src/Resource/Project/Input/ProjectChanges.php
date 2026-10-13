<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Time\CalendarDate;

/**
 * Partial update for PUT /projects/{id}. Only non-null fields are sent; fields named in $clear are sent as explicit
 * null, which empties them in factro. Tested live: colorScheme accepts null.
 */
final readonly class ProjectChanges
{
    /**
     * Payload keys that factro accepts as null, verified against the live API.
     *
     * @var list<string>
     */
    public const array CLEARABLE = ['colorScheme'];

    /**
     * @param array<string, mixed>|null $customFields
     * @param list<string>              $clear        payload keys from CLEARABLE to send as null
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
        public array $clear = [],
    ) {
        Payload::assertClearable($this, $clear, self::CLEARABLE);
    }

    /**
     * @internal builds the request body; the format follows the factro API and may change in minor releases
     *
     * @return array<string, mixed>
     */
    public function toPayload(\DateTimeZone $timezone): array
    {
        return Payload::withoutNullsExcept([
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
        ], $this->clear);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload(new \DateTimeZone('UTC'));
    }
}
