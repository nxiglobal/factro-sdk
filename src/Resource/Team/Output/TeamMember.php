<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Team\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A team membership (IGetTeamMemberPayload). id is the membership id used by Teams::removeMember(), not the employee id.
 */
final readonly class TeamMember
{
    public function __construct(
        public string $id,
        public string $teamId,
        public string $employeeId,
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
            employeeId: Field::string($data, 'employeeId', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
