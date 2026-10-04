<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Input;

use Nxi\Factro\Resource\Task\Input\NewTaskComment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewTaskComment::class)]
final class NewTaskCommentTest extends TestCase
{
    public function testPayload(): void
    {
        self::assertSame(['text' => '<p>Hi</p>'], new NewTaskComment('<p>Hi</p>')->toPayload());
        self::assertSame(['text' => 'x', 'parentCommentId' => 'c1'], new NewTaskComment('x', 'c1')->toPayload());
    }
}
