<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\CustomView\CustomViewViewType;
use Nxi\Factro\Resource\CustomView\Output\CustomView;
use Nxi\Factro\Testing\Factory\CustomViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CustomViewFactory::class)]
final class CustomViewFactoryTest extends TestCase
{
    public function testMakeAppliesOverridesAndDtoHydrates(): void
    {
        $row = CustomViewFactory::make(['title' => 'Custom', 'referenceId' => null]);

        self::assertSame('Custom', $row['title']);
        self::assertArrayHasKey('referenceId', $row);
        self::assertNull($row['referenceId']);

        $dto = CustomViewFactory::dto(['viewType' => 'Gantt']);
        self::assertInstanceOf(CustomView::class, $dto);
        self::assertSame(CustomViewViewType::GANTT, $dto->viewType);
        self::assertCount(2, $dto->sorting);
    }

    public function testManyProducesDistinctIds(): void
    {
        $rows = CustomViewFactory::many(3);

        self::assertCount(3, $rows);
        $ids = [];
        foreach ($rows as $row) {
            self::assertIsString($row['id']);
            $ids[] = $row['id'];
        }
        self::assertCount(3, array_unique($ids));
        self::assertContainsOnlyInstancesOf(CustomView::class, array_map(CustomView::fromArray(...), $rows));
    }
}
