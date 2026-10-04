<?php

declare(strict_types=1);

namespace Nxi\Factro\Http;

use Nxi\Factro\RateLimitInfo;

/**
 * Parses the IETF structured-field rate-limit headers factro sends:
 *   ratelimit: "500-in-1min"; r=499; t=60
 *   ratelimit-policy: "500-in-1min"; q=500; w=60; pk=:...:
 *
 * @internal
 */
final class RateLimitHeaders
{
    private function __construct()
    {
    }

    /**
     * @param array<string, list<string>> $headers lower-case names
     */
    public static function parse(array $headers): ?RateLimitInfo
    {
        $limit = self::parseStructured($headers['ratelimit'][0] ?? null);
        if (null === $limit || !isset($limit['params']['r'])) {
            return null;
        }
        $policy = self::parseStructured($headers['ratelimit-policy'][0] ?? null);
        [$quotaFromName, $windowFromName] = self::parsePolicyName($limit['name']);

        return new RateLimitInfo(
            limit: $policy['params']['q'] ?? $quotaFromName ?? 0,
            remaining: $limit['params']['r'],
            windowSeconds: $policy['params']['w'] ?? $windowFromName ?? 0,
            resetSeconds: $limit['params']['t'] ?? 0,
            policyName: $limit['name'],
        );
    }

    /**
     * Retry-After as integer seconds; an HTTP-date is converted relative to now; else "t" from ratelimit; else null.
     *
     * @param array<string, list<string>> $headers lower-case names
     */
    public static function retryAfterSeconds(array $headers): ?int
    {
        $after = $headers['retry-after'][0] ?? null;
        if (null !== $after) {
            $after = trim($after);
            if (ctype_digit($after)) {
                return (int) $after;
            }
            $timestamp = strtotime($after);
            if (false !== $timestamp) {
                return max(0, $timestamp - time());
            }
        }

        return self::parse($headers)?->resetSeconds;
    }

    /**
     * @return array{name: string, params: array<string, int>}|null
     */
    private static function parseStructured(?string $value): ?array
    {
        if (null === $value || 1 !== preg_match('/^"([^"]+)"((?:;\s*[a-z]+=[^;]+)*)$/i', trim($value), $m)) {
            return null;
        }
        $params = [];
        preg_match_all('/;\s*([a-z]+)=(-?\d+)/i', $m[2], $pairs, PREG_SET_ORDER);
        foreach ($pairs as $pair) {
            $params[strtolower($pair[1])] = (int) $pair[2];
        }

        return ['name' => $m[1], 'params' => $params];
    }

    /**
     * @return array{?int, ?int} quota and window seconds parsed from "500-in-1min"
     */
    private static function parsePolicyName(string $name): array
    {
        if (1 !== preg_match('/^(\d+)-in-(\d+)(sec|min|hour|day)$/', $name, $m)) {
            return [null, null];
        }
        $factor = match ($m[3]) {
            'sec' => 1,
            'min' => 60,
            'hour' => 3600,
            'day' => 86400,
        };

        return [(int) $m[1], (int) $m[2] * $factor];
    }
}
