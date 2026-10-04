<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Package;

use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Package\Input\NewPackage;
use Nxi\Factro\Resource\Package\Input\PackageChanges;
use Nxi\Factro\Resource\Package\Output\Package;
use Nxi\Factro\Resource\Package\Packages;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Packages::class)]
final class PackagesTest extends TestCase
{
    public function testListByProjectAndGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /projects/p1/packages' => JsonMockResponse::fromFile(Fixtures::path('packages')),
            'GET /packages/k1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('package')),
            'GET /packages/missing' => new MockResponse('Package with id "missing" not found', ['http_code' => 404]),
        ]);

        $packages = $client->packages()->listByProject('p1');
        self::assertCount(5, $packages);
        self::assertContainsOnlyInstancesOf(Package::class, $packages);
        self::assertInstanceOf(Package::class, $client->packages()->get('k1'));
        self::assertInstanceOf(Package::class, $client->packages()->find('k1'));
        self::assertNull($client->packages()->find('missing'));
    }

    public function testCreatePostsPayload(): void
    {
        $client = MockFactro::client(['POST /projects/p1/packages' => static function (string $method, string $url, array $options): MockResponse {
            self::assertIsString($options['body']);
            self::assertJsonStringEqualsJsonString('{"title":"P","parentPackageId":"root"}', $options['body']);

            return JsonMockResponse::fromFile(Fixtures::path('package'));
        }]);

        self::assertInstanceOf(Package::class, $client->packages()->create('p1', new NewPackage('P', parentPackageId: 'root')));
    }

    public function testUpdatePutsPartialPayload(): void
    {
        $client = MockFactro::client(['PUT /projects/p1/packages/k1' => static function (string $method, string $url, array $options): MockResponse {
            self::assertIsString($options['body']);
            self::assertJsonStringEqualsJsonString('{"title":"New"}', $options['body']);

            return JsonMockResponse::fromFile(Fixtures::path('package'));
        }]);

        self::assertInstanceOf(Package::class, $client->packages()->update('p1', 'k1', new PackageChanges(title: 'New')));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->packages()->update('p1', 'k1', new PackageChanges());
    }

    public function testDeleteAccepts204(): void
    {
        $client = MockFactro::client(['DELETE /projects/p1/packages/k1' => new MockResponse('', ['http_code' => 204])]);

        $client->packages()->delete('p1', 'k1');
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicyBeforeSending(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->packages()->delete('p1', 'k1');
    }

    public function testGetInProjectUsesTheProjectScopedRoute(): void
    {
        $client = MockFactro::client(['GET /projects/p1/packages/k1' => JsonMockResponse::fromFile(Fixtures::path('package'))]);

        $package = $client->packages()->getInProject('p1', 'k1');

        self::assertSame('453190d4-b4cb-5516-a449-e682d35fdbdc', $package->id);
    }

    public function testCreateManyPostsAListOfPayloads(): void
    {
        $client = MockFactro::client(['POST /projects/p1/packages/packages' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"title":"A"},{"title":"B","parentPackageId":"root"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('packages'));
        }]);

        $created = $client->packages()->createMany('p1', [new NewPackage('A'), new NewPackage('B', parentPackageId: 'root')]);

        self::assertCount(5, $created);
        self::assertContainsOnlyInstancesOf(Package::class, $created);
    }

    public function testCreateManyRejectsEmptyInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->packages()->createMany('p1', []);
    }

    public function testUpdateManyPutsAListWithIds(): void
    {
        $client = MockFactro::client(['PUT /projects/p1/packages/packages' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '[{"id":"k1","title":"New"},{"id":"k2","startDate":"2026-08-31T22:00:00.000Z"}]',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('packages'));
        }], new FactroOptions(baseUrl: MockFactro::BASE_URL, timezone: new \DateTimeZone('Europe/Berlin'), maxRetries: 0));

        $updated = $client->packages()->updateMany('p1', [
            'k1' => new PackageChanges(title: 'New'),
            'k2' => new PackageChanges(startDate: CalendarDate::fromYmd('2026-09-01')),
        ]);

        self::assertCount(5, $updated);
        self::assertContainsOnlyInstancesOf(Package::class, $updated);
    }

    public function testUpdateManyRejectsEmptyInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->packages()->updateMany('p1', []);
    }

    public function testUpdateManyRejectsAnEntryWithoutChanges(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->packages()->updateMany('p1', ['k1' => new PackageChanges()]);
    }

    public function testMoveToProjectOmitsPositionWhenNull(): void
    {
        $client = MockFactro::client(['PUT /projects/p1/packages/k1/project' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"projectId":"p2"}', $o['body']);

            return new MockResponse('', ['http_code' => 204]);
        }]);

        $client->packages()->moveToProject('p1', 'k1', 'p2');
        $this->addToAssertionCount(1);
    }

    public function testMoveToProjectSendsPosition(): void
    {
        $client = MockFactro::client(['PUT /projects/p1/packages/k1/project' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"projectId":"p2","position":3}', $o['body']);

            return new MockResponse('', ['http_code' => 204]);
        }]);

        $client->packages()->moveToProject('p1', 'k1', 'p2', 3);
        $this->addToAssertionCount(1);
    }

    public function testMoveToPackageAndRemoveFromParentPackage(): void
    {
        $client = MockFactro::client([
            'PUT /projects/p1/packages/k1/package' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"parentPackageId":"k9","position":0}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /projects/p1/packages/k1/package' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->packages()->moveToPackage('p1', 'k1', 'k9', 0);
        $client->packages()->removeFromParentPackage('p1', 'k1');
        $this->addToAssertionCount(2);
    }

    public function testMoveToPackageOmitsPositionWhenNull(): void
    {
        $client = MockFactro::client(['PUT /projects/p1/packages/k1/package' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"parentPackageId":"k9"}', $o['body']);

            return new MockResponse('', ['http_code' => 204]);
        }]);

        $client->packages()->moveToPackage('p1', 'k1', 'k9');
        $this->addToAssertionCount(1);
    }

    public function testCompanyAndContactAssociations(): void
    {
        $client = MockFactro::client([
            'PUT /projects/p1/packages/k1/company' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"companyId":"c1"}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /projects/p1/packages/k1/company' => new MockResponse('', ['http_code' => 204]),
            'PUT /projects/p1/packages/k1/contact' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"contactId":"ct1"}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /projects/p1/packages/k1/contact' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->packages()->setCompany('p1', 'k1', 'c1');
        $client->packages()->removeCompany('p1', 'k1');
        $client->packages()->setContact('p1', 'k1', 'ct1');
        $client->packages()->removeContact('p1', 'k1');
        $this->addToAssertionCount(4);
    }

    public function testShiftWithSuccessorsPostsDaysDelta(): void
    {
        $client = MockFactro::client(['POST /projects/p1/packages/k1/shift_with_successors' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"daysDelta":-5}', $o['body']);

            return new MockResponse('', ['http_code' => 204]);
        }]);

        $client->packages()->shiftWithSuccessors('p1', 'k1', -5);
        $this->addToAssertionCount(1);
    }
}
