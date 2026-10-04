<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project\Input;

use Nxi\Factro\Resource\Project\Input\NewProjectComment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewProjectComment::class)]
final class NewProjectCommentTest extends TestCase
{
    public function testPayload(): void
    {
        self::assertSame(['text' => '<p>Hi</p>'], new NewProjectComment('<p>Hi</p>')->toPayload());
        self::assertSame(['text' => 'x', 'parentCommentId' => 'c1'], new NewProjectComment('x', 'c1')->toPayload());
    }
}
