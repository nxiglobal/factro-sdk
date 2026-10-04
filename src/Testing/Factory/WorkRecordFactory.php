<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\WorkRecord\Output\WorkRecord;

/**
 * Rows and DTOs for WorkRecord, based on fixtures/work-records-by-project.json.
 */
final class WorkRecordFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['work-records-by-project', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): WorkRecord
    {
        return WorkRecord::fromArray(self::make($overrides));
    }
}
