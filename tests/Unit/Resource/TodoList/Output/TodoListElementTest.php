<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\TodoList\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\TodoList\ListElementReferenceType;
use Nxi\Factro\Resource\TodoList\Output\TodoListElement;
use Nxi\Factro\Resource\TodoList\Output\TodoListReferencedElement;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TodoListElement::class)]
final class TodoListElementTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $list = Fixtures::json('todo-lists')[0];
        self::assertIsArray($list);
        self::assertIsArray($list['listElements']);
        $row = $list['listElements'][$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $element = TodoListElement::fromArray($row);

        self::assertSame($row['id'], $element->id);
        self::assertIsString($row['createdAt']);
        self::assertSame($row['createdAt'], FactroDateTime::toIsoUtc($element->createdAt));
        self::assertFalse($element->checked);
        self::assertSame(ListElementReferenceType::TASK, $element->elementReferenceType);
        self::assertSame($row['nextListElementId'], $element->nextListElementId);
        self::assertInstanceOf(TodoListReferencedElement::class, $element->referencedElement);
        self::assertSame('e53f64f5-6dcc-5990-af50-e315dc575d1a', $element->referencedElement->id);
    }

    public function testLastElementHasNoSuccessor(): void
    {
        $element = TodoListElement::fromArray($this->row(1));

        self::assertTrue($element->checked);
        self::assertSame(ListElementReferenceType::NOTE, $element->elementReferenceType);
        self::assertNull($element->nextListElementId);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'createdAt', 'checked', 'elementReferenceType', 'referencedElement'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        TodoListElement::fromArray($row);
    }

    public function testUnknownReferenceTypeThrows(): void
    {
        $row = $this->row();
        $row['elementReferenceType'] = 'meeting';

        $this->expectException(HydrationException::class);
        TodoListElement::fromArray($row);
    }
}
