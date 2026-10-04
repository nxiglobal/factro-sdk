<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\User\AbsenceType;
use Nxi\Factro\Time\FactroDateTime;

/**
 * Partial update for PUT /users/absences/{id} (IUpdateUserAbsenceRequest); also an element of
 * PUT /users/absences with its id. Only non-null fields are sent.
 */
final readonly class AbsenceChanges
{
    public function __construct(
        public ?\DateTimeImmutable $startDate = null,
        public ?\DateTimeImmutable $endDate = null,
        public ?AbsenceType $type = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'startDate' => null === $this->startDate ? null : FactroDateTime::toIsoUtc($this->startDate),
            'endDate' => null === $this->endDate ? null : FactroDateTime::toIsoUtc($this->endDate),
            'type' => $this->type?->value,
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload();
    }
}
