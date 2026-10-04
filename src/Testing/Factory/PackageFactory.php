<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Package\Output\Package;

/**
 * Rows and DTOs for Package, based on fixtures/packages.json.
 */
final class PackageFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['packages', 1];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Package
    {
        return Package::fromArray(self::make($overrides));
    }
}
