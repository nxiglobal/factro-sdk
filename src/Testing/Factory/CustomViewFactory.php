<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\CustomView\Output\CustomView;

/**
 * Rows and DTOs for CustomView, based on fixtures/custom-views.json.
 */
final class CustomViewFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['custom-views', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): CustomView
    {
        return CustomView::fromArray(self::make($overrides));
    }
}
