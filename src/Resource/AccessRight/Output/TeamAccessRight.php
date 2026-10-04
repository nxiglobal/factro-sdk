<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\AccessRight\Output;

use Nxi\Factro\Mapping\Field;

/**
 * Response of PUT .../team_read_rights and .../team_write_rights (IAdd*RightsForTeamResponse).
 */
final readonly class TeamAccessRight
{
    public function __construct(
        public string $id,
        public string $teamId,
        public string $referenceId,
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
            teamId: Field::string($data, 'teamId', $o),
            referenceId: Field::string($data, 'referenceId', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
