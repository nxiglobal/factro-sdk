<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Document\Output\Document;

/**
 * Rows and DTOs for Document, based on fixtures/documents.json.
 */
final class DocumentFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['documents', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Document
    {
        return Document::fromArray(self::make($overrides));
    }
}
