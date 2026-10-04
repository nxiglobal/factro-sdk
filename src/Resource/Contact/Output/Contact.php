<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Contact\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\User\Salutation;

/**
 * A company contact (IGetContactPayload). Address keys are missing in most rows and hydrate to null.
 */
final readonly class Contact
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public ?Salutation $salutation,
        public ?string $companyId,
        public ?string $description,
        public ?string $city,
        public ?string $emailAddress,
        public ?string $phone,
        public ?string $mobilePhone,
        public ?string $street,
        public ?string $zipCode,
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
            firstName: Field::string($data, 'firstName', $o),
            lastName: Field::string($data, 'lastName', $o),
            salutation: Field::nullableEnum($data, 'salutation', $o, Salutation::class),
            companyId: Field::nullableString($data, 'companyId', $o),
            description: Field::nullableString($data, 'description', $o),
            city: Field::nullableString($data, 'city', $o),
            emailAddress: Field::nullableString($data, 'emailAddress', $o),
            phone: Field::nullableString($data, 'phone', $o),
            mobilePhone: Field::nullableString($data, 'mobilePhone', $o),
            street: Field::nullableString($data, 'street', $o),
            zipCode: Field::nullableString($data, 'zipCode', $o),
        );
    }

    public function displayName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
