<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Contact\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\User\Salutation;

/**
 * Partial update for PUT /contacts/{id} (IUpdateContactRequest). Only non-null fields are sent.
 * Clearing a field is not supported, because it is undocumented whether factro accepts null for one.
 */
final readonly class ContactChanges
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?Salutation $salutation = null,
        public ?string $description = null,
        public ?string $city = null,
        public ?string $emailAddress = null,
        public ?string $phone = null,
        public ?string $mobilePhone = null,
        public ?string $street = null,
        public ?string $zipCode = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'salutation' => $this->salutation?->value,
            'description' => $this->description,
            'city' => $this->city,
            'emailAddress' => $this->emailAddress,
            'phone' => $this->phone,
            'mobilePhone' => $this->mobilePhone,
            'street' => $this->street,
            'zipCode' => $this->zipCode,
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload();
    }
}
