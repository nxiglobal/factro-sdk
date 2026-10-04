<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing\Factory;

use Nxi\Factro\Resource\Contact\Output\Contact;

/**
 * Rows and DTOs for Contact, based on fixtures/contacts.json.
 */
final class ContactFactory extends FixtureFactory
{
    protected static function source(): array
    {
        return ['contacts', 0];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function dto(array $overrides = []): Contact
    {
        return Contact::fromArray(self::make($overrides));
    }
}
