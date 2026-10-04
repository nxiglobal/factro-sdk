<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\CustomView\Output;

use Nxi\Factro\Mapping\Field;

/**
 * One sorting criterion of a custom view (IBookmarkSortEntry). Used in responses and, via toPayload(), in requests.
 */
final readonly class SortEntry
{
    public function __construct(
        public string $id,
        public bool $desc = false,
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
            desc: Field::nullableBool($data, 'desc', $o) ?? false,
        );
    }

    /**
     * @return array{id: string, desc: bool}
     */
    public function toPayload(): array
    {
        return ['id' => $this->id, 'desc' => $this->desc];
    }
}
