<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Document\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A document attached to a task, package or project (IDocumentPayload). referenceId is the id of
 * the element the document is attached to; size is in bytes.
 */
final readonly class Document
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $contentType,
        public ?int $size,
        public ?string $referenceId,
        public ?string $creatorId,
        public ?\DateTimeImmutable $creationDate,
        public ?\DateTimeImmutable $changeDate,
        public ?string $mandantId,
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
            title: Field::string($data, 'title', $o),
            contentType: Field::nullableString($data, 'contentType', $o),
            size: Field::nullableInt($data, 'size', $o),
            referenceId: Field::nullableString($data, 'referenceId', $o),
            creatorId: Field::nullableString($data, 'creatorId', $o),
            creationDate: Field::nullableIsoUtc($data, 'creationDate', $o),
            changeDate: Field::nullableIsoUtc($data, 'changeDate', $o),
            mandantId: Field::nullableString($data, 'mandantId', $o),
        );
    }
}
