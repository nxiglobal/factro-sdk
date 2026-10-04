<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Team\Input;

use Nxi\Factro\Resource\Team\Input\NewTeam;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewTeam::class)]
final class NewTeamTest extends TestCase
{
    public function testPayloadDefaultsToActive(): void
    {
        self::assertSame(['name' => 'Support', 'color' => '#1e90ff', 'isActive' => true], new NewTeam('Support', '#1e90ff')->toPayload());
    }

    public function testInactiveTeamIsSentExplicitly(): void
    {
        self::assertSame(['name' => 'Archive', 'color' => '#808080', 'isActive' => false], new NewTeam('Archive', '#808080', isActive: false)->toPayload());
    }
}
