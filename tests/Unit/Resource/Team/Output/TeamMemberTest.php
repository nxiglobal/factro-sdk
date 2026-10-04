<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Team\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Team\Output\TeamMember;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TeamMember::class)]
final class TeamMemberTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('team-members')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $member = TeamMember::fromArray($row);

        self::assertSame($row['id'], $member->id);
        self::assertSame($row['teamId'], $member->teamId);
        self::assertSame($row['employeeId'], $member->employeeId);
        self::assertSame($row['mandantId'], $member->mandantId);
    }

    public function testHydratesEveryFixtureRow(): void
    {
        $members = array_map(static function (mixed $row): TeamMember {
            self::assertIsArray($row);

            /* @var array<string, mixed> $row */
            return TeamMember::fromArray($row);
        }, Fixtures::json('team-members'));

        self::assertCount(2, $members);
        self::assertSame($members[0]->teamId, $members[1]->teamId);
        self::assertNotSame($members[0]->employeeId, $members[1]->employeeId);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'teamId', 'employeeId', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        TeamMember::fromArray($row);
    }
}
