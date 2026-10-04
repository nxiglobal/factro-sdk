<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task\Input;

use Nxi\Factro\Resource\Task\Input\NewChecklistEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewChecklistEntry::class)]
final class NewChecklistEntryTest extends TestCase
{
    public function testPayloadAlwaysContainsChecked(): void
    {
        self::assertSame(['title' => 'Item', 'checked' => false], new NewChecklistEntry('Item')->toPayload());
        self::assertSame(['title' => 'Item', 'checked' => true], new NewChecklistEntry('Item', checked: true)->toPayload());
    }

    public function testEmptyTitleIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NewChecklistEntry(' ');
    }
}
