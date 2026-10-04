<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Task\Output\TaskTag;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskTag::class)]
final class TaskTagTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('task-tags')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $tag = TaskTag::fromArray($row);

        self::assertSame($row['id'], $tag->id);
        self::assertSame($row['name'], $tag->name);
        self::assertIsString($row['changeDate']);
        self::assertSame($row['changeDate'], FactroDateTime::toIsoUtc($tag->changeDate ?? throw new \LogicException()));
        self::assertSame($row['mandantId'], $tag->mandantId);
    }

    public function testNullChangeDate(): void
    {
        self::assertNull(TaskTag::fromArray($this->row(1))->changeDate);
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
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        TaskTag::fromArray($row);
    }
}
