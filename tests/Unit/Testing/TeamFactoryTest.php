<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\Team\Output\Team;
use Nxi\Factro\Testing\Factory\TeamFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TeamFactory::class)]
final class TeamFactoryTest extends TestCase
{
    public function testMakeAppliesOverridesAndDtoHydrates(): void
    {
        $row = TeamFactory::make(['name' => 'Custom', 'color' => null]);

        self::assertSame('Custom', $row['name']);
        self::assertArrayHasKey('color', $row);
        self::assertNull($row['color']);
        self::assertInstanceOf(Team::class, TeamFactory::dto());
        self::assertSame('Custom', TeamFactory::dto(['name' => 'Custom'])->name);
        self::assertFalse(TeamFactory::dto(['isActive' => false])->isActive);
        self::assertSame('a3e8eb42-6ada-519e-b57f-2f93743e7773', TeamFactory::dto()->id);
    }

    public function testManyProducesDistinctIds(): void
    {
        $rows = TeamFactory::many(2);

        self::assertCount(2, $rows);
        self::assertNotSame($rows[0]['id'], $rows[1]['id']);
        self::assertContainsOnlyInstancesOf(Team::class, array_map(Team::fromArray(...), $rows));
    }
}
