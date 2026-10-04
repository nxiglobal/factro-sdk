<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\CustomView\Input;

use Nxi\Factro\Resource\CustomView\CustomViewReferenceType;
use Nxi\Factro\Resource\CustomView\Input\NewCustomView;
use Nxi\Factro\Resource\CustomView\Output\SortEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewCustomView::class)]
final class NewCustomViewTest extends TestCase
{
    public function testMinimalPayload(): void
    {
        self::assertSame(
            ['referenceId' => 'r1', 'referenceType' => 'Room', 'title' => 'T', 'sourceTemplateId' => 'tpl'],
            new NewCustomView('r1', CustomViewReferenceType::ROOM, 'T', 'tpl')->toPayload(),
        );
    }

    public function testFullPayload(): void
    {
        $input = new NewCustomView(
            referenceId: 'p1',
            referenceType: CustomViewReferenceType::PROJECT,
            title: 'T',
            sourceTemplateId: 'tpl',
            columnOrder: [['title', true]],
            filters: ['taskState' => ['planned']],
            sorting: [new SortEntry('endDate', true)],
            grouping: ['officerId'],
            config: ['showClosed' => false],
        );

        self::assertSame([
            'referenceId' => 'p1',
            'referenceType' => 'Project',
            'title' => 'T',
            'sourceTemplateId' => 'tpl',
            'columnOrder' => [['title', true]],
            'filters' => ['taskState' => ['planned']],
            'sorting' => [['id' => 'endDate', 'desc' => true]],
            'grouping' => ['officerId'],
            'config' => ['showClosed' => false],
        ], $input->toPayload());
    }

    public function testEmptyArraysAreSent(): void
    {
        $payload = new NewCustomView('p1', CustomViewReferenceType::PROJECT, 'T', 'tpl', sorting: [], grouping: [])->toPayload();

        self::assertSame([], $payload['sorting']);
        self::assertSame([], $payload['grouping']);
    }
}
