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
    private const array METHODS = ['GET', 'POST', 'PUT', 'DELETE'];

    /**
     * @var list<string> upper-case HTTP methods
     */
    public array $allowedMethods;

    /**
     * @var list<string>
     */
    public array $deniedPathPatterns;

    /**
     * @param list<string> $allowedMethods     HTTP methods, any case; only GET, POST, PUT and DELETE
     * @param list<string> $deniedPathPatterns PCRE patterns matched against the relative path without query
     *
     * @throws \InvalidArgumentException for an unsupported method or an invalid pattern
     */
    public function __construct(array $allowedMethods = self::METHODS, array $deniedPathPatterns = [])
    {
        $methods = array_map(strtoupper(...), $allowedMethods);
        foreach ($methods as $method) {
            if (!in_array($method, self::METHODS, true)) {
                throw new \InvalidArgumentException(sprintf('Unsupported HTTP method "%s", expected one of %s.', $method, implode(', ', self::METHODS)));
            }
        }
        foreach ($deniedPathPatterns as $pattern) {
            if (false === @preg_match($pattern, '')) {
                throw new \InvalidArgumentException(sprintf('Invalid path pattern "%s": %s.', $pattern, preg_last_error_msg()));
            }
        }
        $this->allowedMethods = $methods;
        $this->deniedPathPatterns = $deniedPathPatterns;
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

        // A pattern that fails at match time (e.g. backtrack limit) denies: fail closed.
        return array_all($this->deniedPathPatterns, static fn ($pattern) => 0 === @preg_match($pattern, $path));
    }
}
