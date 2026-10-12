<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Document;

use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocumentUpload::class)]
final class DocumentUploadTest extends TestCase
{
    public function testToMultipartUsesTheFileField(): void
    {
        $body = new DocumentUpload('Quote.pdf', '%PDF', 'application/pdf')->toMultipart()->bodyToString();

        self::assertStringContainsString('Content-Disposition: form-data; name="file"; filename="Quote.pdf"'."\r\n", $body);
        self::assertStringContainsString("Content-Type: application/pdf\r\n", $body);
        self::assertStringContainsString("\r\n\r\n%PDF\r\n", $body);
    }

    public function testFileNameIsStrippedOfQuotesAndLineBreaks(): void
    {
        $body = new DocumentUpload("a\"b\r\nc.txt", 'x', 'text/plain')->toMultipart()->bodyToString();

        self::assertStringContainsString('filename="abc.txt"', $body);
    }

    #[TestWith(['application/pdf'])]
    #[TestWith(['text/plain; charset=utf-8'])]
    #[TestWith(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])]
    public function testMediaTypesAreAccepted(string $contentType): void
    {
        self::assertSame($contentType, new DocumentUpload('a.txt', 'x', $contentType)->contentType);
    }

    #[TestWith(["text/plain\r\nX-Injected: 1"])]
    #[TestWith(["text/plain\n"])]
    #[TestWith(["text/plain; charset=utf-8\r\n\r\nbody"])]
    #[TestWith([''])]
    #[TestWith(['nonsense'])]
    #[TestWith(['text/'])]
    public function testInvalidContentTypeIsRejected(string $contentType): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DocumentUpload('a.txt', 'x', $contentType);
    }

    public function testFromFileReadsContentAndDefaults(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'factro');
        self::assertIsString($path);
        file_put_contents($path, 'hello');

        $upload = DocumentUpload::fromFile($path);

        self::assertSame('hello', $upload->content);
        self::assertSame(basename($path), $upload->filename);
        self::assertSame('application/octet-stream', $upload->contentType);
        unlink($path);
    }

    public function testEmptyFileNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DocumentUpload(' ', 'x');
    }

    public function testUnreadableFileIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DocumentUpload::fromFile('/nonexistent/file.bin');
    }
}
