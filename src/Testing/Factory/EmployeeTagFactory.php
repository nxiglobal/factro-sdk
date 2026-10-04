<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\User\Output\EmployeeTag;

/**
 * Rows and DTOs for EmployeeTag, based on fixtures/employee-tags.json.
 */
final class EmployeeTagFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['employee-tags', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): EmployeeTag
    {
        return EmployeeTag::fromArray(self::make($overrides));
    }
}
