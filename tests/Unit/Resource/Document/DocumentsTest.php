<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Document;

use Nxi\Factro\Resource\Document\Documents;
use Nxi\Factro\Resource\Document\Output\DataQuota;
use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Documents::class)]
#[CoversClass(Document::class)]
#[CoversClass(DataQuota::class)]
final class DocumentsTest extends TestCase
{
    public function testListHydratesFixture(): void
    {
        $client = MockFactro::client(['GET /documents' => JsonMockResponse::fromFile(Fixtures::path('documents'))]);

        $documents = $client->documents()->list();

        self::assertCount(2, $documents);
        self::assertSame('Quote.pdf', $documents[0]->title);
        self::assertSame(184320, $documents[0]->size);
        self::assertSame('application/pdf', $documents[0]->contentType);
        self::assertNull($documents[1]->changeDate);
    }

    public function testGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /documents/d1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('document')),
            'GET /documents/missing' => new MockResponse(Fixtures::raw('errors/404-task.txt'), ['http_code' => 404]),
        ]);

        self::assertInstanceOf(Document::class, $client->documents()->get('d1'));
        self::assertInstanceOf(Document::class, $client->documents()->find('d1'));
        self::assertNull($client->documents()->find('missing'));
    }

    public function testQuota(): void
    {
        $client = MockFactro::client(['GET /documents/quota' => JsonMockResponse::fromFile(Fixtures::path('data-quota'))]);

        $quota = $client->documents()->quota();

        self::assertSame(107374182400.0, $quota->maxDiskSpace);
        self::assertSame(107374182400.0 - 23622320128.0, $quota->freeDiskSpace());
    }
}
