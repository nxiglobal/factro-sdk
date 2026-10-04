<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Http;

use Nxi\Factro\Exception\AuthenticationException;
use Nxi\Factro\Exception\FactroRequestException;
use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Exception\RateLimitException;
use Nxi\Factro\Exception\ServerException;
use Nxi\Factro\Exception\ValidationException;
use Nxi\Factro\Http\ErrorMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;

#[CoversClass(ErrorMapper::class)]
final class ErrorMapperTest extends TestCase
{
    /** @return iterable<string, array{int, class-string<FactroRequestException>}> */
    public static function statusProvider(): iterable
    {
        yield '400' => [400, ValidationException::class];
        yield '401' => [401, AuthenticationException::class];
        yield '403' => [403, AuthenticationException::class];
        yield '404' => [404, NotFoundException::class];
        yield '409' => [409, FactroRequestException::class];
        yield '422' => [422, ValidationException::class];
        yield '429' => [429, RateLimitException::class];
        yield '500' => [500, ServerException::class];
        yield '503' => [503, ServerException::class];
    }

    /** @param class-string<FactroRequestException> $class */
    #[DataProvider('statusProvider')]
    public function testMapsStatusToExceptionClass(int $status, string $class): void
    {
        $e = new ErrorMapper()->fromResponse('GET', '/tasks/1', $status, '', []);

        self::assertInstanceOf($class, $e);
        self::assertSame($status, $e->statusCode);
        self::assertSame('GET', $e->method);
        self::assertSame('/tasks/1', $e->path);
        self::assertSame("factro responded with HTTP {$status} to GET /tasks/1", $e->getMessage());
    }

    public function testUsesFactroMessageWhenPresent(): void
    {
        $e = new ErrorMapper()->fromResponse('PUT', '/tasks/1', 403, '{"message":"Task is closed"}', []);

        self::assertSame('Task is closed', $e->getMessage());
        self::assertSame('{"message":"Task is closed"}', $e->rawBody);
    }

    public function testTransportExceptionKeepsMethodAndPath(): void
    {
        $inner = new TransportException('Connection refused');
        $e = new ErrorMapper()->fromTransport('POST', '/tasks', $inner);

        self::assertSame('factro request POST /tasks failed: Connection refused', $e->getMessage());
        self::assertTrue($e->possiblyExecuted());
        self::assertSame($inner, $e->getPrevious());
    }

    public function testExtractMessageRules(): void
    {
        self::assertSame('Task is closed', ErrorMapper::extractMessage('{"message":"Task is closed"}'));
        self::assertSame('Bad', ErrorMapper::extractMessage('{"error":"Bad"}'));
        self::assertNull(ErrorMapper::extractMessage('{"foo":"bar"}'));
        self::assertNull(ErrorMapper::extractMessage('[1,2]'));
        self::assertSame('Rate limit exceeded', ErrorMapper::extractMessage("Rate limit exceeded\n"));
        self::assertNull(ErrorMapper::extractMessage('<!DOCTYPE html><html></html>'));
        self::assertNull(ErrorMapper::extractMessage('   '));
        self::assertSame(500, strlen((string) ErrorMapper::extractMessage(str_repeat('x', 900))));
    }
}
