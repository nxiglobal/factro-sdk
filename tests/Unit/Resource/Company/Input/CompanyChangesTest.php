<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Company\Input;

use Nxi\Factro\Resource\Company\Input\CompanyChanges;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompanyChanges::class)]
final class CompanyChangesTest extends TestCase
{
    public function testOnlySetFieldsAreSent(): void
    {
        $changes = new CompanyChanges(name: 'New', city: 'Hamburg', customerId: 'K-1');

        self::assertSame(['name' => 'New', 'city' => 'Hamburg', 'customerId' => 'K-1'], $changes->toPayload());
    }

    public function testAllFields(): void
    {
        $changes = new CompanyChanges(
            name: 'Acme', description: '<p>d</p>', city: 'Hamburg', emailAddress: 'info@example.invalid', phone: '+49 40 1',
            shortName: 'F', street: 'Rue du Marché 1', website: 'https://example.invalid', zipCode: '20095', customerId: 'K-1',
        );

        self::assertSame([
            'name' => 'Acme',
            'description' => '<p>d</p>',
            'city' => 'Hamburg',
            'emailAddress' => 'info@example.invalid',
            'phone' => '+49 40 1',
            'shortName' => 'F',
            'street' => 'Rue du Marché 1',
            'website' => 'https://example.invalid',
            'zipCode' => '20095',
            'customerId' => 'K-1',
        ], $changes->toPayload());
    }

    public function testEmptyStringIsSentAndIsEmptyWorks(): void
    {
        self::assertSame(['description' => ''], new CompanyChanges(description: '')->toPayload());
        self::assertTrue(new CompanyChanges()->isEmpty());
        self::assertFalse(new CompanyChanges(description: '')->isEmpty());
    }
}
