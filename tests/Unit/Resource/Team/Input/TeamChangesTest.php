<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Team\Input;

use Nxi\Factro\Resource\Team\Input\TeamChanges;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TeamChanges::class)]
final class TeamChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSent(): void
    {
        self::assertSame(['name' => 'Renamed', 'color' => '#000000'], new TeamChanges(name: 'Renamed', color: '#000000')->toPayload());
    }

    public function testFalseIsSentAndIsEmptyWorks(): void
    {
        self::assertSame(['isActive' => false], new TeamChanges(isActive: false)->toPayload());
        self::assertTrue(new TeamChanges()->isEmpty());
        self::assertFalse(new TeamChanges(isActive: false)->isEmpty());
    }
}
