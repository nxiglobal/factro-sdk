<?php

declare(strict_types=1);

namespace Nxi\Factro\Http;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Blocks until the limiter grants a token, then delegates.
 *
 * Unlike ThrottlingHttpClient this does not rely on the "pause_handler" info item,
 * which MockResponse does not provide, so the behaviour is testable with MockHttpClient.
 *
 * @internal
 */
final class RateLimitHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait {
        reset as private traitReset;
    }

    public function __construct(HttpClientInterface $client, private readonly LimiterInterface $limiter)
    {
        $this->client = $client;
    }

    /**
     * @param array<mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->limiter->reserve(1)->wait();

        return $this->client->request($method, $url, $options);
    }

    public function reset(): void
    {
        $this->traitReset();
        $this->limiter->reset();
    }
}
