<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Tasks::class)]
final class TasksAssociationsTest extends TestCase
{
    /**
     * @return \Closure(string, string, array<string, mixed>): MockResponse
     */
    private function expectingBody(string $json): \Closure
    {
        return static function (string $m, string $u, array $o) use ($json): MockResponse {
            self::assertIsString($o['body']);
            self::assertJsonStringEqualsJsonString($json, $o['body']);

            return new MockResponse('', ['http_code' => 204]);
        };
    }

    public function testRemoveConnection(): void
    {
        $client = MockFactro::client(['DELETE /tasks/t1/task_connections/t2' => new MockResponse('', ['http_code' => 204])]);

        $client->tasks()->removeConnection('t1', 't2');
        $this->addToAssertionCount(1);
    }

    public function testMoveToProjectOmitsPositionWhenNull(): void
    {
        $client = MockFactro::client(['PUT /tasks/t1/project' => $this->expectingBody('{"projectId":"p1"}')]);

        $client->tasks()->moveToProject('t1', 'p1');
        $this->addToAssertionCount(1);
    }

    public function testMoveToProjectWithPosition(): void
    {
        $client = MockFactro::client(['PUT /tasks/t1/project' => $this->expectingBody('{"projectId":"p1","position":3}')]);

        $client->tasks()->moveToProject('t1', 'p1', 3);
        $this->addToAssertionCount(1);
    }

    public function testRemoveFromProject(): void
    {
        $client = MockFactro::client(['DELETE /tasks/t1/project' => new MockResponse('', ['http_code' => 204])]);

        $client->tasks()->removeFromProject('t1');
        $this->addToAssertionCount(1);
    }

    public function testMoveToPackageOmitsPositionWhenNull(): void
    {
        $client = MockFactro::client(['PUT /tasks/t1/package' => $this->expectingBody('{"packageId":"pk1"}')]);

        $client->tasks()->moveToPackage('t1', 'pk1');
        $this->addToAssertionCount(1);
    }

    public function testMoveToPackageWithPosition(): void
    {
        $client = MockFactro::client(['PUT /tasks/t1/package' => $this->expectingBody('{"packageId":"pk1","position":0}')]);

        $client->tasks()->moveToPackage('t1', 'pk1', 0);
        $this->addToAssertionCount(1);
    }

    public function testRemoveFromPackage(): void
    {
        $client = MockFactro::client(['DELETE /tasks/t1/package' => new MockResponse('', ['http_code' => 204])]);

        $client->tasks()->removeFromPackage('t1');
        $this->addToAssertionCount(1);
    }

    public function testSetAndRemoveCompany(): void
    {
        $client = MockFactro::client([
            'PUT /tasks/t1/company' => $this->expectingBody('{"companyId":"c1"}'),
            'DELETE /tasks/t1/company' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->tasks()->setCompany('t1', 'c1');
        $client->tasks()->removeCompany('t1');
        $this->addToAssertionCount(2);
    }

    public function testSetAndRemoveContact(): void
    {
        $client = MockFactro::client([
            'PUT /tasks/t1/contact' => $this->expectingBody('{"contactId":"k1"}'),
            'DELETE /tasks/t1/contact' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->tasks()->setContact('t1', 'k1');
        $client->tasks()->removeContact('t1');
        $this->addToAssertionCount(2);
    }
}
