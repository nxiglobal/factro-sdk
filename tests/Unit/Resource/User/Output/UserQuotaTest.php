<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\User\Output\UserQuota;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserQuota::class)]
final class UserQuotaTest extends TestCase
{
    private const array KEYS = [
        'maxPaidUserCount',
        'activePaidUserCount',
        'availablePaidUserSeats',
        'inactivePaidUserCount',
        'totalPaidUserCount',
        'activeGuestUserCount',
        'inactiveGuestUserCount',
        'totalGuestUserCount',
        'maxUserCount',
        'activeUserCount',
        'inactiveUserCount',
        'totalUserCount',
    ];

    /** @return array<string, mixed> */
    private function row(): array
    {
        $row = Fixtures::json('user-quota');

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $quota = UserQuota::fromArray($row);

        foreach (self::KEYS as $key) {
            self::assertSame($row[$key], $quota->{$key}, $key);
        }
    }

    public function testIntegralFloatsAreAcceptedAsInt(): void
    {
        $row = $this->row();
        $row['maxPaidUserCount'] = 25.0;

        self::assertSame(25, UserQuota::fromArray($row)->maxPaidUserCount);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (self::KEYS as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        UserQuota::fromArray($row);
    }
}
