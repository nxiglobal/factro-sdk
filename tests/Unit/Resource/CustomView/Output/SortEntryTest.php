<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\CustomView\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\CustomView\Output\SortEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SortEntry::class)]
final class SortEntryTest extends TestCase
{
    public function testHydratesAndDescDefaultsToFalse(): void
    {
        self::assertTrue(SortEntry::fromArray(['id' => 'title', 'desc' => true])->desc);
        self::assertFalse(SortEntry::fromArray(['id' => 'title'])->desc);
        self::assertSame('title', SortEntry::fromArray(['id' => 'title'])->id);
    }

    public function testPayloadRoundTrip(): void
    {
        self::assertSame(['id' => 'endDate', 'desc' => true], new SortEntry('endDate', true)->toPayload());
        self::assertSame(['id' => 'endDate', 'desc' => false], new SortEntry('endDate')->toPayload());
    }

    public function testMissingIdThrows(): void
    {
        $this->expectException(HydrationException::class);
        SortEntry::fromArray(['desc' => true]);
    }
}
