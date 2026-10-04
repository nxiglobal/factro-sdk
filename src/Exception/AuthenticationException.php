<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

/**
 * HTTP 401 (invalid token) or 403 (operation not allowed, e.g. "Task is closed").
 */
final class AuthenticationException extends FactroRequestException
{
}
