<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

/**
 * HTTP 403: the token is valid, but this operation is not allowed (e.g. "Task is closed").
 */
final class ForbiddenException extends FactroRequestException
{
}
