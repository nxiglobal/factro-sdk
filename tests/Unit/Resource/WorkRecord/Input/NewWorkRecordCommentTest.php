<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\WorkRecord\Input;

use Nxi\Factro\Resource\WorkRecord\Input\NewWorkRecordComment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewWorkRecordComment::class)]
final class NewWorkRecordCommentTest extends TestCase
{
    public function testPayload(): void
    {
        self::assertSame(['text' => '<p>Hi</p>'], new NewWorkRecordComment('<p>Hi</p>')->toPayload());
        self::assertSame(['text' => 'x', 'parentCommentId' => 'c1'], new NewWorkRecordComment('x', 'c1')->toPayload());
    }
}
