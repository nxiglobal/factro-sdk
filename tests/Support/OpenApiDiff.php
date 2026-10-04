<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Support;

/**
 * Compares two copies of the factro OpenAPI document: operations added or removed, schema properties changed.
 *
 * @internal used by bin/openapi-diff
 */
final class OpenApiDiff
{
    private const array METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];

    private function __construct()
    {
    }

    /**
     * @param array<mixed> $old
     * @param array<mixed> $new
     *
     * @return array{added_operations: list<string>, removed_operations: list<string>, changed_schemas: array<string, array{added: list<string>, removed: list<string>}>}
     */
    public static function compare(array $old, array $new): array
    {
        $oldOps = self::operations($old);
        $newOps = self::operations($new);
        $added = array_values(array_diff($newOps, $oldOps));
        $removed = array_values(array_diff($oldOps, $newOps));
        sort($added);
        sort($removed);

        $oldSchemas = self::schemas($old);
        $newSchemas = self::schemas($new);
        $changed = [];
        foreach (array_intersect_key($newSchemas, $oldSchemas) as $name => $newProps) {
            $addedProps = array_values(array_diff($newProps, $oldSchemas[$name]));
            $removedProps = array_values(array_diff($oldSchemas[$name], $newProps));
            if ([] === $addedProps && [] === $removedProps) {
                continue;
            }
            sort($addedProps);
            sort($removedProps);
            $changed[$name] = ['added' => $addedProps, 'removed' => $removedProps];
        }
        ksort($changed);

        return ['added_operations' => $added, 'removed_operations' => $removed, 'changed_schemas' => $changed];
    }

    /**
     * Pulls the OpenAPI document out of factro's swagger-ui-init.js, independent of how the
     * script is formatted: the document is the JSON literal between "swaggerDoc": and
     * "customOptions": in the options object of the swagger-ui-express template.
     *
     * @return array<mixed>
     *
     * @throws \UnexpectedValueException when the script does not contain the document
     */
    public static function extractSpec(string $script): array
    {
        $start = strpos($script, '"swaggerDoc":');
        $end = false === $start ? false : strpos($script, '"customOptions":', $start);
        if (false === $start || false === $end) {
            throw new \UnexpectedValueException('swagger-ui-init.js contains no "swaggerDoc" followed by "customOptions".');
        }

        $literal = rtrim(substr($script, $start + \strlen('"swaggerDoc":'), $end - $start - \strlen('"swaggerDoc":')), " \t\r\n,");
        $spec = json_decode($literal, true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($spec) || !isset($spec['openapi'], $spec['paths'])) {
            throw new \UnexpectedValueException('The "swaggerDoc" literal is not an OpenAPI document.');
        }

        return $spec;
    }

    /**
     * @param array{added_operations: list<string>, removed_operations: list<string>, changed_schemas: array<string, array{added: list<string>, removed: list<string>}>} $report
     */
    public static function render(array $report): string
    {
        $out = "## Added operations\n\n".self::list($report['added_operations']);
        $out .= "\n## Removed operations\n\n".self::list($report['removed_operations']);
        $out .= "\n## Changed schemas\n\n";
        if ([] === $report['changed_schemas']) {
            $out .= "- none\n";
        }
        foreach ($report['changed_schemas'] as $name => $change) {
            $out .= sprintf("- %s\n", $name);
            foreach ($change['added'] as $prop) {
                $out .= sprintf("    + %s\n", $prop);
            }
            foreach ($change['removed'] as $prop) {
                $out .= sprintf("    - %s\n", $prop);
            }
        }

        return $out;
    }

    /**
     * @param list<string> $items
     */
    private static function list(array $items): string
    {
        if ([] === $items) {
            return "- none\n";
        }

        return implode('', array_map(static fn (string $item): string => sprintf("- %s\n", $item), $items));
    }

    /**
     * @param array<mixed> $spec
     *
     * @return list<string> "METHOD /path"
     */
    private static function operations(array $spec): array
    {
        $paths = $spec['paths'] ?? [];
        if (!is_array($paths)) {
            return [];
        }
        $operations = [];
        foreach ($paths as $path => $item) {
            if (!is_array($item)) {
                continue;
            }
            foreach ($item as $method => $operation) {
                if (is_string($method) && in_array(strtolower($method), self::METHODS, true)) {
                    $operations[] = strtoupper($method).' '.$path;
                }
            }
        }

        return $operations;
    }

    /**
     * @param array<mixed> $spec
     *
     * @return array<string, list<string>> schema name => property names
     */
    private static function schemas(array $spec): array
    {
        $components = $spec['components'] ?? [];
        $schemas = is_array($components) ? ($components['schemas'] ?? []) : [];
        if (!is_array($schemas)) {
            return [];
        }
        $result = [];
        foreach ($schemas as $name => $schema) {
            $properties = is_array($schema) ? ($schema['properties'] ?? []) : [];
            $names = [];
            if (is_array($properties)) {
                foreach (array_keys($properties) as $property) {
                    $names[] = (string) $property;
                }
            }
            $result[(string) $name] = $names;
        }

        return $result;
    }
}
