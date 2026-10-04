<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Company\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Company\Output\Company;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Company::class)]
final class CompanyTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index): array
    {
        $row = Fixtures::json('companies')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row(0);
        $company = Company::fromArray($row);

        self::assertSame($row['id'], $company->id);
        self::assertSame($row['name'], $company->name);
        self::assertSame($row['shortName'], $company->shortName);
        self::assertSame($row['description'], $company->description);
        self::assertSame($row['companyNumber'], $company->companyNumber);
        self::assertNull($company->customerId);
        self::assertSame($row['emailAddress'], $company->emailAddress);
        self::assertNull($company->phone);
        self::assertNull($company->website);
        self::assertNull($company->street);
        self::assertNull($company->zipCode);
        self::assertNull($company->city);
        self::assertSame($row['contactIds'], $company->contactIds);
        self::assertCount(2, $company->contactIds);
    }

    public function testMissingAddressKeysHydrateToNull(): void
    {
        $row = $this->row(1);
        self::assertArrayNotHasKey('street', $row);
        $company = Company::fromArray($row);

        self::assertNull($company->street);
        self::assertNull($company->city);
        self::assertNull($company->zipCode);
        self::assertSame([], $company->contactIds);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'name'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row(0);
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Company::fromArray($row);
    }
}
