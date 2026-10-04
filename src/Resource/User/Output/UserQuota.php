<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User\Output;

use Nxi\Factro\Mapping\Field;

/**
 * The tenant's user quota (IGetUserQuotaResponse). The API declares the counters as doubles; they are integral.
 */
final readonly class UserQuota
{
    public function __construct(
        public int $maxPaidUserCount,
        public int $activePaidUserCount,
        public int $availablePaidUserSeats,
        public int $inactivePaidUserCount,
        public int $totalPaidUserCount,
        public int $activeGuestUserCount,
        public int $inactiveGuestUserCount,
        public int $totalGuestUserCount,
        public int $maxUserCount,
        public int $activeUserCount,
        public int $inactiveUserCount,
        public int $totalUserCount,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            maxPaidUserCount: Field::int($data, 'maxPaidUserCount', $o),
            activePaidUserCount: Field::int($data, 'activePaidUserCount', $o),
            availablePaidUserSeats: Field::int($data, 'availablePaidUserSeats', $o),
            inactivePaidUserCount: Field::int($data, 'inactivePaidUserCount', $o),
            totalPaidUserCount: Field::int($data, 'totalPaidUserCount', $o),
            activeGuestUserCount: Field::int($data, 'activeGuestUserCount', $o),
            inactiveGuestUserCount: Field::int($data, 'inactiveGuestUserCount', $o),
            totalGuestUserCount: Field::int($data, 'totalGuestUserCount', $o),
            maxUserCount: Field::int($data, 'maxUserCount', $o),
            activeUserCount: Field::int($data, 'activeUserCount', $o),
            inactiveUserCount: Field::int($data, 'inactiveUserCount', $o),
            totalUserCount: Field::int($data, 'totalUserCount', $o),
        );
    }
}
