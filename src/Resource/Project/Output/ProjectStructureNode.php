<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project\Output;

use Nxi\Factro\Mapping\Field;
use Nxi\Factro\Resource\Project\StructureNodeType;

/**
 * A node of GET /projects/{id}/structure: the project, its packages and tasks as a tree.
 */
final readonly class ProjectStructureNode
{
    /**
     * @param list<ProjectStructureNode> $children
     */
    public function __construct(
        public string $id,
        public StructureNodeType $type,
        public array $children,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $o = self::class;

        return new self(
            id: Field::string($data, 'id', $o),
            type: Field::enum($data, 'type', $o, StructureNodeType::class),
            children: Field::objectList($data, 'children', $o, self::fromArray(...)),
        );
    }

    /**
     * Depth-first search including this node.
     */
    public function find(string $id): ?self
    {
        if ($this->id === $id) {
            return $this;
        }
        foreach ($this->children as $child) {
            $hit = $child->find($id);
            if (null !== $hit) {
                return $hit;
            }
        }

        return null;
    }

    /**
     * 0 for this node, 1 for its children, and so on; null when the id is not in the tree.
     */
    public function depthOf(string $id, int $depth = 0): ?int
    {
        if ($this->id === $id) {
            return $depth;
        }
        foreach ($this->children as $child) {
            $hit = $child->depthOf($id, $depth + 1);
            if (null !== $hit) {
                return $hit;
            }
        }

        return null;
    }

    /**
     * Ids of every package node in pre-order.
     *
     * @return list<string>
     */
    public function packageIds(): array
    {
        $ids = StructureNodeType::PACKAGE === $this->type ? [$this->id] : [];
        foreach ($this->children as $child) {
            $ids = [...$ids, ...$child->packageIds()];
        }

        return $ids;
    }
}
