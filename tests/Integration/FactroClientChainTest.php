<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Integration;

use Nxi\Factro\Exception\AuthenticationException;
use Nxi\Factro\Exception\RateLimitException;
use Nxi\Factro\Exception\ServerException;
use Nxi\Factro\FactroClientFactory;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\RateLimitInfo;
use Nxi\Factro\Tests\Support\MockFactro;
use Nxi\Factro\Tests\Support\RecordingLogger;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

#[CoversNothing]
final class FactroClientChainTest extends TestCase
{
    public function testPolicyBlocksDeleteBeforeSending(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->transport()->request('DELETE', '/tasks/1');
    }

    public function testPostOn500IsSentExactlyOnce(): void
    {
        $calls = 0;
        $client = MockFactro::client(['POST /tasks' => static function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse('', ['http_code' => 500]);
        }], new FactroOptions(baseUrl: MockFactro::BASE_URL, maxRetries: 3));

        try {
            $client->transport()->request('POST', '/tasks', json: ['title' => 'x']);
            self::fail('expected ServerException');
        } catch (ServerException) {
        }
        self::assertSame(1, $calls);
    }

    public function testGetOn503IsRetried(): void
    {
        $calls = 0;
        $client = MockFactro::client(['GET /projects' => static function () use (&$calls): MockResponse {
            return ++$calls < 3 ? new MockResponse('', ['http_code' => 503]) : new JsonMockResponse([]);
        }], new FactroOptions(baseUrl: MockFactro::BASE_URL, maxRetries: 3));

        self::assertSame([], $client->transport()->request('GET', '/projects'));
        self::assertSame(3, $calls);
    }

    public function testRateLimitExceptionCarriesRetryAfter(): void
    {
        $client = MockFactro::client(['GET /projects' => static fn (): MockResponse => new MockResponse('Rate limit exceeded', [
            'http_code' => 429, 'response_headers' => ['retry-after' => '120'],
        ])], new FactroOptions(baseUrl: MockFactro::BASE_URL, maxRetries: 3));

        try {
            $client->transport()->request('GET', '/projects');
            self::fail('expected RateLimitException');
        } catch (RateLimitException $e) {
            self::assertSame(120, $e->retryAfterSeconds());
            self::assertSame('Rate limit exceeded', $e->getMessage());
        }
    }

    public function testTokenNeverAppearsInExceptionsOrLogs(): void
    {
        $logger = new RecordingLogger();
        $client = FactroClientFactory::create(
            'very-secret',
            new FactroOptions(baseUrl: MockFactro::BASE_URL, maxRetries: 0, logger: $logger),
            MockFactro::http(['GET /tasks/1' => new MockResponse('Invalid CoreApiAccessToken provided!', ['http_code' => 401])]),
        );

        try {
            $client->transport()->request('GET', '/tasks/1');
            self::fail('expected AuthenticationException');
        } catch (AuthenticationException $e) {
            $dump = $e->getMessage().json_encode($logger->records, JSON_THROW_ON_ERROR).print_r($e->headers, true);
            self::assertStringNotContainsString('very-secret', $dump);
            self::assertSame('Invalid CoreApiAccessToken provided!', $e->getMessage());
            self::assertNotEmpty($logger->records);
        }
    }

    public function testCustomLimiterFactoryIsUsedWithTokenHashKey(): void
    {
        $factory = new class implements RateLimiterFactoryInterface {
            /** @var list<string> */
            public array $keys = [];

            public function create(?string $key = null): LimiterInterface
            {
                $this->keys[] = (string) $key;

                return new RateLimiterFactory(['id' => 'x', 'policy' => 'no_limit'], new InMemoryStorage())->create($key);
            }
        };
        FactroClientFactory::create('tok', new FactroOptions(baseUrl: MockFactro::BASE_URL, limiterFactory: $factory), MockFactro::http([]));

        self::assertSame([hash('sha256', 'tok')], $factory->keys);
    }

    public function testRateLimitCallbackReceivesInfoThroughTheChain(): void
    {
        $seen = [];
        $client = MockFactro::client(
            ['GET /projects' => static fn (): MockResponse => new JsonMockResponse([], ['response_headers' => ['ratelimit' => '"500-in-1min"; r=7; t=3']])],
            new FactroOptions(baseUrl: MockFactro::BASE_URL, maxRetries: 0, onRateLimit: static function (RateLimitInfo $info) use (&$seen): void {
                $seen[] = $info->remaining;
            }),
        );

        $client->projects()->list();

        self::assertSame([7], $seen);
    }
}
