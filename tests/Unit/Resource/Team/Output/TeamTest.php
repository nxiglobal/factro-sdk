<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Team\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Team\Output\Team;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Team::class)]
final class TeamTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('teams')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $team = Team::fromArray($row);

        self::assertSame($row['id'], $team->id);
        self::assertSame($row['name'], $team->name);
        self::assertSame($row['color'], $team->color);
        self::assertTrue($team->isActive);
        self::assertSame($row['mandantId'], $team->mandantId);
    }

    public function testHydratesEveryFixtureRow(): void
    {
        $teams = array_map(static function (mixed $row): Team {
            self::assertIsArray($row);

            /* @var array<string, mixed> $row */
            return Team::fromArray($row);
        }, Fixtures::json('teams'));

        self::assertCount(3, $teams);
        self::assertFalse($teams[1]->isActive);
        self::assertNull($teams[2]->color);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'name', 'isActive', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Team::fromArray($row);
    }
}
