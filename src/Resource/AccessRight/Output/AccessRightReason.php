<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\AccessRight\Output;

use Nxi\Factro\Mapping\Field;

/**
 * One entry of GET .../read_rights and .../write_rights (IAccessRightReason).
 *
 * $reason stays a string ("IsProjectOfficer", "HasDirectPackageTeamWriteRight", ...) so that a reason
 * factro adds later does not break hydration. The ids name the element the reason is attached to; which
 * of them is set depends on the reason. roomId and listId are not mapped.
 */
final readonly class AccessRightReason
{
    public function __construct(
        public string $reason,
        public ?string $projectId = null,
        public ?string $packageId = null,
        public ?string $teamId = null,
        public ?string $taskId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            reason: Field::string($data, 'reason', $o),
            projectId: Field::nullableString($data, 'projectId', $o),
            packageId: Field::nullableString($data, 'packageId', $o),
            teamId: Field::nullableString($data, 'teamId', $o),
            taskId: Field::nullableString($data, 'taskId', $o),
        );
    }
}
