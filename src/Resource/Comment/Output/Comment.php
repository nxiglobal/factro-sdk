<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Comment\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\Comment\CommentReferenceType;

/**
 * A comment looked up by id regardless of its parent object (IGetCommentResponse).
 * creationDate is ISO, changeDate a JavaScript timestamp.
 */
final readonly class Comment
{
    public function __construct(
        public string $id,
        public string $referenceId,
        public CommentReferenceType $referenceType,
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
            referenceId: Field::string($data, 'referenceId', $o),
            referenceType: Field::enum($data, 'referenceType', $o, CommentReferenceType::class),
            text: Field::string($data, 'text', $o),
            creatorId: Field::string($data, 'creatorId', $o),
            parentId: Field::nullableString($data, 'parentId', $o),
            creationDate: Field::isoUtc($data, 'creationDate', $o),
            changeDate: Field::nullableJsTimestamp($data, 'changeDate', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
