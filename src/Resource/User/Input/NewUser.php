<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Resource\User\SecurityGroup;

/**
 * Body of POST /users (ICreateUserRequest); also an element of POST /users/users.
 */
final readonly class NewUser
{
    public function __construct(
        public string $emailAddress,
        public string $firstName,
        public string $lastName,
        public SecurityGroup $securityGroup,
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
        return [
            'emailAddress' => $this->emailAddress,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'securityGroup' => $this->securityGroup->value,
        ] + Payload::withoutNulls([
            'city' => $this->city,
            'street' => $this->street,
            'zipCode' => $this->zipCode,
            'salutation' => $this->salutation?->value,
        ]);
    }
}
