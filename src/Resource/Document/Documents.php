<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Document;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Document\Output\DataQuota;
use Nxi\Factro\Resource\Document\Output\Document;

/**
 * Documents across the tenant. Uploading and detaching happens on the owning resource
 * (Tasks, Packages, Projects: documents(), addDocument(), removeDocument()).
 */
final class Documents extends AbstractResource
{
    /**
     * GET /documents: every document visible to the token's user.
     *
     * @return list<Document>
     */
    public function list(): array
    {
        return array_map(Document::fromArray(...), $this->rows($this->transport->request('GET', '/documents')));
    }

    /**
     * GET /documents/{id}.
     */
    public function get(string $documentId): Document
    {
        return Document::fromArray($this->object($this->transport->request('GET', '/documents/'.rawurlencode($documentId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $documentId): ?Document
    {
        try {
            return $this->get($documentId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * GET /documents/quota.
     */
    public function quota(): DataQuota
    {
        return DataQuota::fromArray($this->object($this->transport->request('GET', '/documents/quota')));
    }
}
