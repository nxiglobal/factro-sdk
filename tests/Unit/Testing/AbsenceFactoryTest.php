<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\User\AbsenceType;
use Nxi\Factro\Resource\User\Output\Absence;
use Nxi\Factro\Testing\Factory\AbsenceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbsenceFactory::class)]
final class AbsenceFactoryTest extends TestCase
{
    public function testMakeAppliesOverridesAndDtoHydrates(): void
    {
        $row = AbsenceFactory::make(['type' => 'Unplanned', 'employeeId' => 'u9']);

        self::assertSame('Unplanned', $row['type']);
        self::assertSame('u9', $row['employeeId']);
        self::assertInstanceOf(Absence::class, AbsenceFactory::dto());
        self::assertSame(AbsenceType::UNPLANNED, AbsenceFactory::dto(['type' => 'Unplanned'])->type);
    }

    public function testManyProducesDistinctIds(): void
    {
        $rows = AbsenceFactory::many(3);

        self::assertCount(3, $rows);
        $ids = [];
        foreach ($rows as $row) {
            self::assertIsString($row['id']);
            $ids[] = $row['id'];
        }
        self::assertCount(3, array_unique($ids));
        self::assertContainsOnlyInstancesOf(Absence::class, array_map(Absence::fromArray(...), $rows));
    }
}
