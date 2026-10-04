<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Webhook\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Webhook\Output\Webhook;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Webhook::class)]
final class WebhookTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(): array
    {
        $payload = Fixtures::json('webhook-payloads')[0];
        self::assertIsArray($payload);
        $row = $payload['webhook'];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $webhook = Webhook::fromArray($row);

        self::assertSame($row['id'], $webhook->id);
        self::assertSame($row['employeeId'], $webhook->employeeId);
        self::assertSame('https://hooks.example.test/factro', $webhook->callbackUri);
        self::assertSame('X-Factro-Secret', $webhook->authenticationHeaderName);
        self::assertSame('TaskStateChanged', $webhook->actionType);
        self::assertIsString($row['createdAt']);
        self::assertSame($row['createdAt'], FactroDateTime::toIsoUtc($webhook->createdAt));
        self::assertSame($row['mandantId'], $webhook->mandantId);
    }

    public function testAuthenticationHeaderValueIsNeverHydrated(): void
    {
        $row = $this->row();
        self::assertArrayHasKey('authenticationHeaderValue', $row);

        $webhook = Webhook::fromArray($row);

        self::assertFalse(new \ReflectionClass($webhook)->hasProperty('authenticationHeaderValue'));
        self::assertStringNotContainsString('REDACTED', json_encode($webhook, JSON_THROW_ON_ERROR));
    }

    public function testAuthenticationHeaderNameIsOptional(): void
    {
        $row = $this->row();
        unset($row['authenticationHeaderName']);

        self::assertNull(Webhook::fromArray($row)->authenticationHeaderName);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'employeeId', 'callbackUri', 'actionType', 'createdAt', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Webhook::fromArray($row);
    }
}
