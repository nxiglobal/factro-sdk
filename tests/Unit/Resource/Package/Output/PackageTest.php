<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Package\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Package\Output\Package;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Package::class)]
final class PackageTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index): array
    {
        $row = Fixtures::json('packages')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row(1);
        $package = Package::fromArray($row);

        self::assertSame($row['id'], $package->id);
        self::assertSame($row['title'], $package->title);
        self::assertSame($row['description'], $package->description);
        self::assertSame($row['projectId'], $package->projectId);
        self::assertSame($row['parentPackageId'], $package->parentPackageId);
        self::assertIsString($row['startDate']);
        self::assertSame($row['startDate'], FactroDateTime::toIsoUtc($package->startDate ?? throw new \LogicException()));
        self::assertIsString($row['endDate']);
        self::assertSame($row['endDate'], FactroDateTime::toIsoUtc($package->endDate ?? throw new \LogicException()));
        self::assertIsString($row['creationDate']);
        self::assertSame($row['creationDate'], FactroDateTime::toIsoUtc($package->creationDate));
        self::assertIsString($row['changeDate']);
        self::assertSame($row['changeDate'], FactroDateTime::toIsoUtc($package->changeDate ?? throw new \LogicException()));
        self::assertSame($row['creatorId'], $package->creatorId);
        self::assertSame($row['officerId'], $package->officerId);
        self::assertSame($row['companyId'], $package->companyId);
        self::assertSame($row['companyContactId'], $package->companyContactId);
        self::assertSame(40.0, $package->plannedEffort);
        self::assertSame(8.0, $package->realizedEffort);
        self::assertSame(12.5, $package->remainingEffort);
        self::assertSame($row['number'], $package->number);
        self::assertSame($row['colorScheme'], $package->colorScheme);
        self::assertSame($row['customFields'], $package->customFields);
        self::assertSame($row['mandantId'], $package->mandantId);
    }

    public function testRootPackageHasNullParent(): void
    {
        $rows = Fixtures::json('packages');
        $roots = array_filter($rows, static fn (mixed $row): bool => is_array($row) && null === $row['parentPackageId']);
        self::assertCount(1, $roots);

        $root = Package::fromArray($this->row(0));
        self::assertNull($root->parentPackageId);
        self::assertNull($root->realizedEffort);
        self::assertSame([], $root->customFields);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'title', 'projectId', 'creationDate', 'creatorId', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row(1);
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Package::fromArray($row);
    }
}
