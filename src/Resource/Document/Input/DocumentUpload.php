<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Document\Input;

use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

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
        // type/subtype with optional parameters; \z instead of $, which would accept a trailing "\n"
        if (1 !== preg_match('~^[\w!#$&^.+-]+/[\w!#$&^.+-]+(;[^\x00-\x1F\x7F]*)?\z~', $contentType)) {
            throw new \InvalidArgumentException(sprintf('DocumentUpload needs a media type like "application/pdf", got "%s".', addcslashes($contentType, "\0..\37\177")));
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
     * @internal builds the request body; the format follows the factro API and may change in minor releases
     */
    public function toMultipart(): FormDataPart
    {
        // symfony/mime would percent-encode these characters; stripping keeps the names factro stores
        $filename = str_replace(['"', "\r", "\n"], '', $this->filename);

        return new FormDataPart([self::FIELD => new DataPart($this->content, $filename, $this->contentType)]);
    }
}
