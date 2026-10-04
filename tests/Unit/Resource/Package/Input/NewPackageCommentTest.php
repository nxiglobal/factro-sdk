<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Package\Input;

use Nxi\Factro\Resource\Package\Input\NewPackageComment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewPackageComment::class)]
final class NewPackageCommentTest extends TestCase
{
    public function testPayload(): void
    {
        self::assertSame(['text' => '<p>Hi</p>'], new NewPackageComment('<p>Hi</p>')->toPayload());
        self::assertSame(['text' => 'x', 'parentCommentId' => 'c1'], new NewPackageComment('x', 'c1')->toPayload());
    }
}
