<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Http\Transport;

/**
 * Base class of the resource classes. Stateless; FactroClient creates a new instance per call.
 *
 * @internal
 */
abstract class AbstractResource
{
    public function __construct(protected readonly Transport $transport, protected readonly FactroOptions $options)
    {
    }

    /**
     * Turns a decoded list response into rows, rejecting anything that is not a list of objects.
     *
     * @param array<mixed>|null $body
     *
     * @return list<array<string, mixed>>
     */
    protected function rows(?array $body): array
    {
        if (null === $body) {
            return [];
        }
        $rows = [];
        foreach ($body as $index => $row) {
            if (!is_array($row)) {
                throw new HydrationException(static::class, (string) $index, 'array', $row);
            }
            $rows[] = $this->withStringKeys($row);
        }

        return $rows;
    }

    /**
     * Turns a decoded object response into a row. An empty body yields [] so that the DTO's
     * required-field check produces a HydrationException with a clear message.
     *
     * @param array<mixed>|null $body
     *
     * @return array<string, mixed>
     */
    protected function object(?array $body): array
    {
        return $this->withStringKeys($body ?? []);
    }

    /**
     * JSON objects always have string keys; PHP turns numeric ones into ints on decode.
     * Rebuilding the array makes the key type explicit for static analysis and callers alike.
     *
     * @param array<mixed> $row
     *
     * @return array<string, mixed>
     */
    private function withStringKeys(array $row): array
    {
        $object = [];
        foreach ($row as $key => $value) {
            $object[(string) $key] = $value;
        }

        return $object;
    }
}
