<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User\Input;

use Nxi\Factro\Resource\User\AbsenceType;
use Nxi\Factro\Resource\User\Input\NewAbsence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewAbsence::class)]
final class NewAbsenceTest extends TestCase
{
    public function testDatesAreSentAsIsoUtc(): void
    {
        $absence = new NewAbsence(new \DateTimeImmutable('2026-10-05T00:00:00+02:00'), new \DateTimeImmutable('2026-10-10T00:00:00+02:00'), AbsenceType::PLANNED);

        self::assertSame([
            'startDate' => '2026-10-04T22:00:00.000Z',
            'endDate' => '2026-10-09T22:00:00.000Z',
            'type' => 'Planned',
        ], $absence->toPayload());
        self::assertNull($absence->employeeId);
    }

    public function testEmployeeIdIsOnlyPartOfTheBatchPayload(): void
    {
        $absence = new NewAbsence(new \DateTimeImmutable('2026-10-04T22:00:00Z'), new \DateTimeImmutable('2026-10-09T22:00:00Z'), AbsenceType::UNPLANNED, 'u1');

        self::assertArrayNotHasKey('employeeId', $absence->toPayload());
        self::assertSame([
            'startDate' => '2026-10-04T22:00:00.000Z',
            'endDate' => '2026-10-09T22:00:00.000Z',
            'type' => 'Unplanned',
            'employeeId' => 'u1',
        ], $absence->toBatchPayload());
    }

    public function testBatchPayloadWithoutEmployeeIdThrows(): void
    {
        $absence = new NewAbsence(new \DateTimeImmutable('2026-10-04T22:00:00Z'), new \DateTimeImmutable('2026-10-09T22:00:00Z'), AbsenceType::PLANNED);

        $this->expectException(\InvalidArgumentException::class);
        $absence->toBatchPayload();
    }

    public function testEndBeforeStartIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NewAbsence(new \DateTimeImmutable('2026-10-09T22:00:00Z'), new \DateTimeImmutable('2026-10-04T22:00:00Z'), AbsenceType::PLANNED);
    }
}
