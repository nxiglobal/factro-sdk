<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\User\AbsenceType;

/**
 * A user absence (IGetUserAbsencesPayload); same shape for the list, per-user, create and update responses.
 */
final readonly class Absence
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public AbsenceType $type,
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
            employeeId: Field::string($data, 'employeeId', $o),
            startDate: Field::isoUtc($data, 'startDate', $o),
            endDate: Field::isoUtc($data, 'endDate', $o),
            type: Field::enum($data, 'type', $o, AbsenceType::class),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
