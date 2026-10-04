<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Package\Input;

use Nxi\Factro\Resource\Package\Input\NewPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewPackage::class)]
final class NewPackageTest extends TestCase
{
    public function testPayloadContainsTitleAndSetFields(): void
    {
        self::assertSame(['title' => 'P', 'parentPackageId' => 'root'], new NewPackage(title: 'P', parentPackageId: 'root')->toPayload());
        self::assertSame(['title' => 'P'], new NewPackage('P')->toPayload());
        self::assertSame(
            ['title' => 'P', 'description' => 'd', 'colorScheme' => 'blue', 'officerId' => 'u', 'customFields' => ['a' => 1]],
            new NewPackage('P', description: 'd', colorScheme: 'blue', officerId: 'u', customFields: ['a' => 1])->toPayload(),
        );
    }
}
