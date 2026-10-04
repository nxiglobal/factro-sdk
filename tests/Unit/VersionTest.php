<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit;

use Nxi\Factro\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Version::class)]
final class VersionTest extends TestCase
{
    public function testCurrentVersionIsSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-dev)?$/', Version::CURRENT);
    }
}
