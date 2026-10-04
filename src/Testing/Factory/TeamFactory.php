<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Team\Output\Team;

/**
 * Rows and DTOs for Team, based on fixtures/teams.json.
 */
final class TeamFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['teams', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Team
    {
        return Team::fromArray(self::make($overrides));
    }
}
