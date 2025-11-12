<?php

declare(strict_types=1);

namespace netcup\DNS\API\Exception;

use RuntimeException;

/**
 * Exception thrown when there are issues with the configuration validation.
 */
final class ConfigurationException extends RuntimeException
{
    public static function missingField(string $field): self
    {
        return new self(sprintf('Missing required configuration field: %s', $field));
    }

    public static function invalidField(string $field, string $reason): self
    {
        return new self(sprintf('Invalid configuration field %s: %s', $field, $reason));
    }
}