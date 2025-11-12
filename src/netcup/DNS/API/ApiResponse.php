<?php

declare(strict_types=1);

namespace netcup\DNS\API;

/**
 * Value object for API responses
 */
final class ApiResponse
{
    private int $statusCode;
    private string $message;
    private string $sessionId;
    private ?array $dnsRecords;

    public function __construct(object $response)
    {
        $this->statusCode = $response->statuscode;
        $this->message = $response->longmessage ?? '';
        $this->sessionId = $response->responsedata->apisessionid ?? '';
        $this->dnsRecords = $response->responsedata->dnsrecords ?? null;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getDnsRecords(): ?array
    {
        return $this->dnsRecords;
    }

    public function isSuccess(): bool
    {
        return $this->statusCode === 2000;
    }
}