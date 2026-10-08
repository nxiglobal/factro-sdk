<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Package\Input;

use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Time\CalendarDate;

/**
 * Partial update for PUT /projects/{id}/packages/{pid}. Only non-null fields are sent; fields named in $clear are
 * sent as explicit null, which empties them in factro.
 */
final readonly class PackageChanges
{
    /**
     * @param array<string, mixed>|null $customFields
     * @param list<string>              $clear        payload keys to send as null, e.g. ['colorScheme']
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?CalendarDate $startDate = null,
        public ?CalendarDate $endDate = null,
        public ?string $colorScheme = null,
        public ?string $officerId = null,
        public ?array $customFields = null,
        public array $clear = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(\DateTimeZone $timezone): array
    {
        return Payload::withoutNullsExcept([
            'title' => $this->title,
            'description' => $this->description,
            'startDate' => $this->startDate?->toFactro($timezone),
            'endDate' => $this->endDate?->toFactro($timezone),
            'colorScheme' => $this->colorScheme,
            'officerId' => $this->officerId,
            'customFields' => $this->customFields,
        ], $this->clear);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload(new \DateTimeZone('UTC'));
    }
}
