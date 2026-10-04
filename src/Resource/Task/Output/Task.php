<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\Task\TaskPriority;
use Nxi\Factro\Resource\Task\TaskState;
use Nxi\Factro\Resource\Task\Urgency;

/**
 * A factro task (IGetTaskPayload). targetParentId is write-only and therefore not hydrated.
 */
final readonly class Task
{
    /**
     * @param array<string, mixed> $customFields
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $projectId,
        public ?string $parentPackageId,
        public TaskState $taskState,
        public ?TaskPriority $taskPriority,
        public ?Urgency $urgency,
        public bool $isMilestone,
        public ?\DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $endDate,
        public ?\DateTimeImmutable $pausedUntil,
        public \DateTimeImmutable $creationDate,
        public ?\DateTimeImmutable $changeDate,
        public string $creatorId,
        public ?string $officerId,
        public ?string $executorId,
        public ?string $companyId,
        public ?string $companyContactId,
        public ?float $plannedEffort,
        public ?float $realizedEffort,
        public ?float $remainingEffort,
        public ?int $number,
        public ?string $colorScheme,
        public array $customFields,
        public string $mandantId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            id: Field::string($data, 'id', $o),
            title: Field::string($data, 'title', $o),
            description: Field::nullableString($data, 'description', $o),
            projectId: Field::string($data, 'projectId', $o),
            parentPackageId: Field::nullableString($data, 'parentPackageId', $o),
            taskState: Field::enum($data, 'taskState', $o, TaskState::class),
            taskPriority: Field::nullableEnum($data, 'taskPriority', $o, TaskPriority::class),
            urgency: Field::nullableEnum($data, 'urgency', $o, Urgency::class),
            isMilestone: Field::bool($data, 'isMilestone', $o),
            startDate: Field::nullableIsoUtc($data, 'startDate', $o),
            endDate: Field::nullableIsoUtc($data, 'endDate', $o),
            pausedUntil: Field::nullableIsoUtc($data, 'pausedUntil', $o),
            creationDate: Field::isoUtc($data, 'creationDate', $o),
            changeDate: Field::nullableIsoUtc($data, 'changeDate', $o),
            creatorId: Field::string($data, 'creatorId', $o),
            officerId: Field::nullableString($data, 'officerId', $o),
            executorId: Field::nullableString($data, 'executorId', $o),
            companyId: Field::nullableString($data, 'companyId', $o),
            companyContactId: Field::nullableString($data, 'companyContactId', $o),
            plannedEffort: Field::nullableFloat($data, 'plannedEffort', $o),
            realizedEffort: Field::nullableFloat($data, 'realizedEffort', $o),
            remainingEffort: Field::nullableFloat($data, 'remainingEffort', $o),
            number: Field::nullableInt($data, 'number', $o),
            colorScheme: Field::nullableString($data, 'colorScheme', $o),
            customFields: Field::array($data, 'customFields', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
