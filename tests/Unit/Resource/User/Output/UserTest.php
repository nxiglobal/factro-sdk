<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\User\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\User\Output\User;
use Nxi\Factro\Resource\User\SecurityGroup;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
final class UserTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('users')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $user = User::fromArray($row);

        self::assertSame($row['id'], $user->id);
        self::assertSame($row['firstName'], $user->firstName);
        self::assertSame($row['lastName'], $user->lastName);
        self::assertSame($row['emailAddress'], $user->emailAddress);
        self::assertSame(SecurityGroup::ALL_RIGHTS, $user->securityGroup);
        self::assertSame($row['isActive'], $user->isActive);
        self::assertSame($row['anonymized'], $user->anonymized);
        self::assertSame($row['employeeNumber'], $user->employeeNumber);
        self::assertSame($row['accountId'], $user->accountId);
        self::assertNull($user->salutation);
        self::assertNull($user->street);
        self::assertNull($user->zipCode);
        self::assertNull($user->city);
        self::assertNull($user->fallbackImageText);
        self::assertSame($row['mandantId'], $user->mandantId);
        self::assertSame('FirstName1 LastName1', $user->displayName());
    }

    public function testEverySecurityGroupAndAnInactiveUserAreCovered(): void
    {
        $users = array_map(static function (mixed $row): User {
            self::assertIsArray($row);

            /* @var array<string, mixed> $row */
            return User::fromArray($row);
        }, Fixtures::json('users'));

        self::assertCount(5, $users);
        $groups = array_map(static fn (User $u): string => $u->securityGroup->value, array_slice($users, 0, 4));
        self::assertEqualsCanonicalizing(array_column(SecurityGroup::cases(), 'value'), $groups);
        self::assertTrue($users[3]->securityGroup->isGuest());
        self::assertNull($users[3]->accountId);
        self::assertFalse($users[4]->isActive);
    }

    public function testSingleUserFixtureCarriesAddressKeys(): void
    {
        $user = User::fromArray(Fixtures::json('user'));

        self::assertSame('VN', $user->fallbackImageText);
        self::assertNull($user->street);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'firstName', 'lastName', 'emailAddress', 'securityGroup', 'isActive', 'anonymized', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        User::fromArray($row);
    }

    public function testDisplayNameIsTrimmed(): void
    {
        $user = User::fromArray($this->row() + []);
        self::assertSame(trim($user->firstName.' '.$user->lastName), $user->displayName());
    }
}
