<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Comment;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Comment\Output\Comment;

/**
 * Comment lookup by id. Listing and creating comments happens on the owning resource
 * (Tasks, Projects, Packages, WorkRecords, Notes).
 */
final class Comments extends AbstractResource
{
    /**
     * GET /comments/{id}.
     */
    public function get(string $commentId): Comment
    {
        return Comment::fromArray($this->object($this->transport->request('GET', '/comments/'.rawurlencode($commentId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $commentId): ?Comment
    {
        try {
            return $this->get($commentId);
        } catch (NotFoundException) {
            return null;
        }
    }
}
