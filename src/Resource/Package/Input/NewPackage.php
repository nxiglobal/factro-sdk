<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Package\Input;

use Nxi\Factro\Mapping\Payload;

/**
 * Body of POST /projects/{id}/packages. A null parentPackageId creates a root package.
 */
final readonly class NewPackage
{
    /**
     * @param array<string, mixed>|null $customFields
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $colorScheme = null,
        public ?string $parentPackageId = null,
        public ?string $officerId = null,
        public ?array $customFields = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'title' => $this->title,
            'description' => $this->description,
            'colorScheme' => $this->colorScheme,
            'parentPackageId' => $this->parentPackageId,
            'officerId' => $this->officerId,
            'customFields' => $this->customFields,
        ]);
    }
}
