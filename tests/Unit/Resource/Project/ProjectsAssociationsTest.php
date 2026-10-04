<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project;

use Nxi\Factro\Resource\AccessRight\AccessRights;
use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Resource\Project\Projects;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Company, contact, document and access-right operations of Projects.
 */
#[CoversClass(Projects::class)]
final class ProjectsAssociationsTest extends TestCase
{
    public function testSetAndRemoveCompany(): void
    {
        $client = MockFactro::client([
            'PUT /projects/p1/company' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"companyId":"c1"}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /projects/p1/company' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->projects()->setCompany('p1', 'c1');
        $client->projects()->removeCompany('p1');
        $this->addToAssertionCount(2);
    }

    public function testSetAndRemoveContact(): void
    {
        $client = MockFactro::client([
            'PUT /projects/p1/contact' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"contactId":"k1"}', $o['body']);

                return new MockResponse('', ['http_code' => 204]);
            },
            'DELETE /projects/p1/contact' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->projects()->setContact('p1', 'k1');
        $client->projects()->removeContact('p1');
        $this->addToAssertionCount(2);
    }

    public function testDocumentsAddDocumentAndRemoveDocument(): void
    {
        $client = MockFactro::client([
            'GET /projects/p1/documents' => JsonMockResponse::fromFile(Fixtures::path('documents')),
            'POST /projects/p1/documents' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertStringContainsString('name="file"; filename="Quote.pdf"', $o['body']);
                self::assertStringContainsString('%PDF', $o['body']);
                self::assertStringContainsString('multipart/form-data; boundary=', (string) json_encode($o['headers'], JSON_UNESCAPED_SLASHES));

                return JsonMockResponse::fromFile(Fixtures::path('document'));
            },
            'DELETE /projects/p1/documents/d%2F1' => JsonMockResponse::fromFile(Fixtures::path('document')),
        ]);

        $documents = $client->projects()->documents('p1');
        self::assertCount(2, $documents);
        self::assertContainsOnlyInstancesOf(Document::class, $documents);

        $added = $client->projects()->addDocument('p1', new DocumentUpload('Quote.pdf', '%PDF', 'application/pdf'));
        self::assertSame('Quote.pdf', $added->title);

        $client->projects()->removeDocument('p1', 'd/1');
        $this->addToAssertionCount(1);
    }

    public function testAccessRightsUseTheProjectPathForEmployeesAndTeams(): void
    {
        $client = MockFactro::client([
            'GET /projects/p%2F1/read_rights' => JsonMockResponse::fromFile(Fixtures::path('access-rights')),
            'PUT /projects/p%2F1/team_read_rights' => JsonMockResponse::fromFile(Fixtures::path('team-access-right')),
        ]);

        $rights = $client->projects()->accessRights('p/1');

        self::assertInstanceOf(AccessRights::class, $rights);
        self::assertCount(3, $rights->readRights());
        $rights->grantTeamRead('team1');
        $this->addToAssertionCount(1);
    }
}
