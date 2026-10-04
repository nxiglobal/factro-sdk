<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A predecessor/successor relation between two tasks (IGetTaskConnectionPayload).
 */
final readonly class TaskConnection
{
    public function __construct(
        public string $id,
        public string $taskId,
        public string $referenceId,
        public bool $isPredecessor,
        public bool $isSuccessor,
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
            referenceId: Field::string($data, 'referenceId', $o),
            isPredecessor: Field::bool($data, 'isPredecessor', $o),
            isSuccessor: Field::bool($data, 'isSuccessor', $o),
            changeDate: Field::nullableIsoUtc($data, 'changeDate', $o),
        );
    }
}
