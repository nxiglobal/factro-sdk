<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Contact\Input;

use Nxi\Factro\Resource\Contact\Input\ContactChanges;
use Nxi\Factro\Resource\User\Salutation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContactChanges::class)]
final class ContactChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSent(): void
    {
        $changes = new ContactChanges(lastName: 'New', salutation: Salutation::MALE);

        self::assertSame(['lastName' => 'New', 'salutation' => 'male'], $changes->toPayload());
    }

    public function testAllFields(): void
    {
        $changes = new ContactChanges(
            firstName: 'Jane', lastName: 'Doe', salutation: Salutation::NONE, description: '<p>d</p>', city: 'Hamburg',
            emailAddress: 'jane@example.invalid', phone: '+49 40 1', mobilePhone: '+49 170 1', street: 'Rue du Marché 1', zipCode: '20095',
        );

        self::assertSame([
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'salutation' => 'none',
            'description' => '<p>d</p>',
            'city' => 'Hamburg',
            'emailAddress' => 'jane@example.invalid',
            'phone' => '+49 40 1',
            'mobilePhone' => '+49 170 1',
            'street' => 'Rue du Marché 1',
            'zipCode' => '20095',
        ], $changes->toPayload());
    }

    public function testEmptyStringIsSentAndIsEmptyWorks(): void
    {
        self::assertSame(['description' => ''], new ContactChanges(description: '')->toPayload());
        self::assertTrue(new ContactChanges()->isEmpty());
        self::assertFalse(new ContactChanges(description: '')->isEmpty());
    }
}
