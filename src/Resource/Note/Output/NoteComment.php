<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Note\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A note comment (IGetNoteCommentPayload). creationDate is ISO, changeDate a JavaScript timestamp.
 */
final readonly class NoteComment
{
    public function __construct(
        public string $id,
        public string $noteId,
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
            noteId: Field::string($data, 'noteId', $o),
            text: Field::string($data, 'text', $o),
            creatorId: Field::string($data, 'creatorId', $o),
            parentId: Field::nullableString($data, 'parentId', $o),
            creationDate: Field::isoUtc($data, 'creationDate', $o),
            changeDate: Field::nullableJsTimestamp($data, 'changeDate', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
