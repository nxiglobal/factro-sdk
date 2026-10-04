<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\Appointment\Output\Appointment;
use Nxi\Factro\Testing\Factory\AppointmentFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppointmentFactory::class)]
final class AppointmentFactoryTest extends TestCase
{
    public function testMakeAppliesOverridesAndDtoHydrates(): void
    {
        $row = AppointmentFactory::make(['subject' => 'Custom', 'location' => null]);

        self::assertSame('Custom', $row['subject']);
        self::assertArrayHasKey('location', $row);
        self::assertNull($row['location']);
        self::assertInstanceOf(Appointment::class, AppointmentFactory::dto());
        self::assertSame('Custom', AppointmentFactory::dto(['subject' => 'Custom'])->subject);
        self::assertSame('0a03bdb0-4da0-5845-b55f-d9dd4a8d6daf', AppointmentFactory::dto()->id);
    }

    public function testManyProducesDistinctIds(): void
    {
        $rows = AppointmentFactory::many(2);

        self::assertCount(2, $rows);
        self::assertNotSame($rows[0]['id'], $rows[1]['id']);
        self::assertContainsOnlyInstancesOf(Appointment::class, array_map(Appointment::fromArray(...), $rows));
    }
}
