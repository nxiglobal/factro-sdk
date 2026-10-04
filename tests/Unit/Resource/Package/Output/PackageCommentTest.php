<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Package\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Package\Output\PackageComment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Time\FactroDateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageComment::class)]
final class PackageCommentTest extends TestCase
{
    /** @return array<string, mixed> */
    private function row(int $index = 0): array
    {
        $row = Fixtures::json('package-comments')[$index];
        self::assertIsArray($row);

        /* @var array<string, mixed> $row */
        return $row;
    }

    public function testHydratesFromFixtureWithNestedSubComments(): void
    {
        $row = $this->row();
        $comment = PackageComment::fromArray($row);

        self::assertSame($row['id'], $comment->id);
        self::assertSame($row['taskPackageId'], $comment->taskPackageId);
        self::assertSame($row['text'], $comment->text);
        self::assertSame($row['creatorId'], $comment->creatorId);
        self::assertNull($comment->parentId);
        self::assertIsString($row['creationDate']);
        self::assertSame($row['creationDate'], FactroDateTime::toIsoUtc($comment->creationDate));
        self::assertIsInt($row['changeDate']);
        self::assertSame(intdiv($row['changeDate'], 1000), $comment->changeDate?->getTimestamp());
        self::assertSame($row['mandantId'], $comment->mandantId);
        self::assertCount(1, $comment->subComments);
        self::assertContainsOnlyInstancesOf(PackageComment::class, $comment->subComments);
        self::assertSame($comment->id, $comment->subComments[0]->parentId);
        self::assertSame([], $comment->subComments[0]->subComments);
    }

    public function testNullChangeDateAndEmptySubComments(): void
    {
        $comment = PackageComment::fromArray($this->row(1));

        self::assertNull($comment->changeDate);
        self::assertSame([], $comment->subComments);
    }

    public function testSubCommentsDefaultToEmptyList(): void
    {
        $comment = PackageComment::fromArray(['id' => 'c', 'taskPackageId' => 'k', 'text' => 'x', 'creatorId' => 'u', 'creationDate' => '2026-01-01T00:00:00.000Z', 'mandantId' => 'm']);

        self::assertSame([], $comment->subComments);
        self::assertNull($comment->parentId);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredKeys(): iterable
    {
        foreach (['id', 'taskPackageId', 'text', 'creatorId', 'creationDate', 'mandantId'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('requiredKeys')]
    public function testRequiredKeyMissingThrows(string $key): void
    {
        $row = $this->row();
        unset($row[$key]);

        $this->expectException(HydrationException::class);
        PackageComment::fromArray($row);
    }
}
