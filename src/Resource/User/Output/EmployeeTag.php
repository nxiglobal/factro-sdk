<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User\Output;

use Nxi\Factro\Mapping\Field;

/**
 * An employee tag (IGetEmployeeTagPayload); same shape for GET /users/tags and GET /users/{id}/tags.
 */
final readonly class EmployeeTag
{
    public function __construct(
        public string $id,
        public string $name,
        public ?\DateTimeImmutable $changeDate,
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
            changeDate: Field::nullableIsoUtc($data, 'changeDate', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
