<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Webhook\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Webhook\Output\Webhook;
use Nxi\Factro\Resource\Webhook\Output\WebhookPayload;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebhookPayload::class)]
final class WebhookPayloadTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('webhook-payloads')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $payload = WebhookPayload::fromArray($row);

        self::assertSame($row['id'], $payload->id);
        self::assertSame($row['webhookId'], $payload->webhookId);
        self::assertSame('TaskStateChanged', $payload->action);
        self::assertSame('delivered', $payload->deliveryStatus);
        self::assertSame(1, $payload->deliveryAttempts);
        self::assertIsString($row['createdAt']);
        self::assertSame($row['createdAt'], FactroDateTime::toIsoUtc($payload->createdAt));
        self::assertSame($row['context'], $payload->context);
        self::assertSame($row['mandantId'], $payload->mandantId);
        self::assertInstanceOf(Webhook::class, $payload->webhook);
        self::assertSame($row['webhookId'], $payload->webhook->id);
    }

    public function testPayloadWithoutWebhookAndDeliveryInfo(): void
    {
        $row = $this->row(1);
        $payload = WebhookPayload::fromArray($row);

        self::assertSame($row['id'], $payload->id);
        self::assertNull($payload->webhookId);
        self::assertNull($payload->deliveryStatus);
        self::assertNull($payload->deliveryAttempts);
        self::assertSame([], $payload->context);
        self::assertNull($payload->webhook);
    }

    public function testActionIsKeptAsStringForUnknownValues(): void
    {
        $row = $this->row();
        $row['action'] = 'SomethingNewFactroAdded';

        self::assertSame('SomethingNewFactroAdded', WebhookPayload::fromArray($row)->action);
    }

    public function testMalformedWebhookThrows(): void
    {
        $row = $this->row();
        $row['webhook'] = 'not-an-object';

        $this->expectException(HydrationException::class);
        WebhookPayload::fromArray($row);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['action', 'createdAt', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        WebhookPayload::fromArray($row);
    }
}
