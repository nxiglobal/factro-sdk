<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\Contact\Output\Contact;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Testing\Factory\ContactFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContactFactory::class)]
final class ContactFactoryTest extends TestCase
{
    public function testMakeAppliesOverridesAndDtoHydrates(): void
    {
        $row = ContactFactory::make(['lastName' => 'Custom', 'companyId' => null]);

        self::assertSame('Custom', $row['lastName']);
        self::assertArrayHasKey('companyId', $row);
        self::assertNull($row['companyId']);

        $dto = ContactFactory::dto(['salutation' => 'female']);
        self::assertInstanceOf(Contact::class, $dto);
        self::assertSame(Salutation::FEMALE, $dto->salutation);
        self::assertSame('FirstName11', ContactFactory::dto()->firstName);
    }

    public function testManyProducesDistinctIds(): void
    {
        $rows = ContactFactory::many(3, ['city' => 'Hamburg']);

        self::assertCount(3, $rows);
        self::assertCount(3, array_unique(array_map(static function (array $row): string {
            self::assertIsString($row['id']);

            return $row['id'];
        }, $rows)));
        self::assertIsString($rows[0]['id']);
        self::assertStringEndsWith('-1', $rows[0]['id']);
        self::assertArrayNotHasKey('number', $rows[0]);
        self::assertContainsOnlyInstancesOf(Contact::class, array_map(Contact::fromArray(...), $rows));
    }
}
