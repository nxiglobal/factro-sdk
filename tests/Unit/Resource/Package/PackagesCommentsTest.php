<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Package;

use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Resource\Package\Input\NewPackageComment;
use Nxi\Factro\Resource\Package\Output\PackageComment;
use Nxi\Factro\Resource\Package\Packages;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
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
            'GET /projects/'.Id::P1.'/packages/'.Id::K1.'/comments' => JsonMockResponse::fromFile(Fixtures::path('package-comments')),
            'GET /projects/'.Id::P1.'/packages/'.Id::K1.'/comments/'.Id::C1 => new JsonMockResponse($first),
            'POST /projects/'.Id::P1.'/packages/'.Id::K1.'/comments' => static function (string $m, string $u, array $o) use ($first): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"text":"<p>Hi</p>","parentCommentId":"'.Id::C1.'"}', $o['body']);

                return new JsonMockResponse($first);
            },
            'DELETE /projects/'.Id::P1.'/packages/'.Id::K1.'/comments/'.Id::C1 => new JsonMockResponse($first),
        ]);

        $comments = $client->packages()->comments(Id::P1, Id::K1);
        self::assertCount(2, $comments);
        self::assertContainsOnlyInstancesOf(PackageComment::class, $comments);

        self::assertSame($first['id'], $client->packages()->comment(Id::P1, Id::K1, Id::C1)->id);

        $added = $client->packages()->addComment(Id::P1, Id::K1, new NewPackageComment('<p>Hi</p>', Id::C1));
        self::assertSame($first['id'], $added->id);
        self::assertSame($first['taskPackageId'], $added->taskPackageId);

        $client->packages()->deleteComment(Id::P1, Id::K1, Id::C1);
        $this->addToAssertionCount(1);
    }

    public function testDocumentsAndAddDocumentAndRemoveDocument(): void
    {
        $client = MockFactro::client([
            'GET /projects/'.Id::P1.'/packages/'.Id::K1.'/documents' => JsonMockResponse::fromFile(Fixtures::path('documents')),
            'POST /projects/'.Id::P1.'/packages/'.Id::K1.'/documents' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertStringContainsString('name="file"; filename="notes.txt"', $o['body']);
                self::assertStringContainsString("Content-Type: text/plain\r\n\r\nhello\r\n", $o['body']);
                self::assertIsArray($o['normalized_headers']);
                self::assertIsArray($o['normalized_headers']['content-type']);
                self::assertIsString($o['normalized_headers']['content-type'][0]);
                self::assertStringStartsWith('Content-Type: multipart/form-data; boundary=nxi-factro-', $o['normalized_headers']['content-type'][0]);

                return JsonMockResponse::fromFile(Fixtures::path('document'));
            },
            'DELETE /projects/'.Id::P1.'/packages/'.Id::K1.'/documents/'.Id::D1 => new MockResponse('', ['http_code' => 204]),
        ]);

        $documents = $client->packages()->documents(Id::P1, Id::K1);
        self::assertCount(2, $documents);
        self::assertContainsOnlyInstancesOf(Document::class, $documents);

        $document = $client->packages()->addDocument(Id::P1, Id::K1, new DocumentUpload('notes.txt', 'hello', 'text/plain'));
        self::assertSame('Quote.pdf', $document->title);

        $client->packages()->removeDocument(Id::P1, Id::K1, Id::D1);
        $this->addToAssertionCount(1);
    }

    public function testAccessRightsUseThePackagePathForEmployeesAndTheShortPathForTeams(): void
    {
        $client = MockFactro::client([
            'GET /projects/'.Id::P1.'/packages/'.Id::K1.'/read_rights' => JsonMockResponse::fromFile(Fixtures::path('access-rights')),
            'PUT /projects/'.Id::P1.'/packages/'.Id::K1.'/write_rights' => JsonMockResponse::fromFile(Fixtures::path('employee-access-right')),
            'PUT /projects/'.Id::P1.'/'.Id::K1.'/team_read_rights' => static function (string $m, string $u, array $o): MockResponse {
                self::assertIsString($o['body']);
                self::assertJsonStringEqualsJsonString('{"teamId":"'.Id::TEAM1.'"}', $o['body']);

                return JsonMockResponse::fromFile(Fixtures::path('team-access-right'));
            },
            'DELETE /projects/'.Id::P1.'/'.Id::K1.'/team_write_rights/'.Id::TEAM1 => new MockResponse('', ['http_code' => 204]),
        ]);

        $rights = $client->packages()->accessRights(Id::P1, Id::K1);

        self::assertCount(3, $rights->readRights());
        self::assertFalse($rights->grantWrite(Id::E1)->canEdit);
        self::assertSame('c4d5e6f7-a8b9-5c0d-9e1f-3a4b5c6d7e8f', $rights->grantTeamRead(Id::TEAM1)->teamId);
        $rights->revokeTeamWrite(Id::TEAM1);
        $this->addToAssertionCount(1);
    }
}
