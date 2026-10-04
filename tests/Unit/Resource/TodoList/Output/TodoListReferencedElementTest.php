<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\TodoList\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\TodoList\Output\TodoListReferencedElement;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TodoListReferencedElement::class)]
final class TodoListReferencedElementTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $list = Fixtures::json('todo-lists')[0];
        self::assertIsArray($list);
        self::assertIsArray($list['listElements']);
        $element = $list['listElements'][$index];
        self::assertIsArray($element);
        $row = $element['referencedElement'];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesTaskReference(): void
    {
        $row = $this->row();
        $ref = TodoListReferencedElement::fromArray($row);

        self::assertSame($row['id'], $ref->id);
        self::assertSame($row['title'], $ref->title);
        self::assertSame('inProcess', $ref->taskState);
        self::assertSame('red', $ref->colorScheme);
        self::assertSame($row['projectId'], $ref->projectId);
        self::assertSame($row['parentPackageId'], $ref->parentPackageId);
    }

    public function testHydratesNoteReferenceWithNulls(): void
    {
        $ref = TodoListReferencedElement::fromArray($this->row(1));

        self::assertNull($ref->taskState);
        self::assertSame('blue', $ref->colorScheme);
        self::assertNull($ref->projectId);
        self::assertNull($ref->parentPackageId);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'title'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        TodoListReferencedElement::fromArray($row);
    }
}
