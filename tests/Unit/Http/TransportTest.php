<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Http;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Exception\ServerException;
use Nxi\Factro\Exception\TransportException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Http\Transport;
use Nxi\Factro\RateLimitInfo;
use Nxi\Factro\Tests\Support\RecordingLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(Transport::class)]
final class TransportTest extends TestCase
{
    private function transport(callable|ResponseInterface $responses, ?FactroOptions $options = null, ?LoggerInterface $logger = null): Transport
    {
        $options ??= new FactroOptions(logger: $logger ?? new NullLogger());
        $mock = new MockHttpClient($responses, 'https://factro.test/api/core/');

        return new Transport($mock, $options);
    }

    public function testGetDecodesJson(): void
    {
        $transport = $this->transport(static function (string $method, string $url): JsonMockResponse {
            self::assertSame('GET', $method);
            self::assertSame('https://factro.test/api/core/tasks/abc?include=x', $url);

            return new JsonMockResponse(['id' => 'abc']);
        });

        self::assertSame(['id' => 'abc'], $transport->request('GET', '/tasks/abc', ['include' => 'x']));
    }

    public function testPostSendsJsonBody(): void
    {
        $transport = $this->transport(static function (string $method, string $url, array $options): JsonMockResponse {
            self::assertSame('POST', $method);
            self::assertIsString($options['body']);
            self::assertJsonStringEqualsJsonString('{"title":"T"}', $options['body']);
            self::assertIsArray($options['headers']);
            self::assertContains('Content-Type: application/json', $options['headers']);

            return new JsonMockResponse(['id' => 'new'], ['http_code' => 200]);
        });

        self::assertSame(['id' => 'new'], $transport->request('POST', '/tasks', json: ['title' => 'T']));
    }

    public function testEmptyBodyYieldsNull(): void
    {
        $transport = $this->transport(new MockResponse('', ['http_code' => 204]));

        self::assertNull($transport->request('PUT', '/tasks/abc/state', json: ['state' => 'closed']));
    }

    public function testJsonScalarIsRejected(): void
    {
        $transport = $this->transport(new MockResponse('42', ['http_code' => 200]));

        $this->expectException(\UnexpectedValueException::class);
        $transport->request('GET', '/tasks/abc');
    }

    public function testErrorStatusIsMapped(): void
    {
        $transport = $this->transport(new MockResponse('Task with id "abc" not found', ['http_code' => 404]));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Task with id "abc" not found');
        $transport->request('GET', '/tasks/abc');
    }

    public function testTransportErrorIsMapped(): void
    {
        $transport = $this->transport(new MockResponse('', ['error' => 'Connection refused']));

        try {
            $transport->request('POST', '/tasks', json: []);
            self::fail('expected TransportException');
        } catch (TransportException $e) {
            self::assertTrue($e->possiblyExecuted());
            self::assertStringContainsString('POST /tasks', $e->getMessage());
        }
    }

    public function testRejectsAbsoluteUrls(): void
    {
        $transport = $this->transport(new MockResponse(''));

        $this->expectException(\InvalidArgumentException::class);
        $transport->request('GET', 'https://evil.test/x');
    }

    public function testRejectsPathsWithoutLeadingSlash(): void
    {
        $transport = $this->transport(new MockResponse(''));

        $this->expectException(\InvalidArgumentException::class);
        $transport->request('GET', 'tasks/abc');
    }

    public function testRejectsProtocolRelativePaths(): void
    {
        $transport = $this->transport(new MockResponse(''));

        $this->expectException(\InvalidArgumentException::class);
        $transport->request('GET', '//evil.test/x');
    }

    public function testRateLimitCallbackAndLogContext(): void
    {
        $seen = null;
        $logger = new RecordingLogger();
        $options = new FactroOptions(logger: $logger, onRateLimit: static function (RateLimitInfo $info) use (&$seen): void {
            $seen = $info;
        });
        $transport = $this->transport(new JsonMockResponse([], ['response_headers' => [
            'ratelimit' => '"500-in-1min"; r=498; t=42',
            'ratelimit-policy' => '"500-in-1min"; q=500; w=60',
        ]]), $options);

        $transport->request('GET', '/projects');

        self::assertInstanceOf(RateLimitInfo::class, $seen);
        self::assertSame(498, $seen->remaining);
        self::assertSame('debug', $logger->records[0][0]);
        self::assertSame(498, $logger->records[0][2]['remaining']);
        self::assertSame('GET', $logger->records[0][2]['method']);
        self::assertSame('/projects', $logger->records[0][2]['path']);
        self::assertSame(200, $logger->records[0][2]['status']);
        self::assertArrayHasKey('duration_ms', $logger->records[0][2]);
        self::assertArrayNotHasKey('headers', $logger->records[0][2]);
        self::assertStringNotContainsString('Authorization', json_encode($logger->records, JSON_THROW_ON_ERROR));
    }

    public function testNotFoundIsLoggedAsInfoAndServerErrorAsError(): void
    {
        $infoLogger = new RecordingLogger();
        $notFound = $this->transport(new MockResponse('Task with id "x" not found', ['http_code' => 404]), logger: $infoLogger);
        try {
            $notFound->request('GET', '/tasks/x');
        } catch (NotFoundException) {
        }
        self::assertSame('info', $infoLogger->records[0][0]);
        self::assertSame('Task with id "x" not found', $infoLogger->records[0][2]['factro_message']);

        $errorLogger = new RecordingLogger();
        $failing = $this->transport(new MockResponse('{"message":"boom"}', ['http_code' => 500]), logger: $errorLogger);
        try {
            $failing->request('GET', '/tasks/x');
        } catch (ServerException) {
        }
        self::assertSame('error', $errorLogger->records[0][0]);
        self::assertSame('boom', $errorLogger->records[0][2]['factro_message']);
    }

    public function testGetManyReturnsBodiesKeyedByPathWithNullFor404(): void
    {
        $transport = $this->transport(static fn (string $method, string $url): MockResponse => match (true) {
            str_ends_with($url, '/users/a/tags') => new JsonMockResponse([['id' => 't1']]),
            str_ends_with($url, '/users/b/tags') => new MockResponse('not found', ['http_code' => 404]),
            default => new JsonMockResponse([]),
        });

        $result = $transport->getMany(['/users/a/tags', '/users/b/tags', '/users/c/tags'], concurrency: 2);

        self::assertSame(['/users/a/tags' => [['id' => 't1']], '/users/b/tags' => null, '/users/c/tags' => []], $result);
    }

    public function testGetManyThrowsFirstNon404Error(): void
    {
        $transport = $this->transport(static fn (string $m, string $url): MockResponse => str_contains($url, 'b') ? new MockResponse('', ['http_code' => 500]) : new JsonMockResponse([]));

        $this->expectException(ServerException::class);
        $transport->getMany(['/users/a/tags', '/users/b/tags']);
    }

    public function testGetManyWithEmptyInput(): void
    {
        self::assertSame([], $this->transport(new MockResponse(''))->getMany([]));
    }
}
