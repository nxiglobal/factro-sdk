<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Note\Input;

use Nxi\Factro\Mapping\Payload;

/**
 * Body of POST /note/{id}/comments (ICreateNoteCommentRequest).
 */
final readonly class NewNoteComment
{
    public function __construct(public string $text, public ?string $parentCommentId = null)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls(['text' => $this->text, 'parentCommentId' => $this->parentCommentId]);
    }
}
