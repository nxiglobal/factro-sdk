<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project;

use Nxi\Factro\Resource\Project\Output\ProjectStructureNode;
use Nxi\Factro\Resource\Project\Projects;
use Nxi\Factro\Resource\Project\StructureNodeType;
use Nxi\Factro\Testing\Fixtures;
use Nxi\Factro\Tests\Support\MockFactro;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

#[CoversClass(Projects::class)]
final class ProjectsStructureTest extends TestCase
{
    public function testStructureReturnsTheRootNode(): void
    {
        $client = MockFactro::client(['GET /projects/p1/structure' => JsonMockResponse::fromFile(Fixtures::path('project-structure'))]);

        $root = $client->projects()->structure('p1');

        self::assertInstanceOf(ProjectStructureNode::class, $root);
        self::assertSame(StructureNodeType::PROJECT, $root->type);
        self::assertNotEmpty($root->children);
    }
}
