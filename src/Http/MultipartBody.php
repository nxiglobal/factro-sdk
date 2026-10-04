<?php

declare(strict_types=1);

namespace Nxi\Factro\Http;

/**
 * A multipart/form-data body built without symfony/mime, used for document uploads.
 *
 * @internal
 */
final readonly class MultipartBody
{
    private function __construct(public string $boundary, public string $content)
    {
    }

    /**
     * A body with exactly one file part.
     */
    public static function file(string $field, string $filename, string $content, string $contentType): self
    {
        $boundary = 'nxi-factro-'.bin2hex(random_bytes(16));
        $safeName = str_replace(['"', "\r", "\n"], '', $filename);
        $body = '--'.$boundary."\r\n"
            .sprintf('Content-Disposition: form-data; name="%s"; filename="%s"', $field, $safeName)."\r\n"
            .'Content-Type: '.$contentType."\r\n\r\n"
            .$content."\r\n"
            .'--'.$boundary."--\r\n";

        return new self($boundary, $body);
    }

    public function contentType(): string
    {
        return 'multipart/form-data; boundary='.$this->boundary;
    }
}
