<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Bin;

use Nxi\Factro\Tests\Support\OpenApiDiff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiDiff::class)]
final class OpenApiDiffTest extends TestCase
{
    public function testDiffReportsAddedRemovedAndChangedPaths(): void
    {
        $old = ['paths' => ['/a' => ['get' => []], '/b' => ['get' => []]], 'components' => ['schemas' => ['S' => ['properties' => ['x' => []]]]]];
        $new = ['paths' => ['/a' => ['get' => [], 'post' => []], '/c' => ['get' => []]], 'components' => ['schemas' => ['S' => ['properties' => ['x' => [], 'y' => []]]]]];

        $report = OpenApiDiff::compare($old, $new);

        self::assertSame(['GET /c', 'POST /a'], $report['added_operations']);
        self::assertSame(['GET /b'], $report['removed_operations']);
        self::assertSame(['S' => ['added' => ['y'], 'removed' => []]], $report['changed_schemas']);
    }

    public function testIdenticalSpecsYieldEmptyReport(): void
    {
        $spec = ['paths' => ['/a' => ['get' => [], 'post' => []]], 'components' => ['schemas' => ['S' => ['properties' => ['x' => []]]]]];

        $report = OpenApiDiff::compare($spec, $spec);

        self::assertSame(['added_operations' => [], 'removed_operations' => [], 'changed_schemas' => []], $report);
        self::assertStringContainsString('none', OpenApiDiff::render($report));
    }

    public function testRenderListsEverything(): void
    {
        $out = OpenApiDiff::render(['added_operations' => ['GET /c'], 'removed_operations' => [], 'changed_schemas' => ['S' => ['added' => ['y'], 'removed' => ['z']]]]);

        self::assertStringContainsString('- GET /c', $out);
        self::assertStringContainsString('S', $out);
        self::assertStringContainsString('+ y', $out);
        self::assertStringContainsString('- z', $out);
    }

    public function testNonPathKeysAreIgnored(): void
    {
        $report = OpenApiDiff::compare(['paths' => ['/a' => ['get' => [], 'parameters' => []]]], ['paths' => ['/a' => ['get' => [], 'summary' => 'x']]]);

        self::assertSame([], $report['added_operations']);
        self::assertSame([], $report['removed_operations']);
    }
}
