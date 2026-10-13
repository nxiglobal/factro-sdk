<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Task;

use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Tests\Support\Id;
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
        $client = MockFactro::client(['DELETE /tasks/'.Id::T1.'/task_connections/'.Id::T2 => new MockResponse('', ['http_code' => 204])]);

        $client->tasks()->removeConnection(Id::T1, Id::T2);
        $this->addToAssertionCount(1);
    }

    public function testMoveToProjectOmitsPositionWhenNull(): void
    {
        $client = MockFactro::client(['PUT /tasks/'.Id::T1.'/project' => $this->expectingBody('{"projectId":"'.Id::P1.'"}')]);

        $client->tasks()->moveToProject(Id::T1, Id::P1);
        $this->addToAssertionCount(1);
    }

    public function testMoveToProjectWithPosition(): void
    {
        $client = MockFactro::client(['PUT /tasks/'.Id::T1.'/project' => $this->expectingBody('{"projectId":"'.Id::P1.'","position":3}')]);

        $client->tasks()->moveToProject(Id::T1, Id::P1, 3);
        $this->addToAssertionCount(1);
    }

    public function testRemoveFromProject(): void
    {
        $client = MockFactro::client(['DELETE /tasks/'.Id::T1.'/project' => new MockResponse('', ['http_code' => 204])]);

        $client->tasks()->removeFromProject(Id::T1);
        $this->addToAssertionCount(1);
    }

    public function testMoveToPackageOmitsPositionWhenNull(): void
    {
        $client = MockFactro::client(['PUT /tasks/'.Id::T1.'/package' => $this->expectingBody('{"packageId":"'.Id::PK1.'"}')]);

        $client->tasks()->moveToPackage(Id::T1, Id::PK1);
        $this->addToAssertionCount(1);
    }

    public function testMoveToPackageWithPosition(): void
    {
        $client = MockFactro::client(['PUT /tasks/'.Id::T1.'/package' => $this->expectingBody('{"packageId":"'.Id::PK1.'","position":0}')]);

        $client->tasks()->moveToPackage(Id::T1, Id::PK1, 0);
        $this->addToAssertionCount(1);
    }

    public function testRemoveFromPackage(): void
    {
        $client = MockFactro::client(['DELETE /tasks/'.Id::T1.'/package' => new MockResponse('', ['http_code' => 204])]);

        $client->tasks()->removeFromPackage(Id::T1);
        $this->addToAssertionCount(1);
    }

    public function testSetAndRemoveCompany(): void
    {
        $client = MockFactro::client([
            'PUT /tasks/'.Id::T1.'/company' => $this->expectingBody('{"companyId":"'.Id::C1.'"}'),
            'DELETE /tasks/'.Id::T1.'/company' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->tasks()->setCompany(Id::T1, Id::C1);
        $client->tasks()->removeCompany(Id::T1);
        $this->addToAssertionCount(2);
    }

    public function testSetAndRemoveContact(): void
    {
        $client = MockFactro::client([
            'PUT /tasks/'.Id::T1.'/contact' => $this->expectingBody('{"contactId":"'.Id::K1.'"}'),
            'DELETE /tasks/'.Id::T1.'/contact' => new MockResponse('', ['http_code' => 204]),
        ]);

        $client->tasks()->setContact(Id::T1, Id::K1);
        $client->tasks()->removeContact(Id::T1);
        $this->addToAssertionCount(2);
    }
}
