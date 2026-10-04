<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User\Input;

use Nxi\Factro\Resource\User\AbsenceType;
use Nxi\Factro\Resource\User\Input\AbsenceChanges;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbsenceChanges::class)]
final class AbsenceChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSent(): void
    {
        $changes = new AbsenceChanges(endDate: new \DateTimeImmutable('2026-10-12T00:00:00+02:00'), type: AbsenceType::UNPLANNED);

        self::assertSame(['endDate' => '2026-10-11T22:00:00.000Z', 'type' => 'Unplanned'], $changes->toPayload());
    }

    public function testAllFieldsAreSent(): void
    {
        $changes = new AbsenceChanges(new \DateTimeImmutable('2026-10-04T22:00:00Z'), new \DateTimeImmutable('2026-10-09T22:00:00Z'), AbsenceType::PLANNED);

        self::assertSame(['startDate' => '2026-10-04T22:00:00.000Z', 'endDate' => '2026-10-09T22:00:00.000Z', 'type' => 'Planned'], $changes->toPayload());
    }

    public function testIsEmpty(): void
    {
        self::assertTrue(new AbsenceChanges()->isEmpty());
        self::assertFalse(new AbsenceChanges(type: AbsenceType::PLANNED)->isEmpty());
    }
}
