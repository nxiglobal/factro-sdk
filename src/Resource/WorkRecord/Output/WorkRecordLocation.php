<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord\Output;

use Nxi\Factro\Mapping\Field;

/**
 * Location of a work record. factro always sends the object, usually with all five fields null.
 */
final readonly class WorkRecordLocation
{
    public function __construct(
        public ?string $name,
        public ?string $street,
        public ?string $city,
        public ?string $zipCode,
        public ?string $country,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            name: Field::nullableString($data, 'name', $o),
            street: Field::nullableString($data, 'street', $o),
            city: Field::nullableString($data, 'city', $o),
            zipCode: Field::nullableString($data, 'zipCode', $o),
            country: Field::nullableString($data, 'country', $o),
        );
    }

    /**
     * All five keys, null allowed, as factro expects them in write bodies.
     *
     * @return array{name: ?string, street: ?string, city: ?string, zipCode: ?string, country: ?string}
     */
    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'street' => $this->street,
            'city' => $this->city,
            'zipCode' => $this->zipCode,
            'country' => $this->country,
        ];
    }
}
