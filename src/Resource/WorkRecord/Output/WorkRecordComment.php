<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A work record comment (IGetWorkRecordCommentPayload). creationDate is ISO, changeDate a JavaScript timestamp.
 */
final readonly class WorkRecordComment
{
    public function __construct(
        public string $id,
        public string $workRecordId,
        public string $text,
        public string $creatorId,
        public ?string $parentId,
        public \DateTimeImmutable $creationDate,
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
            workRecordId: Field::string($data, 'workRecordId', $o),
            text: Field::string($data, 'text', $o),
            creatorId: Field::string($data, 'creatorId', $o),
            parentId: Field::nullableString($data, 'parentId', $o),
            creationDate: Field::isoUtc($data, 'creationDate', $o),
            changeDate: Field::nullableJsTimestamp($data, 'changeDate', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
