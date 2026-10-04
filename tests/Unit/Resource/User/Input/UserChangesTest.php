<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User\Input;

use Nxi\Factro\Resource\User\Input\UserChanges;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Resource\User\SecurityGroup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserChanges::class)]
final class UserChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSent(): void
    {
        $changes = new UserChanges(emailAddress: 'b@example.invalid', securityGroup: SecurityGroup::ADVANCED_RIGHTS, zipCode: '20095', salutation: Salutation::NONE);

        self::assertSame([
            'emailAddress' => 'b@example.invalid',
            'zipCode' => '20095',
            'salutation' => 'none',
            'securityGroup' => 'AdvancedRights',
        ], $changes->toPayload());
    }

    public function testAllFieldsAreSent(): void
    {
        $changes = new UserChanges('a@example.invalid', 'V', 'N', SecurityGroup::ALL_RIGHTS, 'Hamburg', 'Street 1', '20095', Salutation::MALE);

        self::assertSame(['emailAddress', 'firstName', 'lastName', 'city', 'street', 'zipCode', 'salutation', 'securityGroup'], array_keys($changes->toPayload()));
    }

    public function testIsEmpty(): void
    {
        self::assertTrue(new UserChanges()->isEmpty());
        self::assertFalse(new UserChanges(city: '')->isEmpty());
    }
}
