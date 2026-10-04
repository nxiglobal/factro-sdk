<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Exception;

use Nxi\Factro\Exception\TransportException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransportException::class)]
final class TransportExceptionTest extends TestCase
{
    public function testPossiblyExecutedDependsOnMethod(): void
    {
        self::assertFalse(new TransportException('m', 'GET', '/x')->possiblyExecuted());
        self::assertFalse(new TransportException('m', 'get', '/x')->possiblyExecuted());
        self::assertTrue(new TransportException('m', 'POST', '/x')->possiblyExecuted());
        self::assertTrue(new TransportException('m', 'PUT', '/x')->possiblyExecuted());
        self::assertTrue(new TransportException('m', 'DELETE', '/x')->possiblyExecuted());
    }

    public function testKeepsMethodPathAndPrevious(): void
    {
        $previous = new \RuntimeException('inner');
        $e = new TransportException('outer', 'POST', '/tasks', $previous);

        self::assertSame('outer', $e->getMessage());
        self::assertSame('POST', $e->method);
        self::assertSame('/tasks', $e->path);
        self::assertSame($previous, $e->getPrevious());
    }
}
