<?php

declare(strict_types=1);

namespace Nxi\Factro;

use Nxi\Factro\Policy\RequestPolicy;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Immutable configuration of a FactroClient. Every value has a production-ready default.
 */
final readonly class FactroOptions
{
    /**
     * @param float                                $requestTimeoutSeconds    maps to the http-client option "max_duration"
     * @param float                                $inactivityTimeoutSeconds maps to the http-client option "timeout"
     * @param (\Closure(RateLimitInfo): void)|null $onRateLimit              called after every response that carries rate-limit headers
     * @param bool                                 $sendCreationDate         whether NewTask sends "creationDate"; the API's handling of it is undocumented
     *
     * @throws \InvalidArgumentException for a base URL without https and host, non-positive timeouts, negative retry values or a user agent with line breaks
     */
    public function __construct(
        public string $baseUrl = 'https://cloud.factro.com/api/core',
        public float $requestTimeoutSeconds = 20.0,
        public float $inactivityTimeoutSeconds = 10.0,
        public \DateTimeZone $timezone = new \DateTimeZone('Europe/Berlin'),
        public RequestPolicy $policy = new RequestPolicy(),
        public ?RateLimiterFactoryInterface $limiterFactory = null,
        public int $maxRetries = 3,
        public int $maxRetryAfterSeconds = 5,
        public LoggerInterface $logger = new NullLogger(),
        public ClockInterface $clock = new NativeClock(),
        public ?\Closure $onRateLimit = null,
        public string $userAgent = 'nxi-factro-sdk/'.Version::CURRENT,
        public bool $sendCreationDate = true,
    ) {
        // The token travels in a header, plain http would expose it
        if ('https' !== strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)) || '' === (string) parse_url($baseUrl, PHP_URL_HOST)) {
            throw new \InvalidArgumentException(sprintf('Option "baseUrl" must be an absolute https URL, got "%s".', $baseUrl));
        }
        if ($requestTimeoutSeconds <= 0) {
            throw new \InvalidArgumentException(sprintf('Option "requestTimeoutSeconds" must be greater than 0, got %s.', $requestTimeoutSeconds));
        }
        if ($inactivityTimeoutSeconds <= 0) {
            throw new \InvalidArgumentException(sprintf('Option "inactivityTimeoutSeconds" must be greater than 0, got %s.', $inactivityTimeoutSeconds));
        }
        if ($maxRetries < 0) {
            throw new \InvalidArgumentException(sprintf('Option "maxRetries" must not be negative, got %d.', $maxRetries));
        }
        if ($maxRetryAfterSeconds < 0) {
            throw new \InvalidArgumentException(sprintf('Option "maxRetryAfterSeconds" must not be negative, got %d.', $maxRetryAfterSeconds));
        }
        if (false !== strpbrk($userAgent, "\r\n")) {
            throw new \InvalidArgumentException('Option "userAgent" must not contain line breaks.');
        }
    }
}
