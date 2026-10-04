<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Company\Input;

use Nxi\Factro\Resource\Company\Input\NewCompany;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewCompany::class)]
final class NewCompanyTest extends TestCase
{
    public function testMinimalPayloadCarriesOnlyName(): void
    {
        self::assertSame(['name' => 'Acme'], new NewCompany('Acme')->toPayload());
    }

    public function testAllOptionalFields(): void
    {
        $company = new NewCompany(
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
        ], $company->toPayload());
    }
}
