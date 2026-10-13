<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit;

use Nxi\Factro\FactroOptions;
use Nxi\Factro\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $options = new FactroOptions(baseUrl: 'https://factro.test/api/core', maxRetries: 0);

        self::assertSame('https://factro.test/api/core', $options->baseUrl);
        self::assertSame(0, $options->maxRetries);
    }

    /**
     * @param \Closure(): FactroOptions $create
     */
    #[DataProvider('invalidOptions')]
    public function testRejectsInvalidValue(\Closure $create, string $option): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Option "%s"', $option));

        $create();
    }

    /**
     * @return iterable<string, array{\Closure(): FactroOptions, string}>
     */
    public static function invalidOptions(): iterable
    {
        yield 'http base URL' => [static fn (): FactroOptions => new FactroOptions(baseUrl: 'http://factro.test/api/core'), 'baseUrl'];
        yield 'relative base URL' => [static fn (): FactroOptions => new FactroOptions(baseUrl: '/api/core'), 'baseUrl'];
        yield 'empty base URL' => [static fn (): FactroOptions => new FactroOptions(baseUrl: ''), 'baseUrl'];
        yield 'base URL without host' => [static fn (): FactroOptions => new FactroOptions(baseUrl: 'https:///api/core'), 'baseUrl'];
        yield 'zero request timeout' => [static fn (): FactroOptions => new FactroOptions(requestTimeoutSeconds: 0.0), 'requestTimeoutSeconds'];
        yield 'negative inactivity timeout' => [static fn (): FactroOptions => new FactroOptions(inactivityTimeoutSeconds: -1.0), 'inactivityTimeoutSeconds'];
        yield 'negative retries' => [static fn (): FactroOptions => new FactroOptions(maxRetries: -1), 'maxRetries'];
        yield 'negative retry-after cap' => [static fn (): FactroOptions => new FactroOptions(maxRetryAfterSeconds: -1), 'maxRetryAfterSeconds'];
        yield 'user agent with CRLF' => [static fn (): FactroOptions => new FactroOptions(userAgent: "sdk\r\nX-Injected: 1"), 'userAgent'];
    }
}
