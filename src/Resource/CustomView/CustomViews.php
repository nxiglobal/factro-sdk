<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\CustomView;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\CustomView\Input\CustomViewChanges;
use Nxi\Factro\Resource\CustomView\Input\NewCustomView;
use Nxi\Factro\Resource\CustomView\Output\CustomView;

final class CustomViews extends AbstractResource
{
    /**
     * GET /custom-views/templates. All templates a view on a reference can be derived from.
     *
     * @return list<CustomView>
     */
    public function templates(): array
    {
        return array_map(CustomView::fromArray(...), $this->rows($this->transport->request('GET', '/custom-views/templates')));
    }

    /**
     * GET /custom-views/by-reference/{referenceId}. The views of one project or room.
     *
     * @return list<CustomView>
     */
    public function listByReference(string $referenceId): array
    {
        return array_map(CustomView::fromArray(...), $this->rows($this->transport->request('GET', '/custom-views/by-reference/'.rawurlencode($referenceId))));
    }

    /**
     * GET /custom-views/{customViewId}.
     */
    public function get(string $customViewId): CustomView
    {
        return CustomView::fromArray($this->object($this->transport->request('GET', '/custom-views/'.rawurlencode($customViewId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $customViewId): ?CustomView
    {
        try {
            return $this->get($customViewId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * POST /custom-views. Creates a view on a reference based on a template.
     */
    public function create(NewCustomView $view): CustomView
    {
        return CustomView::fromArray($this->object($this->transport->request('POST', '/custom-views', json: $view->toPayload())));
    }

    /**
     * POST /custom-views/template/{sourceTemplateId}. Creates a new template from an existing one.
     */
    public function createTemplate(string $sourceTemplateId, string $title, string $description): CustomView
    {
        return CustomView::fromArray($this->object($this->transport->request(
            'POST',
            '/custom-views/template/'.rawurlencode($sourceTemplateId),
            json: ['title' => $title, 'description' => $description],
        )));
    }

    /**
     * PUT /custom-views/{customViewId}.
     */
    public function update(string $customViewId, CustomViewChanges $changes): CustomView
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('CustomViewChanges must set at least one field.');
        }

        return CustomView::fromArray($this->object($this->transport->request('PUT', '/custom-views/'.rawurlencode($customViewId), json: $changes->toPayload())));
    }

    /**
     * DELETE /custom-views/{customViewId}. The deleted view in the response is discarded. Requires a RequestPolicy that permits DELETE.
     */
    public function delete(string $customViewId): void
    {
        $this->transport->request('DELETE', '/custom-views/'.rawurlencode($customViewId));
    }

    /**
     * PUT /custom-views/{referenceId}/{customViewId}/position/{position}. Reorders a view within its reference; no body.
     */
    public function setPosition(string $referenceId, string $customViewId, int $position): CustomView
    {
        return CustomView::fromArray($this->object($this->transport->request(
            'PUT',
            '/custom-views/'.rawurlencode($referenceId).'/'.rawurlencode($customViewId).'/position/'.$position,
        )));
    }
}
