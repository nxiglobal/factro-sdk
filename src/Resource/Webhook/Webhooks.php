<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Webhook;

use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Webhook\Output\WebhookPayload;
use Nxi\Factro\Time\FactroDateTime;

/**
 * Read access to the payloads factro delivered to the API user's webhooks.
 *
 * The OpenAPI document declares startDate as a plain string without a format. Sending it as
 * ISO-8601 UTC ("2026-09-01T00:00:00.000Z") is an assumption derived from the other date fields.
 */
final class Webhooks extends AbstractResource
{
    /**
     * GET /webhook/payload/{startDate}. Payloads of every webhook executed since $since.
     *
     * @return list<WebhookPayload>
     */
    public function payloads(\DateTimeImmutable $since): array
    {
        return array_map(WebhookPayload::fromArray(...), $this->rows($this->transport->request('GET', '/webhook/payload/'.$this->startDate($since))));
    }

    /**
     * GET /webhook/payload/type/{actionType}/{startDate}. Payloads of one action type (e.g. "TaskStateChanged") since $since.
     *
     * @return list<WebhookPayload>
     */
    public function payloadsByAction(string $action, \DateTimeImmutable $since): array
    {
        return array_map(WebhookPayload::fromArray(...), $this->rows($this->transport->request('GET', '/webhook/payload/type/'.rawurlencode($action).'/'.$this->startDate($since))));
    }

    private function startDate(\DateTimeImmutable $since): string
    {
        return rawurlencode(FactroDateTime::toIsoUtc($since));
    }
}
