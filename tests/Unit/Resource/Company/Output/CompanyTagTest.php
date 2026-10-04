<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Company\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Company\Output\CompanyTag;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompanyTag::class)]
final class CompanyTagTest extends TestCase
{
    public function testHydratesFromFixture(): void
    {
        $rows = Fixtures::json('company-tags');
        self::assertCount(3, $rows);
        foreach ($rows as $row) {
            self::assertIsArray($row);
            /** @var array<string, mixed> $row */
            $tag = CompanyTag::fromArray($row);
            self::assertSame($row['id'], $tag->id);
            self::assertSame($row['name'], $tag->name);
            self::assertSame($row['mandantId'], $tag->mandantId);
            if (is_string($row['changeDate'])) {
                self::assertSame($row['changeDate'], FactroDateTime::toIsoUtc($tag->changeDate ?? throw new \LogicException()));
            } else {
                self::assertNull($tag->changeDate);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'name', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = Fixtures::json('company-tags')[0];
        self::assertIsArray($row);
        /* @var array<string, mixed> $row */
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        CompanyTag::fromArray($row);
    }
}
