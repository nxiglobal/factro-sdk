<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Contact\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Contact\Output\Contact;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Contact::class)]
final class ContactTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index): array
    {
        $row = Fixtures::json('contacts')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row(0);
        $contact = Contact::fromArray($row);

        self::assertSame($row['id'], $contact->id);
        self::assertSame($row['firstName'], $contact->firstName);
        self::assertSame($row['lastName'], $contact->lastName);
        self::assertSame(Salutation::MALE, $contact->salutation);
        self::assertSame($row['companyId'], $contact->companyId);
        self::assertSame($row['description'], $contact->description);
        self::assertSame($row['city'], $contact->city);
        self::assertSame($row['emailAddress'], $contact->emailAddress);
        self::assertSame($row['phone'], $contact->phone);
        self::assertSame($row['mobilePhone'], $contact->mobilePhone);
        self::assertSame($row['street'], $contact->street);
        self::assertSame($row['zipCode'], $contact->zipCode);
        self::assertSame('FirstName11 LastName11', $contact->displayName());
    }

    public function testSingleContactFixtureMatchesListRow(): void
    {
        self::assertEquals(Contact::fromArray($this->row(0)), Contact::fromArray(Fixtures::json('contact')));
    }

    public function testMissingAddressKeysAndNullSalutationHydrateToNull(): void
    {
        $row = $this->row(2);
        self::assertArrayNotHasKey('street', $row);
        $contact = Contact::fromArray($row);

        self::assertNull($contact->salutation);
        self::assertNull($contact->companyId);
        self::assertNull($contact->street);
        self::assertNull($contact->zipCode);
        self::assertNull($contact->city);
        self::assertNull($contact->mobilePhone);
    }

    public function testEverySalutationIsCovered(): void
    {
        $salutations = array_map(static function (mixed $row): ?Salutation {
            self::assertIsArray($row);

            /* @var array<string, mixed> $row */
            return Contact::fromArray($row)->salutation;
        }, Fixtures::json('contacts'));

        self::assertSame([Salutation::MALE, Salutation::FEMALE, null], $salutations);
        self::assertSame(['male', 'female', 'none'], array_column(Salutation::cases(), 'value'));
    }

    public function testUnknownSalutationThrows(): void
    {
        $row = $this->row(0);
        $row['salutation'] = 'diverse';

        $this->expectException(HydrationException::class);
        Contact::fromArray($row);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'firstName', 'lastName'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row(0);
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Contact::fromArray($row);
    }
}
