<?php

declare(strict_types=1);

namespace Nxi\Factro;

/**
 * Snapshot of the IETF rate-limit headers factro sends with every response.
 * Null means factro did not send the value; 0 is a real value ("no requests left", "resets now").
 */
final readonly class RateLimitInfo
{
    public function __construct(
        public ?int $limit,
        public int $remaining,
        public ?int $windowSeconds,
        public ?int $resetSeconds,
        public string $policyName,
    ) {
    }
}
