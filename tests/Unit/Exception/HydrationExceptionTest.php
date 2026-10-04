<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Exception;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Task\Output\Task;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HydrationException::class)]
final class HydrationExceptionTest extends TestCase
{
    public function testMessageDescribesOwnerFieldExpectationAndActualValue(): void
    {
        $e = new HydrationException(Task::class, 'taskState', 'TaskState', 'archived');

        self::assertSame('Cannot hydrate Nxi\Factro\Resource\Task\Output\Task::$taskState: expected TaskState, got string "archived".', $e->getMessage());
        self::assertSame(Task::class, $e->owner);
        self::assertSame('taskState', $e->field);
        self::assertSame('TaskState', $e->expected);
    }

    public function testActualValueFormatting(): void
    {
        self::assertStringEndsWith('got null.', new HydrationException('O', 'f', 'string', null)->getMessage());
        self::assertStringEndsWith('got int 1.', new HydrationException('O', 'f', 'string', 1)->getMessage());
        self::assertStringEndsWith('got float 3.5.', new HydrationException('O', 'f', 'int', 3.5)->getMessage());
        self::assertStringEndsWith('got bool true.', new HydrationException('O', 'f', 'string', true)->getMessage());
        self::assertStringEndsWith('got array.', new HydrationException('O', 'f', 'string', ['a'])->getMessage());
        self::assertStringEndsWith('got object stdClass.', new HydrationException('O', 'f', 'string', new \stdClass())->getMessage());
    }
}
