<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Webhook\Output;

use Nxi\Factro\Mapping\Field;

/**
 * A registered webhook (IWebhook) as embedded in a payload.
 *
 * authenticationHeaderValue is deliberately not hydrated: it is the shared secret factro sends
 * with every delivery, and the SDK must not carry it into logs, dumps or serialised DTOs.
 * actionType stays a string; the API's Action enum has around 300 values.
 */
final readonly class Webhook
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $callbackUri,
        public ?string $authenticationHeaderName,
        public string $actionType,
        public \DateTimeImmutable $createdAt,
        public string $mandantId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            id: Field::string($data, 'id', $o),
            employeeId: Field::string($data, 'employeeId', $o),
            callbackUri: Field::string($data, 'callbackUri', $o),
            authenticationHeaderName: Field::nullableString($data, 'authenticationHeaderName', $o),
            actionType: Field::string($data, 'actionType', $o),
            createdAt: Field::isoUtc($data, 'createdAt', $o),
            mandantId: Field::string($data, 'mandantId', $o),
        );
    }
}
