<?php

declare(strict_types=1);

namespace Nxi\Factro;

/**
 * Snapshot of the IETF rate-limit headers factro sends with every response.
 */
final readonly class RateLimitInfo
{
    public function __construct(
        public int $limit,
        public int $remaining,
        public int $windowSeconds,
        public int $resetSeconds,
        public string $policyName,
    ) {
    }
}
