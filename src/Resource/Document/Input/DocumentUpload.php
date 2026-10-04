<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Document\Input;

use Nxi\Factro\Http\MultipartBody;

/**
 * A file for POST /tasks/{id}/documents, /projects/{id}/documents and /projects/{id}/packages/{pid}/documents.
 *
 * The OpenAPI document declares no request body for these routes. The SDK sends one multipart part
 * named "file"; the part name is derived from the observed behaviour of the API.
 */
final readonly class DocumentUpload
{
    public const string FIELD = 'file';

    public function __construct(
        public string $filename,
        public string $content,
        public string $contentType = 'application/octet-stream',
    ) {
        if ('' === trim($filename)) {
            throw new \InvalidArgumentException('DocumentUpload needs a file name.');
        }
    }

    /**
     * Reads the file from disk; the content type defaults to application/octet-stream.
     */
    public static function fromFile(string $path, ?string $contentType = null, ?string $filename = null): self
    {
        $content = @file_get_contents($path);
        if (false === $content) {
            throw new \InvalidArgumentException(sprintf('DocumentUpload cannot read "%s".', $path));
        }

        return new self($filename ?? basename($path), $content, $contentType ?? 'application/octet-stream');
    }

    /**
     * @internal
     */
    public function toMultipart(): MultipartBody
    {
        return MultipartBody::file(self::FIELD, $this->filename, $this->content, $this->contentType);
    }
}
