<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Http;

use Nxi\Factro\Http\MultipartBody;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultipartBody::class)]
final class MultipartBodyTest extends TestCase
{
    public function testFileBuildsOnePartWithBoundary(): void
    {
        $body = MultipartBody::file('file', 'Quote.pdf', '%PDF-1.4', 'application/pdf');

        self::assertStringStartsWith('multipart/form-data; boundary=nxi-factro-', $body->contentType());
        self::assertStringStartsWith('--'.$body->boundary."\r\n", $body->content);
        self::assertStringContainsString('Content-Disposition: form-data; name="file"; filename="Quote.pdf"'."\r\n", $body->content);
        self::assertStringContainsString("Content-Type: application/pdf\r\n\r\n%PDF-1.4\r\n", $body->content);
        self::assertStringEndsWith('--'.$body->boundary."--\r\n", $body->content);
    }

    public function testFileNameIsStrippedOfQuotesAndLineBreaks(): void
    {
        $body = MultipartBody::file('file', "a\"b\r\nc.txt", 'x', 'text/plain');

        self::assertStringContainsString('filename="abc.txt"', $body->content);
    }

    public function testBoundariesAreUnique(): void
    {
        self::assertNotSame(MultipartBody::file('f', 'a', 'x', 'text/plain')->boundary, MultipartBody::file('f', 'a', 'x', 'text/plain')->boundary);
    }
}
