<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Resource\User\SecurityGroup;

/**
 * A factro user (IGetUserPayload). The list endpoint omits address fields and fallbackImageText.
 */
final readonly class User
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $emailAddress,
        public SecurityGroup $securityGroup,
        public bool $isActive,
        public bool $anonymized,
        public ?int $employeeNumber,
        public ?string $accountId,
        public ?Salutation $salutation,
        public ?string $street,
        public ?string $zipCode,
        public ?string $city,
        public ?string $fallbackImageText,
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
            firstName: Field::string($data, 'firstName', $o),
            lastName: Field::string($data, 'lastName', $o),
            emailAddress: Field::string($data, 'emailAddress', $o),
            securityGroup: Field::enum($data, 'securityGroup', $o, SecurityGroup::class),
            isActive: Field::bool($data, 'isActive', $o),
            anonymized: Field::bool($data, 'anonymized', $o),
            employeeNumber: Field::nullableInt($data, 'employeeNumber', $o),
            accountId: Field::nullableString($data, 'accountId', $o),
            salutation: Field::nullableEnum($data, 'salutation', $o, Salutation::class),
            street: Field::nullableString($data, 'street', $o),
            zipCode: Field::nullableString($data, 'zipCode', $o),
            city: Field::nullableString($data, 'city', $o),
            fallbackImageText: Field::nullableString($data, 'fallbackImageText', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }

    public function displayName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
