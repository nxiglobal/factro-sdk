<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Mapping;

use Nxi\Factro\Mapping\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Payload::class)]
final class PayloadTest extends TestCase
{
    public function testAssertClearableAcceptsAnUnsetClearableField(): void
    {
        $this->expectNotToPerformAssertions();

        Payload::assertClearable(new class {
            public ?string $color = null;
        }, ['color'], ['color']);
    }

    /**
     * @param list<string> $clear
     */
    #[DataProvider('invalidClears')]
    public function testAssertClearableRejects(array $clear, ?string $color, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Payload::assertClearable(new class($color) {
            public function __construct(public ?string $color)
            {
            }
        }, $clear, ['color']);
    }

    /**
     * @return iterable<string, array{list<string>, ?string, string}>
     */
    public static function invalidClears(): iterable
    {
        yield 'not clearable' => [['owner'], null, 'Field "owner" cannot be cleared'];
        yield 'set and cleared at once' => [['color'], 'red', 'Field "color" cannot be set and cleared at once.'];
    }

    public function testWithoutNullsExceptSendsClearedFieldsAsNull(): void
    {
        self::assertSame(['a' => 1, 'b' => null], Payload::withoutNullsExcept(['a' => 1, 'b' => null, 'c' => null], ['b']));
    }

    public function testWithoutNullsExceptRejectsAnUnknownField(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Cannot clear unknown field "x".'));

        Payload::withoutNullsExcept(['a' => null], ['x']);
    }

    public function testWithoutNullsExceptRejectsAFieldSetAndClearedAtOnce(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Field "a" cannot be set and cleared at once.'));

        Payload::withoutNullsExcept(['a' => 1], ['a']);
    }
}
