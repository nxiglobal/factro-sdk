<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Task\Output\ChecklistEntry;

/**
 * Rows and DTOs for ChecklistEntry, based on fixtures/checklist.json.
 */
final class ChecklistEntryFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['checklist', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): ChecklistEntry
    {
        return ChecklistEntry::fromArray(self::make($overrides));
    }
}
