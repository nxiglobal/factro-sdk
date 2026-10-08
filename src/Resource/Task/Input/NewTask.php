<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\Task\TaskPriority;
use Nxi\Factro\Resource\Task\Urgency;
use Nxi\Factro\Time\CalendarDate;
use Nxi\Factro\Time\FactroDateTime;
use Psr\Clock\ClockInterface;

/**
 * Body of POST /tasks.
 *
 * targetParentId is the package the task is created in. Whether factro also accepts a project id
 * here (task on project level) is undocumented. Fields named in $clear are sent as explicit null instead of
 * leaving them to factro's default, e.g. ['executorId'] creates a task without executor (live test 2026-10-02).
 */
final readonly class NewTask
{
    /**
     * @param array<string, mixed>|null $customFields
     * @param list<string>              $clear        optional payload keys to send as null, e.g. ['executorId']
     */
    public function __construct(
        public string $title,
        public string $targetParentId,
        public ?string $description = null,
        public ?CalendarDate $startDate = null,
        public ?CalendarDate $endDate = null,
        public ?string $officerId = null,
        public ?string $executorId = null,
        public ?TaskPriority $taskPriority = null,
        public bool $isMilestone = false,
        public ?float $plannedEffort = null,
        public ?float $remainingEffort = null,
        public ?string $colorScheme = null,
        public ?array $customFields = null,
        public ?Urgency $urgency = null,
        public array $clear = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(\DateTimeZone $timezone, ClockInterface $clock, bool $sendCreationDate): array
    {
        $payload = [
            'title' => $this->title,
            'targetParentId' => $this->targetParentId,
            'isMilestone' => $this->isMilestone,
        ];
        if ($sendCreationDate) {
            // Accepted by the API; whether factro honours the value is undocumented.
            $payload['creationDate'] = FactroDateTime::toIsoUtc($clock->now());
        }

        return $payload + Payload::withoutNullsExcept([
            'description' => $this->description,
            'startDate' => $this->startDate?->toFactro($timezone),
            'endDate' => $this->endDate?->toFactro($timezone),
            'officerId' => $this->officerId,
            'executorId' => $this->executorId,
            'taskPriority' => $this->taskPriority?->value,
            'plannedEffort' => $this->plannedEffort,
            'remainingEffort' => $this->remainingEffort,
            'colorScheme' => $this->colorScheme,
            'customFields' => $this->customFields,
            'urgency' => $this->urgency?->value,
        ], $this->clear);
    }
}
