<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Resource\User\SecurityGroup;

/**
 * Partial update for PUT /users/{id} (IUpdateUserRequest); also an element of PUT /users/users with its id.
 * Only non-null fields are sent.
 */
final readonly class UserChanges
{
    public function __construct(
        public ?string $emailAddress = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?SecurityGroup $securityGroup = null,
        public ?string $city = null,
        public ?string $street = null,
        public ?string $zipCode = null,
        public ?Salutation $salutation = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'emailAddress' => $this->emailAddress,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'city' => $this->city,
            'street' => $this->street,
            'zipCode' => $this->zipCode,
            'salutation' => $this->salutation?->value,
            'securityGroup' => $this->securityGroup?->value,
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload();
    }
}
