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
    }
}
