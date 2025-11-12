<?php

declare(strict_types=1);

namespace netcup\DNS\API\Exception;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when there are issues with the API communication.
 */
final class ApiException extends RuntimeException
{
    private int $statusCode;

    public function __construct(string $message, int $statusCode, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function loginFailed(string $message, int $statusCode): self
    {
        return new self(sprintf('API login failed: %s', $message), $statusCode);
    }

    public static function dnsUpdateFailed(string $message, int $statusCode): self
    {
        return new self(sprintf('DNS update failed: %s', $message), $statusCode);
    }

    public static function logoutFailed(string $message, int $statusCode): self
    {
        return new self(sprintf('API logout failed: %s', $message), $statusCode);
    }
}