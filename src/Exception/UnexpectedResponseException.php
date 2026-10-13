<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

/**
 * factro answered with a success status, but the body is not a JSON object or array
 * (an HTML error page from a proxy, a truncated body, a JSON scalar).
 */
final class UnexpectedResponseException extends FactroRequestException
{
}
