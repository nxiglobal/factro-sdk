<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\CustomView\Input;

use Nxi\Factro\Resource\CustomView\Input\CustomViewChanges;
use Nxi\Factro\Resource\CustomView\Output\SortEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CustomViewChanges::class)]
final class CustomViewChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSent(): void
    {
        $changes = new CustomViewChanges(description: '<p>x</p>', sorting: [new SortEntry('title')], config: ['showClosed' => true]);

        self::assertSame([
            'description' => '<p>x</p>',
            'sorting' => [['id' => 'title', 'desc' => false]],
            'config' => ['showClosed' => true],
        ], $changes->toPayload());
    }

    public function testAllFieldsInApiOrder(): void
    {
        $changes = new CustomViewChanges(
            description: 'd',
            columnOrder: [['title', true]],
            title: 't',
            filters: ['a' => 1],
            sorting: [],
            grouping: ['g'],
            config: [],
        );

        self::assertSame(['description', 'columnOrder', 'title', 'filters', 'sorting', 'grouping', 'config'], array_keys($changes->toPayload()));
    }

    public function testIsEmpty(): void
    {
        self::assertTrue(new CustomViewChanges()->isEmpty());
        self::assertFalse(new CustomViewChanges(title: 'x')->isEmpty());
        self::assertFalse(new CustomViewChanges(grouping: [])->isEmpty());
    }
}
