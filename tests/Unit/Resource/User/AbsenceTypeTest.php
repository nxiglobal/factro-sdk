<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User;

use Nxi\Factro\Resource\User\AbsenceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbsenceType::class)]
final class AbsenceTypeTest extends TestCase
{
    public function testBackingValuesMatchTheApi(): void
    {
        self::assertSame(['Planned', 'Unplanned'], array_column(AbsenceType::cases(), 'value'));
        self::assertSame(AbsenceType::PLANNED, AbsenceType::from('Planned'));
        self::assertSame(AbsenceType::UNPLANNED, AbsenceType::from('Unplanned'));
    }
}
