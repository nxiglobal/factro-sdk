<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Package\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A factro package (IGetPackagePayload). parentPackageId is null for root packages.
 */
final readonly class Package
{
    /**
     * @param array<string, mixed> $customFields
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public string $projectId,
        public ?string $parentPackageId,
        public ?\DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $endDate,
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
            projectId: Field::string($data, 'projectId', $o),
            parentPackageId: Field::nullableString($data, 'parentPackageId', $o),
            startDate: Field::nullableIsoUtc($data, 'startDate', $o),
            endDate: Field::nullableIsoUtc($data, 'endDate', $o),
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
