<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\User\Output\User;

/**
 * Rows and DTOs for User, based on fixtures/users.json.
 */
final class UserFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['users', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): User
    {
        return User::fromArray(self::make($overrides));
    }
}
