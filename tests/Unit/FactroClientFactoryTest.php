<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit;

use Nxi\Factro\FactroClient;
use Nxi\Factro\FactroClientFactory;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

#[CoversClass(FactroClientFactory::class)]
#[CoversClass(FactroClient::class)]
final class FactroClientFactoryTest extends TestCase
{
    public function testDefaultLimiterFactoryIsSlidingWindow480PerMinute(): void
    {
        $limiter = FactroClientFactory::defaultLimiterFactory()->create('k');
        $limit = $limiter->consume(0);

        self::assertSame(480, $limit->getLimit());
    }

    public function testUsesGivenHttpClientAsBase(): void
    {
        $seen = [];
        $mock = new MockHttpClient(static function (string $method, string $url, array $options) use (&$seen): JsonMockResponse {
            $seen = ['method' => $method, 'url' => $url, 'headers' => $options['headers']];

            return new JsonMockResponse([]);
        });
        $options = new FactroOptions(baseUrl: 'https://factro.test/api/core');
        $client = FactroClientFactory::create('secret-token', $options, $mock);

        $client->transport()->request('GET', '/projects');

        self::assertSame('GET', $seen['method']);
        self::assertSame('https://factro.test/api/core/projects', $seen['url']);
        self::assertIsArray($seen['headers']);
        self::assertContains('Authorization: secret-token', $seen['headers']);
        self::assertNotContains('Authorization: Bearer secret-token', $seen['headers']);
        self::assertContains('Accept: application/json', $seen['headers']);
        self::assertContains('User-Agent: nxi-factro-sdk/'.Version::CURRENT, $seen['headers']);
        self::assertSame($options, $client->options());
    }

    public function testBaseUrlWithTrailingSlashIsNormalised(): void
    {
        $seen = null;
        $mock = new MockHttpClient(static function (string $method, string $url) use (&$seen): JsonMockResponse {
            $seen = $url;

            return new JsonMockResponse([]);
        });
        FactroClientFactory::create('t', new FactroOptions(baseUrl: 'https://factro.test/api/core/'), $mock)
            ->transport()->request('GET', '/tasks/abc', ['x' => 1]);

        self::assertSame('https://factro.test/api/core/tasks/abc?x=1', $seen);
    }

    public function testTimeoutsAreForwarded(): void
    {
        $seen = [];
        $mock = new MockHttpClient(static function (string $method, string $url, array $options) use (&$seen): JsonMockResponse {
            $seen = $options;

            return new JsonMockResponse([]);
        });
        FactroClientFactory::create('t', new FactroOptions(baseUrl: 'https://factro.test/api/core', requestTimeoutSeconds: 7.5, inactivityTimeoutSeconds: 2.5), $mock)
            ->transport()->request('GET', '/projects');

        self::assertSame(7.5, $seen['max_duration']);
        self::assertSame(2.5, $seen['timeout']);
    }
}
