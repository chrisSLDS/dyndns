<?php

declare(strict_types=1);

namespace netcup\DNS\API\Exception;

use RuntimeException;

/**
 * Exception thrown when there are issues with the payload validation.
 */
final class PayloadException extends RuntimeException
{
    public static function missingField(string $field): self
    {
        return new self(sprintf('Missing required payload field: %s', $field));
    }

    public static function invalidField(string $field, string $reason): self
    {
        return new self(sprintf('Invalid payload field %s: %s', $field, $reason));
    }

    public static function invalidCredentials(): self
    {
        return new self('Invalid credentials provided');
    }
}