<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Team\Input;

use Nxi\Factro\Mapping\Payload;

/**
 * Partial update for PUT /teams/{id} (IUpdateTeamRequest). Only non-null fields are sent.
 */
final readonly class TeamChanges
{
    public function __construct(
        public ?string $name = null,
        public ?string $color = null,
        public ?bool $isActive = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls([
            'name' => $this->name,
            'color' => $this->color,
            'isActive' => $this->isActive,
        ]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->toPayload();
    }
}
