<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Team\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A team (IGetTeamPayload).
 */
final readonly class Team
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $color,
        public bool $isActive,
        public string $mandantId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            id: Field::string($data, 'id', $o),
            name: Field::string($data, 'name', $o),
            color: Field::nullableString($data, 'color', $o),
            isActive: Field::bool($data, 'isActive', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
