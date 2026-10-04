<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use Nxi\Factro\Time\CalendarDate;
use Nxi\Factro\Time\FactroDateTime;

/**
 * A work record (IGetWorkRecordPayload). Dates are Y-m-d, times HH:MM, utcOffset in minutes,
 * createdAt/updatedAt JavaScript timestamps.
 */
final readonly class WorkRecord
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $workEmployeeId,
        public ?string $creatorEmployeeId,
        public CalendarDate $startDate,
        public ?string $startTime,
        public ?CalendarDate $endDate,
        public ?string $endTime,
        public ?int $utcOffsetMinutes,
        public int $minutesWorked,
        public bool $isBillable,
        public bool $isBilled,
        public ?string $internalBookingDetails,
        public ?string $externalBookingDetails,
        public string $bookedOnReferenceId,
        public WorkRecordReferenceType $bookedOnReferenceType,
        public ?string $createdInContextOfReferenceId,
        public ?string $createdInContextOfReferenceType,
        public ?string $createdInContextOfReferenceTitle,
        public ?float $travelledDistanceKm,
        public ?WorkRecordLocation $location,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public string $mandantId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;
        $location = Field::array($data, 'location', $o, []);

        return new self(
            id: Field::string($data, 'id', $o),
            title: Field::string($data, 'title', $o),
            description: Field::nullableString($data, 'description', $o),
            workEmployeeId: Field::string($data, 'workEmployeeId', $o),
            creatorEmployeeId: Field::nullableString($data, 'creatorEmployeeId', $o),
            startDate: Field::ymd($data, 'startDate', $o),
            startTime: Field::nullableString($data, 'startTime', $o),
            endDate: Field::nullableYmd($data, 'endDate', $o),
            endTime: Field::nullableString($data, 'endTime', $o),
            utcOffsetMinutes: Field::nullableInt($data, 'utcOffset', $o),
            minutesWorked: Field::int($data, 'minutesWorked', $o),
            isBillable: Field::bool($data, 'isBillable', $o),
            isBilled: Field::bool($data, 'isBilled', $o),
            internalBookingDetails: Field::nullableString($data, 'internalBookingDetails', $o),
            externalBookingDetails: Field::nullableString($data, 'externalBookingDetails', $o),
            bookedOnReferenceId: Field::string($data, 'bookedOnReferenceId', $o),
            bookedOnReferenceType: Field::enum($data, 'bookedOnReferenceType', $o, WorkRecordReferenceType::class),
            createdInContextOfReferenceId: Field::nullableString($data, 'createdInContextOfReferenceId', $o),
            createdInContextOfReferenceType: Field::nullableString($data, 'createdInContextOfReferenceType', $o),
            createdInContextOfReferenceTitle: Field::nullableString($data, 'createdInContextOfReferenceTitle', $o),
            travelledDistanceKm: Field::nullableFloat($data, 'travelledDistanceKm', $o),
            location: null === ($data['location'] ?? null) ? null : WorkRecordLocation::fromArray($location),
            createdAt: Field::jsTimestamp($data, 'createdAt', $o),
            updatedAt: Field::nullableJsTimestamp($data, 'updatedAt', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }

    /**
     * Start instant: startDate plus startTime (midnight when missing) in the record's UTC offset, else in $fallback.
     */
    public function startsAt(\DateTimeZone $fallback): \DateTimeImmutable
    {
        return FactroDateTime::fromDateAndTime($this->startDate, $this->startTime, $this->utcOffsetMinutes, $fallback);
    }

    /**
     * End instant, null when the record has no endDate.
     */
    public function endsAt(\DateTimeZone $fallback): ?\DateTimeImmutable
    {
        return null === $this->endDate ? null : FactroDateTime::fromDateAndTime($this->endDate, $this->endTime, $this->utcOffsetMinutes, $fallback);
    }
}
