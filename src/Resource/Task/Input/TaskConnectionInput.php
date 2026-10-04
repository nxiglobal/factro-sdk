<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Input;

/**
 * Body of PUT /tasks/{id}/task_connections. A connection is either a predecessor or a successor.
 */
final readonly class TaskConnectionInput
{
    public function __construct(
        public string $connectedTaskId,
        public bool $isPredecessor = false,
        public bool $isSuccessor = false,
    ) {
        if ($isPredecessor && $isSuccessor) {
            throw new \InvalidArgumentException('A task connection is either a predecessor or a successor, not both.');
        }
    }

    /**
     * @return array{connectedTaskId: string, isPredecessor: bool, isSuccessor: bool}
     */
    public function toPayload(): array
    {
        return [
            'connectedTaskId' => $this->connectedTaskId,
            'isPredecessor' => $this->isPredecessor,
            'isSuccessor' => $this->isSuccessor,
        ];
    }
}
