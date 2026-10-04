<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Appointment\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Time\FactroDateTime;

/**
 * Body of POST /appointments (ICreateAppointmentRequest). Dates are points in time and are sent as ISO-8601 UTC.
 *
 * factro requires either a subject or a referenced task; with a referenced task the task title becomes the subject.
 */
final readonly class NewAppointment
{
    /**
     * @throws \InvalidArgumentException when neither subject nor referencedTaskId is set
     */
    public function __construct(
        public string $employeeId,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public ?string $subject = null,
        public ?string $referencedTaskId = null,
        public ?string $description = null,
        public ?string $location = null,
        public ?float $distance = null,
    ) {
        if (null === $this->subject && null === $this->referencedTaskId) {
            throw new \InvalidArgumentException('NewAppointment needs a subject or referencedTaskId.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'employeeId' => $this->employeeId,
            'startDate' => FactroDateTime::toIsoUtc($this->startDate),
            'endDate' => FactroDateTime::toIsoUtc($this->endDate),
        ] + Payload::withoutNulls([
            'subject' => $this->subject,
            'referencedTaskId' => $this->referencedTaskId,
            'description' => $this->description,
            'location' => $this->location,
            'distance' => $this->distance,
        ]);
    }
}
