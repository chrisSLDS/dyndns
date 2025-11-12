<?php

declare(strict_types=1);

namespace netcup\DNS\API\Interface;

use netcup\DNS\API\ApiResponse;

interface DnsClientInterface
{
    public function login(int $customerId, string $apiKey, string $apiPassword, string $clientRequestId): ApiResponse;
    public function logout(int $customerId, string $apiKey, string $sessionId, string $clientRequestId): ApiResponse;
    public function getDnsRecords(string $hostname, int $customerId, string $apiKey, string $sessionId, string $clientRequestId): ApiResponse;
    public function updateDnsRecords(string $hostname, int $customerId, string $apiKey, string $sessionId, string $clientRequestId, object $recordSet): ApiResponse;
}