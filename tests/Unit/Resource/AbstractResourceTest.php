<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource;

use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Comment\Comments;
use Nxi\Factro\Resource\Comment\Output\Comment;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\Id;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

#[CoversClass(AbstractResource::class)]
#[CoversClass(Comments::class)]
final class AbstractResourceTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'dot' => ['.'];
        yield 'dot dot' => ['..'];
        yield 'short id' => ['P1'];
        yield 'uuid with path suffix' => [Id::C1.'/x'];
        yield 'uuid with query' => [Id::C1.'?a=b'];
    }

    #[DataProvider('invalidIds')]
    public function testNonUuidIdIsRejectedBeforeSending(string $id): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->comments()->get($id);
    }

    public function testUpperCaseUuidIsPassedOnUnchanged(): void
    {
        $id = strtoupper(Id::C1);
        $client = MockFactro::client(['GET /comments/'.$id => JsonMockResponse::fromFile(Fixtures::path('comment'))]);

        self::assertInstanceOf(Comment::class, $client->comments()->get($id));
    }
}
