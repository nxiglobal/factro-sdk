<?php

declare(strict_types=1);

namespace Nxi\Factro;

use Nxi\Factro\Http\FactroRetryStrategy;
use Nxi\Factro\Http\PolicyHttpClient;
use Nxi\Factro\Http\RateLimitHttpClient;
use Nxi\Factro\Http\Transport;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Builds the decorator chain around any HttpClientInterface:
 * Transport -> PolicyHttpClient -> RetryableHttpClient -> RateLimitHttpClient -> ScopingHttpClient -> base client.
 */
final class FactroClientFactory
{
    private function __construct()
    {
    }

    public static function create(
        #[\SensitiveParameter] string $token,
        ?FactroOptions $options = null,
        ?HttpClientInterface $httpClient = null,
    ): FactroClient {
        $options ??= new FactroOptions();

        $base = $httpClient ?? HttpClient::create();
        if (null === $httpClient && $base instanceof LoggerAwareInterface) {
            $base->setLogger($options->logger);
        }
        // A trailing slash keeps the base path when relative paths are resolved (see docs/DECISIONS.md).
        $scoped = ScopingHttpClient::forBaseUri($base, rtrim($options->baseUrl, '/').'/', [
            'headers' => [
                // Raw token, no "Bearer" prefix: factro answers HTTP 500 otherwise.
                'Authorization' => $token,
                'Accept' => 'application/json',
                'User-Agent' => $options->userAgent,
            ],
            'timeout' => $options->inactivityTimeoutSeconds,
            'max_duration' => $options->requestTimeoutSeconds,
        ]);
        $limiter = ($options->limiterFactory ?? self::defaultLimiterFactory())->create(hash('sha256', $token));
        $limited = new RateLimitHttpClient($scoped, $limiter);
        $retrying = new RetryableHttpClient(
            $limited,
            new FactroRetryStrategy(maxRetryAfterSeconds: $options->maxRetryAfterSeconds),
            $options->maxRetries,
            $options->logger,
        );
        $guarded = new PolicyHttpClient($retrying, $options->policy);

        return new FactroClient(new Transport($guarded, $options), $options);
    }

    /**
     * Sliding window of 480 requests per minute per token, slightly below factro's limit of 500.
     * Consumers running several PHP processes per token should pass a factory backed by CacheStorage.
     */
    public static function defaultLimiterFactory(): RateLimiterFactoryInterface
    {
        return new RateLimiterFactory(
            ['id' => 'factro', 'policy' => 'sliding_window', 'limit' => 480, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
    }
}
