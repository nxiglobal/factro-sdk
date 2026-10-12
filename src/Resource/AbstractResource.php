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
    private const string UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(protected readonly Transport $transport, protected readonly FactroOptions $options)
    {
    }

    /**
     * factro ids are UUIDs. Anything else is rejected before it can change the request path
     * (empty ids, dot segments, slashes, query characters). Shape only, no version or variant check.
     *
     * @throws \InvalidArgumentException
     */
    protected static function id(string $id): string
    {
        if (1 !== preg_match(self::UUID, $id)) {
            throw new \InvalidArgumentException(sprintf('Expected a factro id (UUID), got "%s".', $id));
        }

        return $id;
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
