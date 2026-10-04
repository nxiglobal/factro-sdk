<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Bin;

use Nxi\Factro\Tests\Support\OpenApiDiff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiDiff::class)]
final class OpenApiExtractTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function scripts(): iterable
    {
        // Single-line literal, as factro served it until 2026-09.
        yield 'single line' => ["var options = {\n\"swaggerDoc\": {\"openapi\":\"3.0.0\",\"paths\":{\"/a\":{}}},\n\"customOptions\": {}\n};"];
        // Pretty-printed literal with "openapi" last and a JS arrow function in customOptions, as served on 2026-10-02.
        yield 'pretty printed' => ["var options = {\n  \"swaggerDoc\": {\n    \"paths\": {\n      \"/a\": {}\n    },\n    \"openapi\": \"3.0.0\"\n  },\n  \"customOptions\": {\n    \"operationsSorter\": (a, b) => 0\n  }\n};"];
    }

    #[DataProvider('scripts')]
    public function testExtractsSpecRegardlessOfFormatting(string $script): void
    {
        self::assertSame(['/a' => []], OpenApiDiff::extractSpec($script)['paths']);
    }

    public function testRejectsScriptWithoutSpec(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        OpenApiDiff::extractSpec('window.onload = function() {};');
    }
}
