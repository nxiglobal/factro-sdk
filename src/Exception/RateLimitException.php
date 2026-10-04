<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

use Nxi\Factro\Http\RateLimitHeaders;

/**
 * HTTP 429.
 */
final class RateLimitException extends FactroRequestException
{
    /**
     * Retry-After header, else "t" from the ratelimit header, else null.
     */
    public function retryAfterSeconds(): ?int
    {
        return RateLimitHeaders::retryAfterSeconds($this->headers);
    }
}
