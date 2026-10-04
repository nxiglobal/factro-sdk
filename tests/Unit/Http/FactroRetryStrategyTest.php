<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Http;

use Nxi\Factro\Http\FactroRetryStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\RetryableHttpClient;

#[CoversClass(FactroRetryStrategy::class)]
final class FactroRetryStrategyTest extends TestCase
{
    /** @var list<string> */
    private array $sent = [];

    /**
     * @param list<MockResponse> $responses
     */
    private function client(array $responses, int $maxRetryAfterSeconds = 5): RetryableHttpClient
    {
        $this->sent = [];
        $mock = new MockHttpClient(function (string $method) use (&$responses): MockResponse {
            $this->sent[] = $method;

            return array_shift($responses) ?? new MockResponse('', ['http_code' => 200]);
        });

        return new RetryableHttpClient($mock, new FactroRetryStrategy(maxRetryAfterSeconds: $maxRetryAfterSeconds, delayMs: 0), 3);
    }

    public function testGetIsRetriedOn503UpToThreeTimes(): void
    {
        $client = $this->client([
            new MockResponse('', ['http_code' => 503]),
            new MockResponse('', ['http_code' => 503]),
            new MockResponse('ok', ['http_code' => 200]),
        ]);

        self::assertSame('ok', $client->request('GET', 'https://factro.test/x')->getContent());
        self::assertCount(3, $this->sent);
    }

    public function testPostIsNeverRetried(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 500]), new MockResponse('ok')]);

        self::assertSame(500, $client->request('POST', 'https://factro.test/x')->getStatusCode());
        self::assertCount(1, $this->sent);
    }

    public function testPutIsNeverRetried(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 503]), new MockResponse('ok')]);

        self::assertSame(503, $client->request('PUT', 'https://factro.test/x')->getStatusCode());
        self::assertCount(1, $this->sent);
    }

    public function testGetIsNotRetriedOn404(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 404]), new MockResponse('ok')]);

        self::assertSame(404, $client->request('GET', 'https://factro.test/x')->getStatusCode());
        self::assertCount(1, $this->sent);
    }

    public function testGetIsRetriedOn429WithSmallRetryAfter(): void
    {
        $client = $this->client([
            new MockResponse('', ['http_code' => 429, 'response_headers' => ['retry-after' => '1']]),
            new MockResponse('ok'),
        ]);

        self::assertSame('ok', $client->request('GET', 'https://factro.test/x')->getContent());
        self::assertCount(2, $this->sent);
    }

    public function testGetIsNotRetriedOn429WithLargeRetryAfter(): void
    {
        $client = $this->client([
            new MockResponse('', ['http_code' => 429, 'response_headers' => ['retry-after' => '120']]),
            new MockResponse('ok'),
        ], maxRetryAfterSeconds: 5);

        self::assertSame(429, $client->request('GET', 'https://factro.test/x')->getStatusCode());
        self::assertCount(1, $this->sent);
    }

    public function testGetIsRetriedOnTransportError(): void
    {
        $client = $this->client([
            new MockResponse('', ['error' => 'Connection reset']),
            new MockResponse('ok'),
        ]);

        self::assertSame('ok', $client->request('GET', 'https://factro.test/x')->getContent());
        self::assertCount(2, $this->sent);
    }
}
