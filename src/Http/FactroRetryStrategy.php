<?php

declare(strict_types=1);

namespace Nxi\Factro\Http;

use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Retries GET requests only (network errors, 429 and 5xx) and gives up on a 429 whose
 * Retry-After exceeds the configured cap instead of blocking the caller.
 *
 * @internal
 */
final readonly class FactroRetryStrategy implements RetryStrategyInterface
{
    private const array GET_ONLY = ['GET'];

    private GenericRetryStrategy $inner;

    public function __construct(
        private int $maxRetryAfterSeconds = 5,
        int $delayMs = 500,
        float $multiplier = 2.0,
        int $maxDelayMs = 5000,
        float $jitter = 0.1,
    ) {
        $this->inner = new GenericRetryStrategy(
            [0 => self::GET_ONLY, 429 => self::GET_ONLY, 500 => self::GET_ONLY, 502 => self::GET_ONLY, 503 => self::GET_ONLY, 504 => self::GET_ONLY],
            $delayMs,
            $multiplier,
            $maxDelayMs,
            $jitter,
        );
    }

    public function shouldRetry(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): ?bool
    {
        $decision = $this->inner->shouldRetry($context, $responseContent, $exception);
        if (true !== $decision || 429 !== $context->getStatusCode()) {
            return $decision;
        }
        $after = RateLimitHeaders::retryAfterSeconds($context->getHeaders());

        return null === $after || $after <= $this->maxRetryAfterSeconds;
    }

    public function getDelay(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): int
    {
        return $this->inner->getDelay($context, $responseContent, $exception);
    }
}
