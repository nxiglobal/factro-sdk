<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Appointment\Output;

use Nxi\Factro\Mapping\Field;

/**
 * An appointment (IGetAppointmentPayload). duration is reported by factro in hours and is read-only.
 */
final readonly class Appointment
{
    public function __construct(
        public string $id,
        public ?string $subject,
        public ?string $description,
        public ?string $location,
        public ?float $distance,
        public ?float $duration,
        public string $employeeId,
        public ?string $referencedTaskId,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public ?string $creatorId,
        public ?\DateTimeImmutable $creationDate,
        public ?\DateTimeImmutable $changeDate,
        public ?string $mandantId,
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
            subject: Field::nullableString($data, 'subject', $o),
            description: Field::nullableString($data, 'description', $o),
            location: Field::nullableString($data, 'location', $o),
            distance: Field::nullableFloat($data, 'distance', $o),
            duration: Field::nullableFloat($data, 'duration', $o),
            employeeId: Field::string($data, 'employeeId', $o),
            referencedTaskId: Field::nullableString($data, 'referencedTaskId', $o),
            startDate: Field::isoUtc($data, 'startDate', $o),
            endDate: Field::isoUtc($data, 'endDate', $o),
            creatorId: Field::nullableString($data, 'creatorId', $o),
            creationDate: Field::nullableIsoUtc($data, 'creationDate', $o),
            changeDate: Field::nullableIsoUtc($data, 'changeDate', $o),
            mandantId: Field::nullableString($data, 'mandantId', $o),
        );
    }
}
