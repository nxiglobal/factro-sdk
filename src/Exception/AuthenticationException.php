<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

/**
 * HTTP 401: the token is missing or invalid.
 */
final class AuthenticationException extends FactroRequestException
{
}
