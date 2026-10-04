<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Appointment\Output\Appointment;

/**
 * Rows and DTOs for Appointment, based on fixtures/appointments.json.
 */
final class AppointmentFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['appointments', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Appointment
    {
        return Appointment::fromArray(self::make($overrides));
    }
}
