<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Company;

use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Company\Companies;
use Nxi\Factro\Resource\Company\Input\CompanyChanges;
use Nxi\Factro\Resource\Company\Input\NewCompany;
use Nxi\Factro\Resource\Company\Output\Company;
use Nxi\Factro\Resource\Company\Output\CompanyTag;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Companies::class)]
final class CompaniesTest extends TestCase
{
    public function testListGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /companies' => JsonMockResponse::fromFile(Fixtures::path('companies')),
            'GET /companies/c1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('company')),
            'GET /companies/missing' => new MockResponse('Company with id "missing" not found', ['http_code' => 404]),
        ]);

        $companies = $client->companies()->list();
        self::assertCount(3, $companies);
        self::assertContainsOnlyInstancesOf(Company::class, $companies);
        self::assertInstanceOf(Company::class, $client->companies()->get('c1'));
        self::assertInstanceOf(Company::class, $client->companies()->find('c1'));
        self::assertNull($client->companies()->find('missing'));
    }

    public function testCreatePostsPayloadAndReturnsCompany(): void
    {
        $client = MockFactro::client(['POST /companies' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"name":"Acme","city":"Hamburg"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('company'));
        }]);

        self::assertInstanceOf(Company::class, $client->companies()->create(new NewCompany('Acme', city: 'Hamburg')));
    }

    public function testUpdateSendsOnlyChangedFields(): void
    {
        $client = MockFactro::client(['PUT /companies/c1' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"website":"https://example.invalid"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('company'));
        }]);

        self::assertInstanceOf(Company::class, $client->companies()->update('c1', new CompanyChanges(website: 'https://example.invalid')));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->companies()->update('c1', new CompanyChanges());
    }

    public function testDeleteDiscardsResponse(): void
    {
        $client = MockFactro::client(['DELETE /companies/c1' => JsonMockResponse::fromFile(Fixtures::path('company'))]);

        $client->companies()->delete('c1');
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->companies()->delete('c1');
    }

    public function testCreateManyPostsListOfPayloads(): void
    {
        $client = MockFactro::client(['POST /companies/companies' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"name":"A"},{"name":"B","zipCode":"20095"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('companies'));
        }]);

        $companies = $client->companies()->createMany([new NewCompany('A'), new NewCompany('B', zipCode: '20095')]);

        self::assertCount(3, $companies);
        self::assertContainsOnlyInstancesOf(Company::class, $companies);
    }

    public function testCreateManyWithEmptyInputIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->companies()->createMany([]);
    }

    public function testUpdateManyPutsListWithIds(): void
    {
        $client = MockFactro::client(['PUT /companies/companies' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"id":"c1","name":"A"},{"id":"c2","phone":"1"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('companies'));
        }]);

        $companies = $client->companies()->updateMany(['c1' => new CompanyChanges(name: 'A'), 'c2' => new CompanyChanges(phone: '1')]);

        self::assertCount(3, $companies);
        self::assertContainsOnlyInstancesOf(Company::class, $companies);
    }

    public function testUpdateManyWithEmptyInputIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->companies()->updateMany([]);
    }

    public function testCompanyTagsUsesLiteralTagsPath(): void
    {
        $client = MockFactro::client(['GET /companies/tags' => JsonMockResponse::fromFile(Fixtures::path('company-tags'))]);

        $tags = $client->companies()->companyTags();

        self::assertCount(3, $tags);
        self::assertContainsOnlyInstancesOf(CompanyTag::class, $tags);
    }

    public function testCreateCompanyTagPostsName(): void
    {
        $client = MockFactro::client(['POST /companies/tags' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"name":"Customer"}', $o['body']);

            return new JsonMockResponse(self::tagRow());
        }]);

        $tag = $client->companies()->createCompanyTag('Customer');

        self::assertSame('Customer', $tag->name);
    }

    public function testDeleteCompanyTagDiscardsResponse(): void
    {
        $client = MockFactro::client(['DELETE /companies/tags/tag1' => new JsonMockResponse(self::tagRow())]);

        $client->companies()->deleteCompanyTag('tag1');
        $this->addToAssertionCount(1);
    }

    public function testTagsOfCompany(): void
    {
        $client = MockFactro::client(['GET /companies/c1/tags' => JsonMockResponse::fromFile(Fixtures::path('company-tags'))]);

        $tags = $client->companies()->tags('c1');

        self::assertCount(3, $tags);
        self::assertContainsOnlyInstancesOf(CompanyTag::class, $tags);
    }

    public function testAddTagPutsTagIdAndAccepts204(): void
    {
        $client = MockFactro::client(['PUT /companies/c1/tags' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"tagId":"tag1"}', $o['body']);

            return new MockResponse('', ['http_code' => 204]);
        }]);

        $client->companies()->addTag('c1', 'tag1');
        $this->addToAssertionCount(1);
    }

    public function testRemoveTagAccepts204(): void
    {
        $client = MockFactro::client(['DELETE /companies/c1/tags/tag1' => new MockResponse('', ['http_code' => 204])]);

        $client->companies()->removeTag('c1', 'tag1');
        $this->addToAssertionCount(1);
    }

    /** @return array<string, mixed> */
    private static function tagRow(): array
    {
        $row = Fixtures::json('company-tags')[0];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }
}
