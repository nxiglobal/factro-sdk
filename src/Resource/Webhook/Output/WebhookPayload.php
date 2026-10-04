<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Webhook\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A delivered webhook payload (IWebhookPayloadPayload). action stays a string: the API's Action
 * enum has around 300 values and grows with every factro release. context is the event-specific
 * body and is passed through untouched.
 */
final readonly class WebhookPayload
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public ?string $id,
        public ?string $webhookId,
        public string $action,
        public ?string $deliveryStatus,
        public ?int $deliveryAttempts,
        public \DateTimeImmutable $createdAt,
        public array $context,
        public string $mandantId,
        public ?Webhook $webhook,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;
        // A missing or null webhook yields []; an empty object is treated the same way.
        $webhook = Field::array($data, 'webhook', $o);

        return new self(
            id: Field::nullableString($data, 'id', $o),
            webhookId: Field::nullableString($data, 'webhookId', $o),
            action: Field::string($data, 'action', $o),
            deliveryStatus: Field::nullableString($data, 'deliveryStatus', $o),
            deliveryAttempts: Field::nullableInt($data, 'deliveryAttempts', $o),
            createdAt: Field::isoUtc($data, 'createdAt', $o),
            context: Field::array($data, 'context', $o),
            mandantId: Field::string($data, 'mandantId', $o),
            webhook: [] === $webhook ? null : Webhook::fromArray($webhook),
        );
    }
}
