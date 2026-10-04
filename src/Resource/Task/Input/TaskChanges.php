<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\Task\TaskPriority;
use Nxi\Factro\Resource\Task\Urgency;
use Nxi\Factro\Time\CalendarDate;

/**
 * Partial update for PUT /tasks/{id}. Only non-null fields are sent; factro treats PUT as a partial update.
 * Clearing a field is not supported, because it is undocumented whether factro accepts null for one.
 */
final readonly class TaskChanges
{
    /**
     * @param array<string, mixed>|null $customFields
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?CalendarDate $startDate = null,
        public ?CalendarDate $endDate = null,
        public ?string $officerId = null,
        public ?string $executorId = null,
        public ?TaskPriority $taskPriority = null,
        public ?bool $isMilestone = null,
        public ?float $plannedEffort = null,
        public ?float $remainingEffort = null,
        public ?string $colorScheme = null,
        public ?array $customFields = null,
        public ?string $targetParentId = null,
        public ?string $companyId = null,
        public ?string $companyContactId = null,
        public ?Urgency $urgency = null,
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
            'startDate' => $this->startDate?->toFactro($timezone),
            'endDate' => $this->endDate?->toFactro($timezone),
            'officerId' => $this->officerId,
            'executorId' => $this->executorId,
            'taskPriority' => $this->taskPriority?->value,
            'isMilestone' => $this->isMilestone,
            'plannedEffort' => $this->plannedEffort,
            'remainingEffort' => $this->remainingEffort,
            'colorScheme' => $this->colorScheme,
            'customFields' => $this->customFields,
            'targetParentId' => $this->targetParentId,
            'companyId' => $this->companyId,
            'companyContactId' => $this->companyContactId,
            'urgency' => $this->urgency?->value,
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload(new \DateTimeZone('UTC'));
    }
}
