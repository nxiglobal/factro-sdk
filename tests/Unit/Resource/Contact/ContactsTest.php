<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Contact;

use Nxi\Factro\Exception\OperationNotPermittedException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Contact\Contacts;
use Nxi\Factro\Resource\Contact\Input\ContactChanges;
use Nxi\Factro\Resource\Contact\Input\NewContact;
use Nxi\Factro\Resource\Contact\Output\Contact;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Contacts::class)]
final class ContactsTest extends TestCase
{
    public function testListGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /contacts' => JsonMockResponse::fromFile(Fixtures::path('contacts')),
            'GET /contacts/'.Id::K1 => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('contact')),
            'GET /contacts/'.Id::MISSING => new MockResponse('Contact with id "missing" not found', ['http_code' => 404]),
        ]);

        $contacts = $client->contacts()->list();
        self::assertCount(3, $contacts);
        self::assertContainsOnlyInstancesOf(Contact::class, $contacts);
        self::assertInstanceOf(Contact::class, $client->contacts()->get(Id::K1));
        self::assertInstanceOf(Contact::class, $client->contacts()->find(Id::K1));
        self::assertNull($client->contacts()->find(Id::MISSING));
    }

    public function testCreatePostsPayloadAndReturnsContact(): void
    {
        $client = MockFactro::client(['POST /contacts' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"firstName":"Jane","lastName":"Doe","salutation":"female"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('contact'));
        }]);

        self::assertInstanceOf(Contact::class, $client->contacts()->create(new NewContact('Jane', 'Doe', salutation: Salutation::FEMALE)));
    }

    public function testUpdateSendsOnlyChangedFields(): void
    {
        $client = MockFactro::client(['PUT /contacts/'.Id::K1 => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"mobilePhone":"+49 170 1"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('contact'));
        }]);

        self::assertInstanceOf(Contact::class, $client->contacts()->update(Id::K1, new ContactChanges(mobilePhone: '+49 170 1')));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->contacts()->update(Id::K1, new ContactChanges());
    }

    public function testDeleteDiscardsResponse(): void
    {
        $client = MockFactro::client(['DELETE /contacts/'.Id::K1 => JsonMockResponse::fromFile(Fixtures::path('contact'))]);

        $client->contacts()->delete(Id::K1);
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->contacts()->delete(Id::K1);
    }

    public function testCreateManyPostsListOfPayloads(): void
    {
        $client = MockFactro::client(['POST /contacts/contacts' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"firstName":"A","lastName":"B"},{"firstName":"C","lastName":"D","city":"Hamburg"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('contacts'));
        }]);

        $contacts = $client->contacts()->createMany([new NewContact('A', 'B'), new NewContact('C', 'D', city: 'Hamburg')]);

        self::assertCount(3, $contacts);
        self::assertContainsOnlyInstancesOf(Contact::class, $contacts);
    }

    public function testCreateManyWithEmptyInputIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->contacts()->createMany([]);
    }

    public function testUpdateManyPutsListWithIds(): void
    {
        $client = MockFactro::client(['PUT /contacts/contacts' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"id":"'.Id::K1.'","lastName":"New"},{"id":"k2","salutation":"none"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('contacts'));
        }]);

        $contacts = $client->contacts()->updateMany([Id::K1 => new ContactChanges(lastName: 'New'), 'k2' => new ContactChanges(salutation: Salutation::NONE)]);

        self::assertCount(3, $contacts);
        self::assertContainsOnlyInstancesOf(Contact::class, $contacts);
    }

    public function testUpdateManyWithEmptyInputIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->contacts()->updateMany([]);
    }
}
