<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;

/**
 * A factro project (IGetProjectPayload). Dates are UTC instants; use CalendarDate::fromFactro() for calendar days.
 */
final readonly class Project
{
    /**
     * @param array<string, mixed> $customFields
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public ProjectState $projectState,
        public ?ProjectPriority $priority,
        public bool $isArchived,
        public bool $isDraft,
        public ?\DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $endDate,
        public ?\DateTimeImmutable $plannedStartDate,
        public ?\DateTimeImmutable $plannedEndDate,
        public \DateTimeImmutable $creationDate,
        public ?\DateTimeImmutable $changeDate,
        public string $creatorId,
        public ?string $officerId,
        public ?string $companyId,
        public ?string $companyContactId,
        public ?float $plannedEffort,
        public ?float $realizedEffort,
        public ?float $remainingEffort,
        public ?int $number,
        public ?string $colorScheme,
        public array $customFields,
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
            title: Field::string($data, 'title', $o),
            description: Field::nullableString($data, 'description', $o),
            projectState: Field::enum($data, 'projectState', $o, ProjectState::class),
            priority: Field::nullableEnum($data, 'priority', $o, ProjectPriority::class),
            isArchived: Field::bool($data, 'isArchived', $o),
            isDraft: Field::bool($data, 'isDraft', $o),
            startDate: Field::nullableIsoUtc($data, 'startDate', $o),
            endDate: Field::nullableIsoUtc($data, 'endDate', $o),
            plannedStartDate: Field::nullableIsoUtc($data, 'plannedStartDate', $o),
            plannedEndDate: Field::nullableIsoUtc($data, 'plannedEndDate', $o),
            creationDate: Field::isoUtc($data, 'creationDate', $o),
            changeDate: Field::nullableIsoUtc($data, 'changeDate', $o),
            creatorId: Field::string($data, 'creatorId', $o),
            officerId: Field::nullableString($data, 'officerId', $o),
            companyId: Field::nullableString($data, 'companyId', $o),
            companyContactId: Field::nullableString($data, 'companyContactId', $o),
            plannedEffort: Field::nullableFloat($data, 'plannedEffort', $o),
            realizedEffort: Field::nullableFloat($data, 'realizedEffort', $o),
            remainingEffort: Field::nullableFloat($data, 'remainingEffort', $o),
            number: Field::nullableInt($data, 'number', $o),
            colorScheme: Field::nullableString($data, 'colorScheme', $o),
            customFields: Field::array($data, 'customFields', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
