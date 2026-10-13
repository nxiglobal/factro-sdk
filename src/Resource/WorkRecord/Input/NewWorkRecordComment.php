<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord\Input;

use Nxi\Factro\Mapping\Payload;

/**
 * Body of POST /work-records/{id}/comments (ICreateWorkRecordCommentRequest).
 */
final readonly class NewWorkRecordComment
{
    public function __construct(public string $text, public ?string $parentCommentId = null)
    {
    }

    /**
     * @internal builds the request body; the format follows the factro API and may change in minor releases
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return Payload::withoutNulls(['text' => $this->text, 'parentCommentId' => $this->parentCommentId]);
    }
}
