<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\AccessRight\Output;

use Nxi\Factro\Mapping\Field;

/**
 * Response of PUT .../read_rights and .../write_rights for an employee (IAdd*RightsForUserResponse).
 */
final readonly class EmployeeAccessRight
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $referenceId,
        public bool $canEdit,
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
            employeeId: Field::string($data, 'employeeId', $o),
            referenceId: Field::string($data, 'referenceId', $o),
            canEdit: Field::bool($data, 'canEdit', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
