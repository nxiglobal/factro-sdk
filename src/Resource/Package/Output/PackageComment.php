<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Package\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A package comment (IGetPackageCommentPayload plus the undocumented subComments list, as with
 * TaskComment). The reference key is taskPackageId; creationDate is ISO, changeDate a JavaScript timestamp.
 */
final readonly class PackageComment
{
    /**
     * @param list<PackageComment> $subComments
     */
    public function __construct(
        public string $id,
        public string $taskPackageId,
        public string $text,
        public string $creatorId,
        public ?string $parentId,
        public \DateTimeImmutable $creationDate,
        public ?\DateTimeImmutable $changeDate,
        public array $subComments,
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
            taskPackageId: Field::string($data, 'taskPackageId', $o),
            text: Field::string($data, 'text', $o),
            creatorId: Field::string($data, 'creatorId', $o),
            parentId: Field::nullableString($data, 'parentId', $o),
            creationDate: Field::isoUtc($data, 'creationDate', $o),
            changeDate: Field::nullableJsTimestamp($data, 'changeDate', $o),
            subComments: Field::objectList($data, 'subComments', $o, self::fromArray(...)),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
