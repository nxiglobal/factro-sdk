<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\User\Output\Absence;

/**
 * Rows and DTOs for Absence, based on fixtures/absences.json.
 */
final class AbsenceFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['absences', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Absence
    {
        return Absence::fromArray(self::make($overrides));
    }
}
