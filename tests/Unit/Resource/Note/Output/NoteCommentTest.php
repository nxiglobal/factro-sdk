<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Note\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Note\Output\NoteComment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(NoteComment::class)]
final class NoteCommentTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('note-comments')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixture(): void
    {
        $row = $this->row();
        $comment = NoteComment::fromArray($row);

        self::assertSame($row['id'], $comment->id);
        self::assertSame($row['noteId'], $comment->noteId);
        self::assertSame($row['text'], $comment->text);
        self::assertSame($row['creatorId'], $comment->creatorId);
        self::assertNull($comment->parentId);
        self::assertIsString($row['creationDate']);
        self::assertSame($row['creationDate'], FactroDateTime::toIsoUtc($comment->creationDate));
        self::assertIsInt($row['changeDate']);
        self::assertSame(intdiv($row['changeDate'], 1000), $comment->changeDate?->getTimestamp());
        self::assertSame($row['mandantId'], $comment->mandantId);
    }

    public function testReplyWithNullChangeDate(): void
    {
        $row = $this->row(1);
        $comment = NoteComment::fromArray($row);

        self::assertSame($row['parentId'], $comment->parentId);
        self::assertNull($comment->changeDate);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'noteId', 'text', 'creatorId', 'creationDate', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        NoteComment::fromArray($row);
    }
}
