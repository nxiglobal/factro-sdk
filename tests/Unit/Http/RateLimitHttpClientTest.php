<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Http;

use Nxi\Factro\Http\RateLimitHttpClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

#[CoversClass(RateLimitHttpClient::class)]
final class RateLimitHttpClientTest extends TestCase
{
    public function testThirdRequestWaitsForTheWindow(): void
    {
        $factory = new RateLimiterFactory(
            ['id' => 't', 'policy' => 'sliding_window', 'limit' => 2, 'interval' => '2 seconds'],
            new InMemoryStorage(),
        );
        $inner = new MockHttpClient(static fn () => new MockResponse('ok'));
        $client = new RateLimitHttpClient($inner, $factory->create('k'));

        $start = microtime(true);
        $client->request('GET', 'https://factro.test/1');
        $client->request('GET', 'https://factro.test/2');
        self::assertLessThan(0.5, microtime(true) - $start);

        $client->request('GET', 'https://factro.test/3');
        self::assertGreaterThan(0.9, microtime(true) - $start);
        self::assertSame(3, $inner->getRequestsCount());
    }

    public function testResetClearsLimiter(): void
    {
        $factory = new RateLimiterFactory(['id' => 't', 'policy' => 'sliding_window', 'limit' => 1, 'interval' => '10 seconds'], new InMemoryStorage());
        $client = new RateLimitHttpClient(new MockHttpClient(static fn () => new MockResponse('ok')), $factory->create('k'));

        $client->request('GET', 'https://factro.test/1');
        $client->reset();
        $start = microtime(true);
        $client->request('GET', 'https://factro.test/2');
        self::assertLessThan(0.5, microtime(true) - $start);
    }
}
