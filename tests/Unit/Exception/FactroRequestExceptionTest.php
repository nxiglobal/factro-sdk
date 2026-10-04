<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Exception;

use Nxi\Factro\Exception\FactroRequestException;
use Nxi\Factro\Exception\RateLimitException;
use Nxi\Factro\Exception\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FactroRequestException::class)]
#[CoversClass(ValidationException::class)]
#[CoversClass(RateLimitException::class)]
final class FactroRequestExceptionTest extends TestCase
{
    public function testDecodedBodyAndMessageFromJson(): void
    {
        $e = new FactroRequestException('m', 'PUT', '/tasks/1', 403, '{"message":"Task is closed"}');

        self::assertSame(['message' => 'Task is closed'], $e->decodedBody());
        self::assertSame('Task is closed', $e->factroMessage());
    }

    public function testMessageFromPlainText(): void
    {
        $e = new FactroRequestException('m', 'GET', '/tasks/1', 404, "Task with id \"1\" not found\n");

        self::assertNull($e->decodedBody());
        self::assertSame('Task with id "1" not found', $e->factroMessage());
    }

    public function testHtmlBodyYieldsNoMessage(): void
    {
        $e = new FactroRequestException('m', 'GET', '/x', 404, '<!DOCTYPE html><html><body><pre>Cannot GET /x</pre></body></html>');

        self::assertNull($e->factroMessage());
    }

    public function testValidationErrorsFallBackToEmptyArray(): void
    {
        self::assertSame([], new ValidationException('m', 'POST', '/tasks', 400, 'nope')->errors());
        self::assertSame(['title' => 'required'], new ValidationException('m', 'POST', '/tasks', 422, '{"errors":{"title":"required"}}')->errors());
    }

    public function testRetryAfterFromHeaderThenRatelimit(): void
    {
        self::assertSame(7, new RateLimitException('m', 'GET', '/x', 429, '', ['retry-after' => ['7']])->retryAfterSeconds());
        self::assertSame(60, new RateLimitException('m', 'GET', '/x', 429, '', ['ratelimit' => ['"500-in-1min"; r=0; t=60']])->retryAfterSeconds());
        self::assertNull(new RateLimitException('m', 'GET', '/x', 429, '')->retryAfterSeconds());
    }
}
