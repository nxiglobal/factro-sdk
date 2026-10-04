<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Policy;

use Nxi\Factro\Policy\RequestPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestPolicy::class)]
final class RequestPolicyTest extends TestCase
{
    public function testAllPermitsEverything(): void
    {
        self::assertTrue(RequestPolicy::all()->permits('DELETE', '/tasks/1'));
    }

    public function testReadOnlyPermitsOnlyGet(): void
    {
        $policy = RequestPolicy::readOnly();

        self::assertTrue($policy->permits('GET', '/tasks/1'));
        self::assertFalse($policy->permits('POST', '/tasks'));
        self::assertFalse($policy->permits('put', '/tasks/1'));
    }

    public function testWithoutDeletesAndDeniedPaths(): void
    {
        $policy = RequestPolicy::withoutDeletes()->denyPaths('#^/tasks/[^/]+/(package|project)$#');

        self::assertTrue($policy->permits('PUT', '/tasks/1/state'));
        self::assertFalse($policy->permits('PUT', '/tasks/1/package'));
        self::assertFalse($policy->permits('DELETE', '/tasks/1'));
        self::assertTrue($policy->permits('GET', '/tasks/1/package'));
    }

    public function testDenyPathsReturnsACopy(): void
    {
        $base = RequestPolicy::all();
        $derived = $base->denyPaths('#^/x$#');

        self::assertSame([], $base->deniedPathPatterns);
        self::assertSame(['#^/x$#'], $derived->deniedPathPatterns);
        self::assertSame($base->allowedMethods, $derived->allowedMethods);
    }
}
