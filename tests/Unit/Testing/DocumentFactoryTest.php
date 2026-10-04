<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Testing;

use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Testing\Factory\DocumentFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocumentFactory::class)]
final class DocumentFactoryTest extends TestCase
{
    public function testMakeAndDto(): void
    {
        self::assertSame('Custom.pdf', DocumentFactory::make(['title' => 'Custom.pdf'])['title']);
        self::assertInstanceOf(Document::class, DocumentFactory::dto());
        self::assertCount(2, DocumentFactory::many(2));
    }
}
