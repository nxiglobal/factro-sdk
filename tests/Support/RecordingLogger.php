<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Support;

use Psr\Log\AbstractLogger;

/**
 * Keeps every log record in memory so tests can assert on levels, messages and context.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{mixed, string, array<string, mixed>}> */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        /* @var array<string, mixed> $context */
        $this->records[] = [$level, (string) $message, $context];
    }
}
