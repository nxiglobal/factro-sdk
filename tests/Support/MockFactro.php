<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Support;

use Nxi\Factro\FactroClient;
use Nxi\Factro\FactroClientFactory;
use Nxi\Factro\FactroOptions;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Builds a FactroClient whose base HTTP client resolves "METHOD /path" routes to mock responses.
 *
 * A MockResponse can be sent only once: routes that are hit several times must be closures.
 */
final class MockFactro
{
    public const string BASE_URL = 'https://factro.test/api/core';

    /**
     * @param array<string, ResponseInterface|\Closure(string, string, array<string, mixed>): ResponseInterface> $routes keyed by "METHOD /path"
     */
    public static function http(array $routes): MockHttpClient
    {
        return new MockHttpClient(static function (string $method, string $url, array $options) use ($routes): ResponseInterface {
            $path = (string) parse_url($url, PHP_URL_PATH);
            $path = preg_replace('#^/api/core#', '', $path) ?? $path;
            $key = strtoupper($method).' '.$path;
            $route = $routes[$key] ?? throw new \LogicException(sprintf('No mock route for "%s".', $key));

            return $route instanceof \Closure ? $route($method, $url, $options) : $route;
        }, self::BASE_URL.'/');
    }

    /**
     * @param array<string, ResponseInterface|\Closure(string, string, array<string, mixed>): ResponseInterface> $routes keyed by "METHOD /path"
     */
    public static function client(array $routes, ?FactroOptions $options = null): FactroClient
    {
        $options ??= new FactroOptions(baseUrl: self::BASE_URL, maxRetries: 0);

        return FactroClientFactory::create('test-token', $options, self::http($routes));
    }
}
