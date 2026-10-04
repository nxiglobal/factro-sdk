<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Project\Output\Project;

/**
 * Rows and DTOs for Project, based on fixtures/projects.json.
 */
final class ProjectFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['projects', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Project
    {
        return Project::fromArray(self::make($overrides));
    }
}
