<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Company\Input;

use Nxi\Factro\Mapping\Payload;

/**
 * Body of POST /companies (ICreateCompanyRequest). Only name is required; unset fields are omitted.
 */
final readonly class NewCompany
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $city = null,
        public ?string $emailAddress = null,
        public ?string $phone = null,
        public ?string $shortName = null,
        public ?string $street = null,
        public ?string $website = null,
        public ?string $zipCode = null,
        public ?string $customerId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return ['name' => $this->name] + Payload::withoutNulls([
            'description' => $this->description,
            'city' => $this->city,
            'emailAddress' => $this->emailAddress,
            'phone' => $this->phone,
            'shortName' => $this->shortName,
            'street' => $this->street,
            'website' => $this->website,
            'zipCode' => $this->zipCode,
            'customerId' => $this->customerId,
        ]);
    }
}
