<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Team\Input;

/**
 * Body of POST /teams (ICreateTeamRequest). isActive is always sent so that the default is explicit.
 */
final readonly class NewTeam
{
    public function __construct(
        public string $name,
        public string $color,
        public bool $isActive = true,
    ) {
    }

    /**
     * @internal builds the request body; the format follows the factro API and may change in minor releases
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'color' => $this->color,
            'isActive' => $this->isActive,
        ];
    }
}
