<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Contact\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\User\Salutation;

/**
 * Body of POST /contacts (ICreateContactRequest). firstName and lastName are required; unset fields are omitted.
 * The request has no companyId: a contact is attached to a company via the company or task endpoints.
 */
final readonly class NewContact
{
    public function __construct(
        public string $firstName,
        public string $lastName,
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
        return ['firstName' => $this->firstName, 'lastName' => $this->lastName] + Payload::withoutNulls([
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
}
