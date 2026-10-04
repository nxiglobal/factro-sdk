<?php

declare(strict_types=1);

namespace Nxi\Factro\Http;

use Nxi\Factro\Exception\AuthenticationException;
use Nxi\Factro\Exception\FactroRequestException;
use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Exception\RateLimitException;
use Nxi\Factro\Exception\ServerException;
use Nxi\Factro\Exception\TransportException;
use Nxi\Factro\Exception\ValidationException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Maps HTTP failures onto the SDK exception family.
 *
 * @internal
 */
final class ErrorMapper
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function fromResponse(string $method, string $path, int $status, string $body, array $headers): FactroRequestException
    {
        $message = self::extractMessage($body) ?? sprintf('factro responded with HTTP %d to %s %s', $status, $method, $path);
        $class = match (true) {
            401 === $status || 403 === $status => AuthenticationException::class,
            404 === $status => NotFoundException::class,
            400 === $status || 422 === $status => ValidationException::class,
            429 === $status => RateLimitException::class,
            $status >= 500 => ServerException::class,
            default => FactroRequestException::class,
        };

        return new $class($message, $method, $path, $status, $body, $headers);
    }

    public function fromTransport(string $method, string $path, TransportExceptionInterface $exception): TransportException
    {
        return new TransportException(
            sprintf('factro request %s %s failed: %s', $method, $path, $exception->getMessage()),
            $method,
            $path,
            $exception,
        );
    }

    /**
     * JSON object: first non-empty string of message, error, detail, title; other JSON: null;
     * text starting with "<" (HTML): null; other text: trimmed, at most 500 characters; empty: null.
     */
    public static function extractMessage(string $body): ?string
    {
        $trimmed = trim($body);
        if ('' === $trimmed || str_starts_with($trimmed, '<')) {
            return null;
        }
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            foreach (['message', 'error', 'detail', 'title'] as $key) {
                $value = $decoded[$key] ?? null;
                if (is_string($value) && '' !== trim($value)) {
                    return trim($value);
                }
            }

            return null;
        }
        if (null !== $decoded || 'null' === $trimmed) {
            return null;
        }

        return mb_substr($trimmed, 0, 500);
    }
}
