<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Input;

use Nxi\Factro\Resource\Task\Input\TaskConnectionInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskConnectionInput::class)]
final class TaskConnectionInputTest extends TestCase
{
    public function testPayloadAlwaysContainsAllThreeKeys(): void
    {
        self::assertSame(['connectedTaskId' => 't2', 'isPredecessor' => false, 'isSuccessor' => false], new TaskConnectionInput('t2')->toPayload());
        self::assertSame(['connectedTaskId' => 't2', 'isPredecessor' => true, 'isSuccessor' => false], new TaskConnectionInput('t2', isPredecessor: true)->toPayload());
        self::assertSame(['connectedTaskId' => 't2', 'isPredecessor' => false, 'isSuccessor' => true], new TaskConnectionInput('t2', isSuccessor: true)->toPayload());
    }

    public function testPredecessorAndSuccessorAreMutuallyExclusive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TaskConnectionInput('t2', isPredecessor: true, isSuccessor: true);
    }
}
