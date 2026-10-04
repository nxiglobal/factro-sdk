<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Http;

use Nxi\Factro\Http\RateLimitHeaders;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RateLimitHeaders::class)]
final class RateLimitHeadersTest extends TestCase
{
    public function testParsesFactroHeaders(): void
    {
        $info = RateLimitHeaders::parse([
            'ratelimit' => ['"500-in-1min"; r=499; t=60'],
            'ratelimit-policy' => ['"500-in-1min"; q=500; w=60; pk=:MzBiOGFhNTRmNGIx:'],
        ]);

        self::assertNotNull($info);
        self::assertSame(500, $info->limit);
        self::assertSame(499, $info->remaining);
        self::assertSame(60, $info->windowSeconds);
        self::assertSame(60, $info->resetSeconds);
        self::assertSame('500-in-1min', $info->policyName);
    }

    public function testFallsBackToPolicyNameWhenPolicyHeaderMissing(): void
    {
        $info = RateLimitHeaders::parse(['ratelimit' => ['"500-in-1min"; r=12; t=9']]);

        self::assertNotNull($info);
        self::assertSame(500, $info->limit);
        self::assertSame(60, $info->windowSeconds);
    }

    public function testReturnsNullWithoutRatelimitHeader(): void
    {
        self::assertNull(RateLimitHeaders::parse(['content-type' => ['application/json']]));
        self::assertNull(RateLimitHeaders::parse(['ratelimit' => ['garbage']]));
    }

    public function testRetryAfterPrefersHeaderSeconds(): void
    {
        self::assertSame(7, RateLimitHeaders::retryAfterSeconds(['retry-after' => ['7'], 'ratelimit' => ['"500-in-1min"; r=0; t=60']]));
        self::assertSame(60, RateLimitHeaders::retryAfterSeconds(['ratelimit' => ['"500-in-1min"; r=0; t=60']]));
        self::assertNull(RateLimitHeaders::retryAfterSeconds([]));
    }

    public function testRetryAfterHttpDateIsConvertedRelativeToNow(): void
    {
        $date = gmdate('D, d M Y H:i:s \G\M\T', time() + 30);
        $seconds = RateLimitHeaders::retryAfterSeconds(['retry-after' => [$date]]);

        self::assertGreaterThanOrEqual(28, $seconds);
        self::assertLessThanOrEqual(30, $seconds);
    }
}
