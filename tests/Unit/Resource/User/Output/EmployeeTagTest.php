<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\User\Output\EmployeeTag;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmployeeTag::class)]
final class EmployeeTagTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function fixtures(): iterable
    {
        yield 'employee-tags' => ['employee-tags'];
        yield 'user-tags' => ['user-tags'];
    }

    #[DataProvider('fixtures')]
    public function testHydratesFromFixture(string $fixture): void
    {
        $rows = Fixtures::json($fixture);
        self::assertNotEmpty($rows);
        foreach ($rows as $row) {
            self::assertIsArray($row);
            /** @var array<string, mixed> $row */
            $tag = EmployeeTag::fromArray($row);
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
        $row = Fixtures::json('employee-tags')[0];
        self::assertIsArray($row);
        /* @var array<string, mixed> $row */
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        EmployeeTag::fromArray($row);
    }
}
