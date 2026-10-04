<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

/**
 * The request could not be sent or the response could not be read (DNS, TCP, TLS, timeout).
 */
final class TransportException extends \RuntimeException implements FactroException
{
    public function __construct(
        string $message,
        public readonly string $method,
        public readonly string $path,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * True for methods with side effects: the server may have applied the change before the failure.
     */
    public function possiblyExecuted(): bool
    {
        return in_array(strtoupper($this->method), ['POST', 'PUT', 'DELETE', 'PATCH'], true);
    }
}
