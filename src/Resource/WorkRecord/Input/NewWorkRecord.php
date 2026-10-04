<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecordLocation;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use Nxi\Factro\Time\CalendarDate;

/**
 * Body of POST /work-records. Dates are sent as Y-m-d, the offset as "utcOffset" in minutes.
 */
final readonly class NewWorkRecord
{
    public function __construct(
        public CalendarDate $startDate,
        public int $minutesWorked,
        public int $utcOffsetMinutes,
        public string $bookedOnReferenceId,
        public WorkRecordReferenceType $bookedOnReferenceType,
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
        return [
            'startDate' => $this->startDate->toYmd(),
            'minutesWorked' => $this->minutesWorked,
            'utcOffset' => $this->utcOffsetMinutes,
            'bookedOnReferenceId' => $this->bookedOnReferenceId,
            'bookedOnReferenceType' => $this->bookedOnReferenceType->value,
        ] + Payload::withoutNulls([
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
}
