<?php

declare(strict_types=1);

namespace Nxi\Factro\Testing;

/**
 * Locates the hand-written response fixtures shipped with the SDK so that consumers can feed them
 * into MockHttpClient (JsonMockResponse::fromFile(Fixtures::path('task'))).
 */
final class Fixtures
{
    private function __construct()
    {
    }

    /**
     * Absolute path of fixtures/<name>.json.
     *
     * @throws \InvalidArgumentException when the fixture does not exist
     */
    public static function path(string $name): string
    {
        $path = self::directory().'/'.$name.'.json';
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Fixture "%s" does not exist at %s.', $name, $path));
        }

        return $path;
    }

    /**
     * Decoded content of fixtures/<name>.json.
     *
     * @return array<mixed>
     */
    public static function json(string $name): array
    {
        $decoded = json_decode((string) file_get_contents(self::path($name)), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new \UnexpectedValueException(sprintf('Fixture "%s" is not a JSON object or array.', $name));
        }

        return $decoded;
    }

    /**
     * Raw content of fixtures/<relative> for text and HTML error bodies (e.g. "errors/401.txt").
     */
    public static function raw(string $relative): string
    {
        $path = self::directory().'/'.$relative;
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Fixture "%s" does not exist at %s.', $relative, $path));
        }

        return (string) file_get_contents($path);
    }

    public static function directory(): string
    {
        return dirname(__DIR__, 2).'/fixtures';
    }
}
