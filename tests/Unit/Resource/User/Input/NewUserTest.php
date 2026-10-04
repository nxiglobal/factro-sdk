<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User\Input;

use Nxi\Factro\Resource\User\Input\NewUser;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Resource\User\SecurityGroup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewUser::class)]
final class NewUserTest extends TestCase
{
    public function testRequiredFieldsOnly(): void
    {
        $user = new NewUser('a@example.invalid', 'Jane', 'Doe', SecurityGroup::BASIC_RIGHTS);

        self::assertSame([
            'emailAddress' => 'a@example.invalid',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'securityGroup' => 'BasicRights',
        ], $user->toPayload());
    }

    public function testOptionalFieldsAreSentWhenSet(): void
    {
        $user = new NewUser('a@example.invalid', 'Jane', 'Doe', SecurityGroup::GUEST_RIGHTS, city: 'Hamburg', street: 'Street 1', zipCode: '20095', salutation: Salutation::FEMALE);

        self::assertSame([
            'emailAddress' => 'a@example.invalid',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'securityGroup' => 'GuestRights',
            'city' => 'Hamburg',
            'street' => 'Street 1',
            'zipCode' => '20095',
            'salutation' => 'female',
        ], $user->toPayload());
    }
}
