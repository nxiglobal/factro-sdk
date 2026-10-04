<?php

declare(strict_types=1);

namespace Nxi\Factro\Policy;

use Nxi\Factro\Exception\FactroException;

/**
 * Thrown before a request is sent when the consumer's RequestPolicy forbids it.
 */
final class OperationNotPermittedException extends \LogicException implements FactroException
{
    public function __construct(public readonly string $method, public readonly string $path)
    {
        parent::__construct(sprintf('Operation "%s %s" is not permitted by the request policy.', $method, $path));
    }
}
