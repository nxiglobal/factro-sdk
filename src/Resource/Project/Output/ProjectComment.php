<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A project comment (IGetProjectCommentPayload plus the subComments list that task comments carry).
 * creationDate is ISO, changeDate a JavaScript timestamp.
 */
final readonly class ProjectComment
{
    /**
     * @param list<ProjectComment> $subComments
     */
    public function __construct(
        public string $id,
        public string $projectId,
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
            projectId: Field::string($data, 'projectId', $o),
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
