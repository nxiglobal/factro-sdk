<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecordLocation;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use Nxi\Factro\Time\CalendarDate;

/**
 * Partial update for PUT /work-records/{id}. Same fields as NewWorkRecord, all optional; only non-null fields are sent.
 */
final readonly class WorkRecordChanges
{
    public function __construct(
        public ?CalendarDate $startDate = null,
        public ?int $minutesWorked = null,
        public ?int $utcOffsetMinutes = null,
        public ?string $bookedOnReferenceId = null,
        public ?WorkRecordReferenceType $bookedOnReferenceType = null,
        public ?string $description = null,
        public ?CalendarDate $endDate = null,
        public ?string $startTime = null,
        public ?string $endTime = null,
        public ?bool $isBillable = null,
        public ?bool $isBilled = null,
        public ?string $internalBookingDetails = null,
        public ?string $externalBookingDetails = null,
        public ?string $workEmployeeId = null,
        public ?float $travelledDistanceKm = null,
        public ?string $createdInContextOfReferenceId = null,
        public ?string $createdInContextOfReferenceType = null,
        public ?float $remainingTaskEffort = null,
        public ?WorkRecordLocation $location = null,
    ) {
        WorkRecordTime::assertValid($startTime, 'startTime');
        WorkRecordTime::assertValid($endTime, 'endTime');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'startDate' => $this->startDate?->toYmd(),
            'minutesWorked' => $this->minutesWorked,
            'utcOffset' => $this->utcOffsetMinutes,
            'bookedOnReferenceId' => $this->bookedOnReferenceId,
            'bookedOnReferenceType' => $this->bookedOnReferenceType?->value,
            'description' => $this->description,
            'endDate' => $this->endDate?->toYmd(),
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'isBillable' => $this->isBillable,
            'isBilled' => $this->isBilled,
            'internalBookingDetails' => $this->internalBookingDetails,
            'externalBookingDetails' => $this->externalBookingDetails,
            'workEmployeeId' => $this->workEmployeeId,
            'travelledDistanceKm' => $this->travelledDistanceKm,
            'createdInContextOfReferenceId' => $this->createdInContextOfReferenceId,
            'createdInContextOfReferenceType' => $this->createdInContextOfReferenceType,
            'remainingTaskEffort' => $this->remainingTaskEffort,
            'location' => $this->location?->toPayload(),
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload();
    }
}
