<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Comment\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Comment\CommentReferenceType;
use Nxi\Factro\Resource\Comment\Output\Comment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comment::class)]
final class CommentTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(): array
    {
        /* @var array<string, mixed> */
        return Fixtures::json('comment');
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $comment = Comment::fromArray($row);

        self::assertSame($row['id'], $comment->id);
        self::assertSame($row['referenceId'], $comment->referenceId);
        self::assertSame(CommentReferenceType::TASK, $comment->referenceType);
        self::assertSame($row['text'], $comment->text);
        self::assertSame($row['creatorId'], $comment->creatorId);
        self::assertNull($comment->parentId);
        self::assertIsString($row['creationDate']);
        self::assertSame($row['creationDate'], FactroDateTime::toIsoUtc($comment->creationDate));
        self::assertIsInt($row['changeDate']);
        self::assertSame(intdiv($row['changeDate'], 1000), $comment->changeDate?->getTimestamp());
        self::assertSame($row['mandantId'], $comment->mandantId);
    }

    public function testNullableFields(): void
    {
        $comment = Comment::fromArray(array_replace($this->row(), ['referenceType' => 'workRecord', 'parentId' => 'p', 'changeDate' => null]));

        self::assertSame(CommentReferenceType::WORK_RECORD, $comment->referenceType);
        self::assertSame('p', $comment->parentId);
        self::assertNull($comment->changeDate);
    }

    public function testUnknownReferenceTypeThrows(): void
    {
        $this->expectException(HydrationException::class);
        Comment::fromArray(array_replace($this->row(), ['referenceType' => 'appointment']));
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'referenceId', 'referenceType', 'text', 'creatorId', 'creationDate', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        Comment::fromArray($row);
    }
}
