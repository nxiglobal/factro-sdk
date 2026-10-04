<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Note\Input;

use Nxi\Factro\Resource\Note\Input\NewNoteComment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewNoteComment::class)]
final class NewNoteCommentTest extends TestCase
{
    public function testPayload(): void
    {
        self::assertSame(['text' => '<p>Hi</p>'], new NewNoteComment('<p>Hi</p>')->toPayload());
        self::assertSame(['text' => 'x', 'parentCommentId' => 'c1'], new NewNoteComment('x', 'c1')->toPayload());
    }
}
