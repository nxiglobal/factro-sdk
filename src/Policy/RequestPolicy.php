<?php

declare(strict_types=1);

namespace Nxi\Factro\Policy;

/**
 * Declares which HTTP methods and paths a consumer allows the SDK to send.
 *
 * Denied path patterns apply to every method except GET: prohibitions target write
 * operations, reading the same path stays permitted.
 */
final readonly class RequestPolicy
{
    /**
     * @param list<string> $allowedMethods     upper-case HTTP methods
     * @param list<string> $deniedPathPatterns PCRE patterns matched against the relative path without query
     */
    public function __construct(
        public array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'],
        public array $deniedPathPatterns = [],
    ) {
    }

    public static function all(): self
    {
        return new self();
    }

    public static function readOnly(): self
    {
        return new self(['GET']);
    }

    public static function withoutDeletes(): self
    {
        return new self(['GET', 'POST', 'PUT']);
    }

    public function denyPaths(string ...$patterns): self
    {
        return new self($this->allowedMethods, [...$this->deniedPathPatterns, ...array_values($patterns)]);
    }

    public function permits(string $method, string $path): bool
    {
        $method = strtoupper($method);
        if (!in_array($method, $this->allowedMethods, true)) {
            return false;
        }
        if ('GET' === $method) {
            return true;
        }

        return array_all($this->deniedPathPatterns, static fn ($pattern) => 1 !== preg_match($pattern, $path));
    }
}
