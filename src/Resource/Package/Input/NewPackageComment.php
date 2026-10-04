<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Package\Input;

use Nxi\Factro\Mapping\Payload;

/**
 * Body of POST /projects/{id}/packages/{pid}/comments (ICreatePackageCommentRequest).
 */
final readonly class NewPackageComment
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
