<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A checklist entry of a task (IGetChecklistEntryPayload). The API sends no mandantId for entries.
 */
final readonly class ChecklistEntry
{
    public function __construct(
        public string $id,
        public string $taskId,
        public string $title,
        public bool $checked,
        public ?float $position,
        public ?\DateTimeImmutable $endDate,
        public ?string $assigneeId,
        public ?\DateTimeImmutable $changeDate,
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
            taskId: Field::string($data, 'taskId', $o),
            title: Field::string($data, 'title', $o),
            checked: Field::bool($data, 'checked', $o),
            position: Field::nullableFloat($data, 'position', $o),
            endDate: Field::nullableIsoUtc($data, 'endDate', $o),
            assigneeId: Field::nullableString($data, 'assigneeId', $o),
            changeDate: Field::nullableIsoUtc($data, 'changeDate', $o),
        );
    }
}
