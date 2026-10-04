<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\CustomView;

use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\CustomView\CustomViewReferenceType;
use Nxi\Factro\Resource\CustomView\CustomViews;
use Nxi\Factro\Resource\CustomView\CustomViewViewType;
use Nxi\Factro\Resource\CustomView\Input\CustomViewChanges;
use Nxi\Factro\Resource\CustomView\Input\NewCustomView;
use Nxi\Factro\Resource\CustomView\Output\CustomView;
use Nxi\Factro\Resource\CustomView\Output\SortEntry;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(CustomViews::class)]
#[CoversClass(CustomViewReferenceType::class)]
#[CoversClass(CustomViewViewType::class)]
final class CustomViewsTest extends TestCase
{
    public function testTemplatesUsesTemplatesEndpoint(): void
    {
        $client = MockFactro::client(['GET /custom-views/templates' => JsonMockResponse::fromFile(Fixtures::path('custom-views'))]);

        $views = $client->customViews()->templates();

        self::assertCount(2, $views);
        self::assertContainsOnlyInstancesOf(CustomView::class, $views);
    }

    public function testListByReferenceUsesByReferenceEndpoint(): void
    {
        $client = MockFactro::client(['GET /custom-views/by-reference/p1' => JsonMockResponse::fromFile(Fixtures::path('custom-views'))]);

        $views = $client->customViews()->listByReference('p1');

        self::assertCount(2, $views);
        self::assertContainsOnlyInstancesOf(CustomView::class, $views);
    }

    public function testGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /custom-views/v1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('custom-view')),
            'GET /custom-views/missing' => new MockResponse(Fixtures::raw('errors/404-task.txt'), ['http_code' => 404]),
        ]);

        self::assertInstanceOf(CustomView::class, $client->customViews()->get('v1'));
        self::assertInstanceOf(CustomView::class, $client->customViews()->find('v1'));
        self::assertNull($client->customViews()->find('missing'));
    }

    public function testCreatePostsPayloadAndReturnsCustomView(): void
    {
        $client = MockFactro::client(['POST /custom-views' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString(
                '{"referenceId":"p1","referenceType":"Project","title":"Board","sourceTemplateId":"tpl","sorting":[{"id":"endDate","desc":false}]}',
                $o['body'],
            );

            return JsonMockResponse::fromFile(Fixtures::path('custom-view'));
        }]);

        $view = $client->customViews()->create(new NewCustomView('p1', CustomViewReferenceType::PROJECT, 'Board', 'tpl', sorting: [new SortEntry('endDate', false)]));

        self::assertInstanceOf(CustomView::class, $view);
    }

    public function testCreateTemplatePostsTitleAndDescription(): void
    {
        $client = MockFactro::client(['POST /custom-views/template/src' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"title":"Template","description":"<p>x</p>"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('custom-view'));
        }]);

        self::assertInstanceOf(CustomView::class, $client->customViews()->createTemplate('src', 'Template', '<p>x</p>'));
    }

    public function testUpdateSendsOnlyChangedFields(): void
    {
        $client = MockFactro::client(['PUT /custom-views/v1' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"title":"New","grouping":["taskState"]}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('custom-view'));
        }]);

        self::assertInstanceOf(CustomView::class, $client->customViews()->update('v1', new CustomViewChanges(title: 'New', grouping: ['taskState'])));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->customViews()->update('v1', new CustomViewChanges());
    }

    public function testDeleteDiscardsResponse(): void
    {
        $client = MockFactro::client(['DELETE /custom-views/v1' => JsonMockResponse::fromFile(Fixtures::path('custom-view'))]);

        $client->customViews()->delete('v1');
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->customViews()->delete('v1');
    }

    public function testSetPositionUsesPathOnlyWithoutBody(): void
    {
        $client = MockFactro::client(['PUT /custom-views/p1/v1/position/3' => static function (string $m, string $u, array $o): MockResponse {
            self::assertSame('PUT', $m);
            self::assertTrue(!isset($o['body']) || '' === $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('custom-view'));
        }]);

        self::assertInstanceOf(CustomView::class, $client->customViews()->setPosition('p1', 'v1', 3));
    }

    public function testEnumValuesMatchTheApi(): void
    {
        self::assertSame(['Project', 'Room'], array_column(CustomViewReferenceType::cases(), 'value'));
        self::assertSame(['Kanban', 'Gantt', 'Grid', 'PSB', 'Link', 'StatusReport', 'RoomOverview'], array_column(CustomViewViewType::cases(), 'value'));
    }
}
