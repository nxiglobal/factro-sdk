<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Company\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A company (IGetCompanyPayload). Address keys are missing in most rows and hydrate to null.
 */
final readonly class Company
{
    /**
     * @param list<string> $contactIds
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $shortName,
        public ?string $description,
        public ?int $companyNumber,
        public ?string $customerId,
        public ?string $emailAddress,
        public ?string $phone,
        public ?string $website,
        public ?string $street,
        public ?string $zipCode,
        public ?string $city,
        public array $contactIds,
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
            name: Field::string($data, 'name', $o),
            shortName: Field::nullableString($data, 'shortName', $o),
            description: Field::nullableString($data, 'description', $o),
            companyNumber: Field::nullableInt($data, 'companyNumber', $o),
            customerId: Field::nullableString($data, 'customerId', $o),
            emailAddress: Field::nullableString($data, 'emailAddress', $o),
            phone: Field::nullableString($data, 'phone', $o),
            website: Field::nullableString($data, 'website', $o),
            street: Field::nullableString($data, 'street', $o),
            zipCode: Field::nullableString($data, 'zipCode', $o),
            city: Field::nullableString($data, 'city', $o),
            contactIds: Field::stringList($data, 'contactIds', $o),
        );
    }
}
