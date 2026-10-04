<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\CustomView\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\CustomView\CustomViewReferenceType;
use Nxi\Factro\Resource\CustomView\CustomViewViewType;
use Nxi\Factro\Resource\CustomView\Output\CustomView;
use Nxi\Factro\Resource\CustomView\Output\SortEntry;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CustomView::class)]
final class CustomViewTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('custom-views')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $view = CustomView::fromArray($row);

        self::assertSame($row['id'], $view->id);
        self::assertSame($row['title'], $view->title);
        self::assertSame($row['description'], $view->description);
        self::assertSame($row['referenceId'], $view->referenceId);
        self::assertSame(CustomViewReferenceType::PROJECT, $view->referenceType);
        self::assertSame(CustomViewViewType::KANBAN, $view->viewType);
        self::assertSame([['title', true], ['officerId', false]], $view->columnOrder);
        self::assertSame(['taskState' => ['planned', 'inProcess']], $view->filters);
        self::assertCount(2, $view->sorting);
        self::assertContainsOnlyInstancesOf(SortEntry::class, $view->sorting);
        self::assertSame('endDate', $view->sorting[0]->id);
        self::assertFalse($view->sorting[0]->desc);
        self::assertTrue($view->sorting[1]->desc);
        self::assertSame(['taskState'], $view->grouping);
        self::assertSame(['showClosed' => false], $view->config);
        self::assertFalse($view->isTemplate);
        self::assertSame($row['sourceTemplateId'], $view->sourceTemplateId);
        self::assertSame(2, $view->position);
        self::assertIsInt($row['createdAt']);
        self::assertSame(intdiv($row['createdAt'], 1000), $view->createdAt?->getTimestamp());
        self::assertIsInt($row['updatedAt']);
        self::assertSame(intdiv($row['updatedAt'], 1000), $view->updatedAt?->getTimestamp());
        self::assertSame($row['mandantId'], $view->mandantId);
    }

    public function testSingleObjectFixtureMatchesListRow(): void
    {
        $single = Fixtures::json('custom-view');

        /* @var array<string, mixed> $single */
        self::assertEquals(CustomView::fromArray($this->row()), CustomView::fromArray($single));
    }

    public function testTemplateRowWithNulls(): void
    {
        $view = CustomView::fromArray($this->row(1));

        self::assertTrue($view->isTemplate);
        self::assertNull($view->description);
        self::assertNull($view->referenceId);
        self::assertNull($view->referenceType);
        self::assertSame(CustomViewViewType::GRID, $view->viewType);
        self::assertSame([], $view->columnOrder);
        self::assertSame([], $view->filters);
        self::assertSame([], $view->sorting);
        self::assertSame([], $view->grouping);
        self::assertSame([], $view->config);
        self::assertNull($view->sourceTemplateId);
        self::assertNull($view->position);
        self::assertNull($view->updatedAt);
    }

    public function testOnlyIdIsRequiredAndDefaultsApply(): void
    {
        $view = CustomView::fromArray(['id' => 'v']);

        self::assertSame('v', $view->id);
        self::assertNull($view->title);
        self::assertNull($view->viewType);
        self::assertFalse($view->isTemplate);
        self::assertSame([], $view->sorting);
        self::assertNull($view->createdAt);
        self::assertNull($view->mandantId);
    }

    public function testMissingIdThrows(): void
    {
        $row = $this->row();
        unset($row['id']);

        $this->expectException(HydrationException::class);
        CustomView::fromArray($row);
    }

    public function testUnknownViewTypeThrows(): void
    {
        $row = $this->row();
        $row['viewType'] = 'Timeline';

        $this->expectException(HydrationException::class);
        CustomView::fromArray($row);
    }
}
