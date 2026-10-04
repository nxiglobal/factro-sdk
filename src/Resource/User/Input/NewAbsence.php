<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User\Input;

use Nxi\Factro\Resource\User\AbsenceType;
use Nxi\Factro\Time\FactroDateTime;

/**
 * Body of POST /users/{id}/absences (ICreateUserAbsenceRequest) and, with employeeId, an element of
 * POST /users/absences (ICreateUserAbsenceRequestWithEmployeeId). Dates are sent as ISO-8601 UTC timestamps.
 */
final readonly class NewAbsence
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public AbsenceType $type,
        public ?string $employeeId = null,
    ) {
        if ($endDate < $startDate) {
            throw new \InvalidArgumentException('NewAbsence: endDate must not be before startDate.');
        }
    }

    /**
     * Payload of the single-user route; employeeId is taken from the path and never sent.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'startDate' => FactroDateTime::toIsoUtc($this->startDate),
            'endDate' => FactroDateTime::toIsoUtc($this->endDate),
            'type' => $this->type->value,
        ];
    }

    /**
     * Payload of the batch route, which requires employeeId on every element.
     *
     * @return array<string, mixed>
     */
    public function toBatchPayload(): array
    {
        if (null === $this->employeeId) {
            throw new \InvalidArgumentException('NewAbsence: employeeId is required for POST /users/absences.');
        }

        return $this->toPayload() + ['employeeId' => $this->employeeId];
    }
}
