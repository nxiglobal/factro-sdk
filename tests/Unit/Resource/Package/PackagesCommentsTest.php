<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Package;

use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Resource\Package\Input\NewPackageComment;
use Nxi\Factro\Resource\Package\Output\PackageComment;
use Nxi\Factro\Resource\Package\Packages;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Comments, documents and access rights of packages; the core CRUD lives in PackagesTest.
 */
#[CoversClass(Packages::class)]
final class PackagesCommentsTest extends TestCase
{
    public function testCommentsAndCommentAndAddCommentAndDeleteComment(): void
    {
        $first = Fixtures::json('package-comments')[0];
        self::assertIsArray($first);
        $client = MockFactro::client([
            'GET /projects/p1/packages/k1/comments' => JsonMockResponse::fromFile(Fixtures::path('package-comments')),
            'GET /projects/p1/packages/k1/comments/c1' => new JsonMockResponse($first),
            'POST /projects/p1/packages/k1/comments' => static function (string $m, string $u, array $o) use ($first): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"text":"<p>Hi</p>","parentCommentId":"c1"}', $o['body']);

                return new JsonMockResponse($first);
            },
            'DELETE /projects/p1/packages/k1/comments/c1' => new JsonMockResponse($first),
        ]);

        $comments = $client->packages()->comments('p1', 'k1');
        self::assertCount(2, $comments);
        self::assertContainsOnlyInstancesOf(PackageComment::class, $comments);

        self::assertSame($first['id'], $client->packages()->comment('p1', 'k1', 'c1')->id);

        $added = $client->packages()->addComment('p1', 'k1', new NewPackageComment('<p>Hi</p>', 'c1'));
        self::assertSame($first['id'], $added->id);
        self::assertSame($first['taskPackageId'], $added->taskPackageId);

        $client->packages()->deleteComment('p1', 'k1', 'c1');
        $this->addToAssertionCount(1);
    }

    public function testDocumentsAndAddDocumentAndRemoveDocument(): void
    {
        $client = MockFactro::client([
            'GET /projects/p1/packages/k1/documents' => JsonMockResponse::fromFile(Fixtures::path('documents')),
            'POST /projects/p1/packages/k1/documents' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertStringContainsString('name="file"; filename="notes.txt"', $o['body']);
                self::assertStringContainsString("Content-Type: text/plain\r\n\r\nhello\r\n", $o['body']);
                self::assertIsArray($o['normalized_headers']);
                self::assertIsArray($o['normalized_headers']['content-type']);
                self::assertIsString($o['normalized_headers']['content-type'][0]);
                self::assertStringStartsWith('Content-Type: multipart/form-data; boundary=nxi-factro-', $o['normalized_headers']['content-type'][0]);

                return JsonMockResponse::fromFile(Fixtures::path('document'));
            },
            'DELETE /projects/p1/packages/k1/documents/d1' => new MockResponse('', ['http_code' => 204]),
        ]);

        $documents = $client->packages()->documents('p1', 'k1');
        self::assertCount(2, $documents);
        self::assertContainsOnlyInstancesOf(Document::class, $documents);

        $document = $client->packages()->addDocument('p1', 'k1', new DocumentUpload('notes.txt', 'hello', 'text/plain'));
        self::assertSame('Quote.pdf', $document->title);

        $client->packages()->removeDocument('p1', 'k1', 'd1');
        $this->addToAssertionCount(1);
    }

    public function testAccessRightsUseThePackagePathForEmployeesAndTheShortPathForTeams(): void
    {
        $client = MockFactro::client([
            'GET /projects/p1/packages/k1/read_rights' => JsonMockResponse::fromFile(Fixtures::path('access-rights')),
            'PUT /projects/p1/packages/k1/write_rights' => JsonMockResponse::fromFile(Fixtures::path('employee-access-right')),
            'PUT /projects/p1/k1/team_read_rights' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"teamId":"team1"}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('team-access-right'));
            },
            'DELETE /projects/p1/k1/team_write_rights/team1' => new MockResponse('', ['http_code' => 204]),
        ]);

        $rights = $client->packages()->accessRights('p1', 'k1');

        self::assertCount(3, $rights->readRights());
        self::assertFalse($rights->grantWrite('e1')->canEdit);
        self::assertSame('c4d5e6f7-a8b9-5c0d-9e1f-3a4b5c6d7e8f', $rights->grantTeamRead('team1')->teamId);
        $rights->revokeTeamWrite('team1');
        $this->addToAssertionCount(1);
    }
}
