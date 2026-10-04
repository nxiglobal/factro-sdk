<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit;

use Nxi\Factro\FactroOptions;
use Nxi\Factro\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\NativeClock;

#[CoversClass(FactroOptions::class)]
final class FactroOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new FactroOptions();

        self::assertSame('https://cloud.factro.com/api/core', $options->baseUrl);
        self::assertSame(20.0, $options->requestTimeoutSeconds);
        self::assertSame(10.0, $options->inactivityTimeoutSeconds);
        self::assertSame('Europe/Berlin', $options->timezone->getName());
        self::assertSame(['GET', 'POST', 'PUT', 'DELETE'], $options->policy->allowedMethods);
        self::assertNull($options->limiterFactory);
        self::assertSame(3, $options->maxRetries);
        self::assertSame(5, $options->maxRetryAfterSeconds);
        self::assertInstanceOf(NullLogger::class, $options->logger);
        self::assertInstanceOf(NativeClock::class, $options->clock);
        self::assertNull($options->onRateLimit);
        self::assertSame('nxi-factro-sdk/'.Version::CURRENT, $options->userAgent);
        self::assertTrue($options->sendCreationDate);
    }

    public function testNamedArgumentsOverride(): void
    {
        $options = new FactroOptions(baseUrl: 'http://factro.test/api/core', maxRetries: 0);

        self::assertSame('http://factro.test/api/core', $options->baseUrl);
        self::assertSame(0, $options->maxRetries);
    }
}
