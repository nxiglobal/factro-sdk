<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit\Resource\Project\Output;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\Resource\Project\Output\ProjectStructureNode;
use Nxi\Factro\Resource\Project\StructureNodeType;
use Nxi\Factro\Testing\Fixtures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectStructureNode::class)]
final class ProjectStructureNodeTest extends TestCase
{
    public function testHydratesTreeAndAnswersDepthQueries(): void
    {
        $root = ProjectStructureNode::fromArray(Fixtures::json('project-structure'));

        self::assertSame(StructureNodeType::PROJECT, $root->type);
        self::assertSame(0, $root->depthOf($root->id));
        self::assertSame($root, $root->find($root->id));
        $firstPackage = $root->children[0];
        self::assertSame(StructureNodeType::PACKAGE, $firstPackage->type);
        self::assertSame(1, $root->depthOf($firstPackage->id));
        self::assertSame($firstPackage, $root->find($firstPackage->id));
        $nestedPackage = $firstPackage->children[0];
        self::assertSame(2, $root->depthOf($nestedPackage->id));
        $task = $nestedPackage->children[0];
        self::assertSame(StructureNodeType::TASK, $task->type);
        self::assertSame(3, $root->depthOf($task->id));
        self::assertNull($root->find('nope'));
        self::assertNull($root->depthOf('nope'));
        self::assertContains($firstPackage->id, $root->packageIds());
        self::assertSame(4, count($root->packageIds()));
        self::assertNotContains($task->id, $root->packageIds());
        self::assertNotContains($root->id, $root->packageIds());
    }

    public function testPackageIdsArePreorder(): void
    {
        $root = ProjectStructureNode::fromArray(Fixtures::json('project-structure'));
        $expected = [
            $root->children[0]->id,
            $root->children[0]->children[0]->id,
            $root->children[0]->children[1]->id,
            $root->children[1]->id,
        ];

        self::assertSame($expected, $root->packageIds());
    }

    public function testChildrenDefaultToEmptyList(): void
    {
        $leaf = ProjectStructureNode::fromArray(['id' => 'x', 'type' => 'task']);

        self::assertSame([], $leaf->children);
        self::assertSame([], $leaf->packageIds());
    }

    public function testUnknownTypeThrows(): void
    {
        $this->expectException(HydrationException::class);
        ProjectStructureNode::fromArray(['id' => 'x', 'type' => 'room', 'children' => []]);
    }
}
