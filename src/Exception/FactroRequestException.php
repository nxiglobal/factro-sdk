<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

use Nxi\Factro\Http\ErrorMapper;

/**
 * factro answered with an HTTP status of 400 or higher.
 *
 * Subclasses exist for the well-known statuses; this class itself is thrown for every other status.
 */
class FactroRequestException extends \RuntimeException implements FactroException
{
    /**
     * @param array<string, list<string>> $headers lower-case header names
     */
    public function __construct(
        string $message,
        public readonly string $method,
        public readonly string $path,
        public readonly int $statusCode,
        public readonly string $rawBody,
        public readonly array $headers = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * @return array<mixed>|null decoded JSON body, null when the body is not a JSON object or array
     */
    public function decodedBody(): ?array
    {
        $decoded = json_decode($this->rawBody, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * message, error, detail or title from a JSON body; else the trimmed text body; null for HTML or empty bodies.
     */
    public function factroMessage(): ?string
    {
        return ErrorMapper::extractMessage($this->rawBody);
    }
}
