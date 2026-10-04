<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Note;

use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Note\Input\NewNoteComment;
use Nxi\Factro\Resource\Note\Output\NoteComment;

/**
 * Comments on notes. The Core API exposes no other note operations; the path segment is the singular "note".
 */
final class Notes extends AbstractResource
{
    /**
     * GET /note/{id}/comments.
     *
     * @return list<NoteComment>
     */
    public function comments(string $noteId): array
    {
        return array_map(NoteComment::fromArray(...), $this->rows($this->transport->request('GET', '/note/'.rawurlencode($noteId).'/comments')));
    }

    /**
     * GET /note/{id}/comments/{commentId}.
     */
    public function comment(string $noteId, string $commentId): NoteComment
    {
        return NoteComment::fromArray($this->object($this->transport->request('GET', '/note/'.rawurlencode($noteId).'/comments/'.rawurlencode($commentId))));
    }

    /**
     * POST /note/{id}/comments.
     */
    public function addComment(string $noteId, NewNoteComment $comment): NoteComment
    {
        return NoteComment::fromArray($this->object($this->transport->request('POST', '/note/'.rawurlencode($noteId).'/comments', json: $comment->toPayload())));
    }
}
