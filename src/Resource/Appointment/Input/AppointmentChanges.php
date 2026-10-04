<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Appointment\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Time\FactroDateTime;

/**
 * Partial update for PUT /appointments/{id} (IUpdateAppointmentRequest). Only non-null fields are sent.
 * The employee of an appointment cannot be changed through the API.
 */
final readonly class AppointmentChanges
{
    public function __construct(
        public ?string $subject = null,
        public ?string $referencedTaskId = null,
        public ?string $description = null,
        public ?string $location = null,
        public ?float $distance = null,
        public ?\DateTimeImmutable $startDate = null,
        public ?\DateTimeImmutable $endDate = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'subject' => $this->subject,
            'referencedTaskId' => $this->referencedTaskId,
            'description' => $this->description,
            'location' => $this->location,
            'distance' => $this->distance,
            'startDate' => null === $this->startDate ? null : FactroDateTime::toIsoUtc($this->startDate),
            'endDate' => null === $this->endDate ? null : FactroDateTime::toIsoUtc($this->endDate),
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload();
    }
}
