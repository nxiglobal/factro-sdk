<?php

declare(strict_types=1);

namespace Nxi\Factro\Http;

use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Rejects requests the RequestPolicy does not permit before they reach the network.
 *
 * @internal
 */
final class PolicyHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait;

    public function __construct(HttpClientInterface $client, private readonly RequestPolicy $policy)
    {
        $this->client = $client;
    }

    /**
     * @param array<mixed> $options
     *
     * @throws OperationNotPermittedException
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // The Transport sends paths without a leading slash so that base_uri keeps its own path
        // (see docs/DECISIONS.md); the policy contract uses paths with a leading slash.
        $path = '/'.ltrim((string) strtok($url, '?'), '/');
        if (!$this->policy->permits($method, $path)) {
            throw new OperationNotPermittedException(strtoupper($method), $path);
        }

        return $this->client->request($method, $url, $options);
    }
}
