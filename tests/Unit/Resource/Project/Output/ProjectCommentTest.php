<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Project\Output\ProjectComment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectComment::class)]
final class ProjectCommentTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('project-comments')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixtureWithNestedSubComments(): void
    {
        $row = $this->row();
        $comment = ProjectComment::fromArray($row);

        self::assertSame($row['id'], $comment->id);
        self::assertSame($row['projectId'], $comment->projectId);
        self::assertSame($row['text'], $comment->text);
        self::assertSame($row['creatorId'], $comment->creatorId);
        self::assertNull($comment->parentId);
        self::assertIsString($row['creationDate']);
        self::assertSame($row['creationDate'], FactroDateTime::toIsoUtc($comment->creationDate));
        self::assertIsInt($row['changeDate']);
        self::assertSame(intdiv($row['changeDate'], 1000), $comment->changeDate?->getTimestamp());
        self::assertSame($row['mandantId'], $comment->mandantId);
        self::assertCount(1, $comment->subComments);
        self::assertContainsOnlyInstancesOf(ProjectComment::class, $comment->subComments);
        self::assertSame($comment->id, $comment->subComments[0]->parentId);
        self::assertSame([], $comment->subComments[0]->subComments);
    }

    public function testNullChangeDateAndEmptySubComments(): void
    {
        $comment = ProjectComment::fromArray($this->row(1));

        self::assertNull($comment->changeDate);
        self::assertSame([], $comment->subComments);
    }

    public function testSubCommentsDefaultToEmptyList(): void
    {
        $comment = ProjectComment::fromArray(['id' => 'c', 'projectId' => 'p', 'text' => 'x', 'creatorId' => 'u', 'creationDate' => '2026-01-01T00:00:00.000Z', 'mandantId' => 'm']);

        self::assertSame([], $comment->subComments);
        self::assertNull($comment->parentId);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'projectId', 'text', 'creatorId', 'creationDate', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        ProjectComment::fromArray($row);
    }
}
