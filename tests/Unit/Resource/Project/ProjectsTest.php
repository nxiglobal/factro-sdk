<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Exception\ServerException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\OperationNotPermittedException;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Project\Input\NewProject;
use Nxi\Factro\Resource\Project\Input\ProjectChanges;
use Nxi\Factro\Resource\Project\Output\Project;
use Nxi\Factro\Resource\Project\Projects;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use Nxi\Factro\Time\CalendarDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Projects::class)]
final class ProjectsTest extends TestCase
{
    public function testListHydratesAllRows(): void
    {
        $client = MockFactro::client(['GET /projects' => JsonMockResponse::fromFile(Fixtures::path('projects'))]);

        $projects = $client->projects()->list();

        self::assertCount(3, $projects);
        self::assertContainsOnlyInstancesOf(Project::class, $projects);
    }

    public function testGetAndFind(): void
    {
        $client = MockFactro::client([
            'GET /projects/p1' => static fn (): MockResponse => JsonMockResponse::fromFile(Fixtures::path('project')),
            'GET /projects/missing' => static fn (): MockResponse => new MockResponse('Project with id "missing" not found', ['http_code' => 404]),
        ]);

        self::assertInstanceOf(Project::class, $client->projects()->get('p1'));
        self::assertInstanceOf(Project::class, $client->projects()->find('p1'));
        self::assertNull($client->projects()->find('missing'));
        $this->expectException(NotFoundException::class);
        $client->projects()->get('missing');
    }

    public function testFindOnlySwallowsNotFound(): void
    {
        $client = MockFactro::client(['GET /projects/p1' => new MockResponse('', ['http_code' => 500])]);

        $this->expectException(ServerException::class);
        $client->projects()->find('p1');
    }

    public function testUpdateSendsPartialPayloadAndEncodesId(): void
    {
        $client = MockFactro::client(['PUT /projects/p%2F1' => static function (string $method, string $url, array $options): MockResponse {
            self::assertIsString($options['body']);
            self::assertJsonStringEqualsJsonString('{"title":"New"}', $options['body']);

            return JsonMockResponse::fromFile(Fixtures::path('project'));
        }]);

        self::assertInstanceOf(Project::class, $client->projects()->update('p/1', new ProjectChanges(title: 'New')));
    }

    public function testUpdateWithEmptyChangesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->projects()->update('p1', new ProjectChanges());
    }

    public function testCreatePostsPayloadAndReturnsProject(): void
    {
        $client = MockFactro::client(['POST /projects' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('{"title":"P","projectState":"planned","plannedStartDate":"2026-09-30T22:00:00.000Z"}', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('project'));
        }], new FactroOptions(baseUrl: MockFactro::BASE_URL, timezone: new \DateTimeZone('Europe/Berlin'), maxRetries: 0));

        $project = $client->projects()->create(new NewProject('P', projectState: ProjectState::PLANNED, plannedStartDate: CalendarDate::fromYmd('2026-10-01')));

        self::assertInstanceOf(Project::class, $project);
    }

    public function testDeleteDiscardsTheResponse(): void
    {
        $client = MockFactro::client(['DELETE /projects/p%2F1' => JsonMockResponse::fromFile(Fixtures::path('project'))]);

        $client->projects()->delete('p/1');
        $this->addToAssertionCount(1);
    }

    public function testDeleteIsBlockedByPolicy(): void
    {
        $client = MockFactro::client([], new FactroOptions(baseUrl: MockFactro::BASE_URL, policy: RequestPolicy::withoutDeletes(), maxRetries: 0));

        $this->expectException(OperationNotPermittedException::class);
        $client->projects()->delete('p1');
    }

    public function testCreateManyPostsAListOfPayloads(): void
    {
        $client = MockFactro::client(['POST /projects/projects' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"title":"A"},{"title":"B","isDraft":true}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('projects'));
        }]);

        $projects = $client->projects()->createMany([new NewProject('A'), new NewProject('B', isDraft: true)]);

        self::assertCount(3, $projects);
        self::assertContainsOnlyInstancesOf(Project::class, $projects);
    }

    public function testCreateManyWithEmptyListIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->projects()->createMany([]);
    }

    public function testUpdateManySendsIdWithEachChangeSet(): void
    {
        $client = MockFactro::client(['PUT /projects/projects' => static function (string $m, string $u, array $o): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString('[{"id":"p1","title":"New"},{"id":"p2","projectState":"closed"}]', $o['body']);

            return JsonMockResponse::fromFile(Fixtures::path('projects'));
        }]);

        $projects = $client->projects()->updateMany(['p1' => new ProjectChanges(title: 'New'), 'p2' => new ProjectChanges(projectState: ProjectState::CLOSED)]);

        self::assertCount(3, $projects);
        self::assertContainsOnlyInstancesOf(Project::class, $projects);
    }

    public function testUpdateManyWithEmptyMapIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->projects()->updateMany([]);
    }

    public function testUpdateManyRejectsAnEmptyChangeSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockFactro::client([])->projects()->updateMany(['p1' => new ProjectChanges()]);
    }
}
