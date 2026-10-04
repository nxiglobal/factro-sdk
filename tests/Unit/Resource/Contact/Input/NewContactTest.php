<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Contact\Input;

use Nxi\Factro\Resource\Contact\Input\NewContact;
use Nxi\Factro\Resource\User\Salutation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewContact::class)]
final class NewContactTest extends TestCase
{
    public function testMinimalPayloadCarriesOnlyNames(): void
    {
        self::assertSame(['firstName' => 'Jane', 'lastName' => 'Doe'], new NewContact('Jane', 'Doe')->toPayload());
    }

    public function testAllOptionalFields(): void
    {
        $contact = new NewContact(
            firstName: 'Jane', lastName: 'Doe', salutation: Salutation::FEMALE, description: '<p>d</p>', city: 'Hamburg',
            emailAddress: 'jane@example.invalid', phone: '+49 40 1', mobilePhone: '+49 170 1', street: 'Rue du Marché 1', zipCode: '20095',
        );

        self::assertSame([
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'salutation' => 'female',
            'description' => '<p>d</p>',
            'city' => 'Hamburg',
            'emailAddress' => 'jane@example.invalid',
            'phone' => '+49 40 1',
            'mobilePhone' => '+49 170 1',
            'street' => 'Rue du Marché 1',
            'zipCode' => '20095',
        ], $contact->toPayload());
    }
}
