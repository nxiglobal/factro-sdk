<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Mapping;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Resource\Task\TaskPriority;
use Nxi\Factro\Resource\Task\TaskState;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Field::class)]
final class FieldTest extends TestCase
{
    private const string OWNER = Task::class;

    public function testStringRequiresPresentNonNullString(): void
    {
        self::assertSame('a', Field::string(['k' => 'a'], 'k', self::OWNER));
        $this->assertHydrationError(static fn () => Field::string(['k' => null], 'k', self::OWNER), 'expected string, got null');
        $this->assertHydrationError(static fn () => Field::string([], 'k', self::OWNER), 'expected string, got null');
        $this->assertHydrationError(static fn () => Field::string(['k' => 1], 'k', self::OWNER), 'expected string, got int 1');
    }

    public function testNullableStringTreatsMissingAsNull(): void
    {
        self::assertNull(Field::nullableString([], 'k', self::OWNER));
        self::assertNull(Field::nullableString(['k' => null], 'k', self::OWNER));
        self::assertSame('', Field::nullableString(['k' => ''], 'k', self::OWNER));
        $this->assertHydrationError(static fn () => Field::nullableString(['k' => 1], 'k', self::OWNER), 'expected ?string, got int 1');
    }

    public function testBoolHelpers(): void
    {
        self::assertTrue(Field::bool(['k' => true], 'k', self::OWNER));
        self::assertFalse(Field::bool(['k' => false], 'k', self::OWNER));
        self::assertNull(Field::nullableBool([], 'k', self::OWNER));
        self::assertTrue(Field::nullableBool(['k' => true], 'k', self::OWNER));
        $this->assertHydrationError(static fn () => Field::bool(['k' => 1], 'k', self::OWNER), 'expected bool, got int 1');
        $this->assertHydrationError(static fn () => Field::bool([], 'k', self::OWNER), 'expected bool, got null');
    }

    public function testFloatAcceptsIntAndFloat(): void
    {
        self::assertSame(3.0, Field::float(['k' => 3], 'k', self::OWNER));
        self::assertSame(2.5, Field::nullableFloat(['k' => 2.5], 'k', self::OWNER));
        self::assertSame(3.0, Field::nullableFloat(['k' => 3], 'k', self::OWNER));
        self::assertNull(Field::nullableFloat([], 'k', self::OWNER));
        $this->assertHydrationError(static fn () => Field::float(['k' => '3'], 'k', self::OWNER), 'expected float, got string "3"');
        $this->assertHydrationError(static fn () => Field::float([], 'k', self::OWNER), 'expected float, got null');
    }

    public function testIntAcceptsIntegralFloat(): void
    {
        self::assertSame(3, Field::int(['k' => 3], 'k', self::OWNER));
        self::assertSame(3, Field::int(['k' => 3.0], 'k', self::OWNER));
        self::assertSame(3, Field::nullableInt(['k' => 3], 'k', self::OWNER));
        self::assertNull(Field::nullableInt([], 'k', self::OWNER));
        $this->assertHydrationError(static fn () => Field::int(['k' => 3.5], 'k', self::OWNER), 'expected int, got float 3.5');
        $this->assertHydrationError(static fn () => Field::int(['k' => '3'], 'k', self::OWNER), 'expected int, got string "3"');
    }

    public function testEnumRejectsUnknownValue(): void
    {
        self::assertSame(TaskState::CLOSED, Field::enum(['k' => 'closed'], 'k', self::OWNER, TaskState::class));
        self::assertSame(TaskPriority::PRIORITY_10, Field::nullableEnum(['k' => 10], 'k', self::OWNER, TaskPriority::class));
        self::assertNull(Field::nullableEnum(['k' => null], 'k', self::OWNER, TaskPriority::class));
        self::assertNull(Field::nullableEnum([], 'k', self::OWNER, TaskPriority::class));
        $this->assertHydrationError(static fn () => Field::enum(['k' => 'archived'], 'k', self::OWNER, TaskState::class), 'expected TaskState, got string "archived"');
        $this->assertHydrationError(static fn () => Field::enum([], 'k', self::OWNER, TaskState::class), 'expected TaskState, got null');
        $this->assertHydrationError(static fn () => Field::nullableEnum(['k' => true], 'k', self::OWNER, TaskState::class), 'expected ?TaskState, got bool true');
        $this->assertHydrationError(static fn () => Field::enum(['k' => 15], 'k', self::OWNER, TaskPriority::class), 'expected TaskPriority, got int 15');
    }

    public function testDateHelpers(): void
    {
        self::assertSame('2026-08-31T22:00:00.000Z', FactroDateTime::toIsoUtc(Field::isoUtc(['k' => '2026-08-31T22:00:00.000Z'], 'k', self::OWNER)));
        self::assertNull(Field::nullableIsoUtc([], 'k', self::OWNER));
        self::assertNull(Field::nullableIsoUtc(['k' => null], 'k', self::OWNER));
        self::assertSame('2025-09-01T08:00:00.000Z', FactroDateTime::toIsoUtc(Field::jsTimestamp(['k' => 1756713600000], 'k', self::OWNER)));
        self::assertSame('2025-09-01T08:00:00.000Z', FactroDateTime::toIsoUtc(Field::jsTimestamp(['k' => 1756713600000.0], 'k', self::OWNER)));
        self::assertNull(Field::nullableJsTimestamp([], 'k', self::OWNER));
        self::assertSame('2026-09-01', Field::ymd(['k' => '2026-09-01'], 'k', self::OWNER)->toYmd());
        self::assertNull(Field::nullableYmd([], 'k', self::OWNER));
        $this->assertHydrationError(static fn () => Field::isoUtc(['k' => '2026-08-31'], 'k', self::OWNER), 'expected ISO-8601 UTC timestamp, got string "2026-08-31"');
        $this->assertHydrationError(static fn () => Field::isoUtc([], 'k', self::OWNER), 'expected ISO-8601 UTC timestamp, got null');
        $this->assertHydrationError(static fn () => Field::jsTimestamp(['k' => '1756713600000'], 'k', self::OWNER), 'expected JavaScript timestamp, got string "1756713600000"');
        $this->assertHydrationError(static fn () => Field::ymd(['k' => '2026-02-30'], 'k', self::OWNER), 'expected Y-m-d date, got string "2026-02-30"');
    }

    public function testArrayHelpers(): void
    {
        self::assertSame([], Field::array([], 'k', self::OWNER));
        self::assertSame(['d' => 1], Field::array([], 'k', self::OWNER, ['d' => 1]));
        self::assertSame(['a' => 1], Field::array(['k' => ['a' => 1]], 'k', self::OWNER));
        self::assertSame([], Field::array(['k' => null], 'k', self::OWNER));
        $this->assertHydrationError(static fn () => Field::array(['k' => 'x'], 'k', self::OWNER), 'expected array, got string "x"');
        self::assertSame([], Field::stringList([], 'k', self::OWNER));
        self::assertSame(['x', 'y'], Field::stringList(['k' => ['x', 'y']], 'k', self::OWNER));
        $this->assertHydrationError(static fn () => Field::stringList(['k' => ['x', 1]], 'k', self::OWNER), 'expected list<string>, got array');
        $hydrated = Field::objectList(['k' => [['id' => '1'], ['id' => '2']]], 'k', self::OWNER, static fn (array $row): mixed => $row['id']);
        self::assertSame(['1', '2'], $hydrated);
        self::assertSame([], Field::objectList([], 'k', self::OWNER, static fn (array $row): mixed => $row));
        $this->assertHydrationError(static fn () => Field::objectList(['k' => ['x']], 'k', self::OWNER, static fn (array $row): mixed => $row), 'expected list<array>, got array');
    }

    private function assertHydrationError(\Closure $call, string $expectedSuffix): void
    {
        try {
            $call();
            self::fail('expected HydrationException');
        } catch (HydrationException $e) {
            self::assertSame('k', $e->field);
            self::assertSame(self::OWNER, $e->owner);
            self::assertStringEndsWith($expectedSuffix.'.', $e->getMessage());
        }
    }
}
