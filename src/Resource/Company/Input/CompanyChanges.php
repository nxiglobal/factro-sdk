<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Company\Input;

use Nxi\Factro\Mapping\Payload;

/**
 * Partial update for PUT /companies/{id} (IUpdateCompanyRequest). Only non-null fields are sent.
 * Clearing a field is not supported, because it is undocumented whether factro accepts null for one.
 */
final readonly class CompanyChanges
{
    public function __construct(
        public ?string $name = null,
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
        return Payload::withoutNulls([
            'name' => $this->name,
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

    public function isEmpty(): bool
    {
        return [] === $this->toPayload();
    }
}
