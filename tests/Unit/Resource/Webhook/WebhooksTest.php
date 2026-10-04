<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Webhook;

use Nxi\Factro\Resource\Webhook\Output\WebhookPayload;
use Nxi\Factro\Resource\Webhook\Webhooks;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Webhooks::class)]
final class WebhooksTest extends TestCase
{
    public function testPayloadsSendsIsoUtcStartDateInPath(): void
    {
        $client = MockFactro::client(['GET /webhook/payload/2026-09-01T00%3A00%3A00.000Z' => static function (string $m, string $u): MockResponse {
            self::assertSame('GET', $m);
            self::assertSame(MockFactro::BASE_URL.'/webhook/payload/2026-09-01T00%3A00%3A00.000Z', $u);

            return JsonMockResponse::fromFile(Fixtures::path('webhook-payloads'));
        }]);

        $payloads = $client->webhooks()->payloads(new \DateTimeImmutable('2026-09-01T00:00:00Z'));

        self::assertCount(2, $payloads);
        self::assertContainsOnlyInstancesOf(WebhookPayload::class, $payloads);
    }

    public function testPayloadsConvertsLocalTimeToUtc(): void
    {
        $client = MockFactro::client(['GET /webhook/payload/2026-08-31T22%3A00%3A00.000Z' => JsonMockResponse::fromFile(Fixtures::path('webhook-payloads'))]);

        $payloads = $client->webhooks()->payloads(new \DateTimeImmutable('2026-09-01 00:00:00', new \DateTimeZone('Europe/Berlin')));

        self::assertCount(2, $payloads);
    }

    public function testPayloadsByActionSendsActionAndStartDateInPath(): void
    {
        $client = MockFactro::client(['GET /webhook/payload/type/TaskStateChanged/2026-09-01T00%3A00%3A00.000Z' => JsonMockResponse::fromFile(Fixtures::path('webhook-payloads'))]);

        $payloads = $client->webhooks()->payloadsByAction('TaskStateChanged', new \DateTimeImmutable('2026-09-01T00:00:00Z'));

        self::assertCount(2, $payloads);
        self::assertSame('TaskStateChanged', $payloads[0]->action);
    }

    public function testEmptyBodyYieldsEmptyList(): void
    {
        $client = MockFactro::client(['GET /webhook/payload/2026-09-01T00%3A00%3A00.000Z' => new MockResponse('[]', ['http_code' => 200])]);

        self::assertSame([], $client->webhooks()->payloads(new \DateTimeImmutable('2026-09-01T00:00:00Z')));
    }
}
