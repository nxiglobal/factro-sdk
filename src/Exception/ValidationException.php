<?php

declare(strict_types=1);

namespace Nxi\Factro\Exception;

/**
 * HTTP 400 or 422.
 */
final class ValidationException extends FactroRequestException
{
    /**
     * @return array<mixed> errors, violations or details from the body, else []
     */
    public function errors(): array
    {
        $body = $this->decodedBody() ?? [];
        foreach (['errors', 'violations', 'details'] as $key) {
            if (isset($body[$key]) && is_array($body[$key])) {
                return $body[$key];
            }
        }

        return [];
    }
}
