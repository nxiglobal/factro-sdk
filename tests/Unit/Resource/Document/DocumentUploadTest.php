<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Document;

use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocumentUpload::class)]
final class DocumentUploadTest extends TestCase
{
    public function testToMultipartUsesTheFileField(): void
    {
        $upload = new DocumentUpload('Quote.pdf', '%PDF', 'application/pdf');

        self::assertStringContainsString('name="file"; filename="Quote.pdf"', $upload->toMultipart()->content);
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
