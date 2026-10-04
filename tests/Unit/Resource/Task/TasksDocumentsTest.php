<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Resource\AccessRight\AccessRights;
use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Tasks::class)]
final class TasksDocumentsTest extends TestCase
{
    public function testDocumentsHydratesFixture(): void
    {
        $client = MockFactro::client(['GET /tasks/t1/documents' => JsonMockResponse::fromFile(Fixtures::path('documents'))]);

        $documents = $client->tasks()->documents('t1');

        self::assertCount(2, $documents);
        self::assertContainsOnlyInstancesOf(Document::class, $documents);
    }

    public function testAddDocumentSendsMultipartAndHydratesDocument(): void
    {
        $client = MockFactro::client(['POST /tasks/t1/documents' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertStringContainsString('name="file"; filename="Quote.pdf"', $o['body']);
            self::assertStringContainsString('%PDF', $o['body']);
            self::assertIsArray($o['normalized_headers']);
            self::assertIsArray($o['normalized_headers']['content-type']);
            self::assertIsString($o['normalized_headers']['content-type'][0]);
            self::assertStringContainsString('multipart/form-data; boundary=', $o['normalized_headers']['content-type'][0]);

            return JsonMockResponse::fromFile(Fixtures::path('document'));
        }]);

        $document = $client->tasks()->addDocument('t1', new DocumentUpload('Quote.pdf', '%PDF', 'application/pdf'));

        self::assertSame('Quote.pdf', $document->title);
    }

    public function testRemoveDocumentDiscardsTheResponse(): void
    {
        $client = MockFactro::client(['DELETE /tasks/t1/documents/d1' => JsonMockResponse::fromFile(Fixtures::path('document'))]);

        $client->tasks()->removeDocument('t1', 'd1');
        $this->addToAssertionCount(1);
    }

    public function testAccessRightsUseTheTaskPathForEmployeesAndTeams(): void
    {
        $client = MockFactro::client([
            'GET /tasks/t1/read_rights' => JsonMockResponse::fromFile(Fixtures::path('access-rights')),
            'PUT /tasks/t1/team_read_rights' => JsonMockResponse::fromFile(Fixtures::path('team-access-right')),
        ]);

        $rights = $client->tasks()->accessRights('t1');

        self::assertInstanceOf(AccessRights::class, $rights);
        self::assertCount(3, $rights->readRights());
        self::assertNotSame('', $rights->grantTeamRead('team1')->teamId);
    }
}
