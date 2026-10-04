<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Company\Output\Company;

/**
 * Rows and DTOs for Company, based on fixtures/companies.json.
 */
final class CompanyFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['companies', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Company
    {
        return Company::fromArray(self::make($overrides));
    }
}
