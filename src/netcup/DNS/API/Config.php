<?php

declare(strict_types=1);

namespace netcup\DNS\API;

use netcup\DNS\API\Exception\ConfigurationException;

readonly class Config
{
    private static string $username = '';
    private static string $password = '';
    private static string $apiKey = '';
    private static string $apiPassword = '';
    private static int $customerId = 0;
    private static bool $log = true;
    private static string $logFile = '';
    private static bool $debug = false;
    private static bool $traceLog = false;

    private function __construct()
    {
        $this->validate();
    }

    /**
     * Create a Config instance from an array
     * 
     * @param array<string, mixed> $config
     * @throws ConfigurationException
     */
    public static function fromArray(array $config): self
    {
        self::$instance = new self(
            username: (string) ($config['username'] ?? ''),
            password: (string) ($config['password'] ?? ''),
            apiKey: (string) ($config['apiKey'] ?? ''),
            apiPassword: (string) ($config['apiPassword'] ?? ''),
            customerId: (int) ($config['customerId'] ?? 0),
            log: (bool) ($config['log'] ?? true),
            logFile: (string) ($config['logFile'] ?? ''),
            debug: (bool) ($config['debug'] ?? false),
            traceLog: (bool) ($config['traceLog'] ?? false),
        );
        
        return self::$instance;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * @throws ConfigurationException
     */
    private function validate(): void
    {
        if (empty($this->username)) {
            throw ConfigurationException::missingField('username');
        }
        if (empty($this->password)) {
            throw ConfigurationException::missingField('password');
        }
        if (empty($this->apiKey)) {
            throw ConfigurationException::missingField('apiKey');
        }
        if (empty($this->apiPassword)) {
            throw ConfigurationException::missingField('apiPassword');
        }
        if (empty($this->customerId)) {
            throw ConfigurationException::missingField('customerId');
        }
        if (empty($this->logFile)) {
            throw ConfigurationException::missingField('logFile');
        }
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getApiPassword(): string
    {
        return $this->apiPassword;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function isLog(): bool
    {
        return $this->log;
    }
    
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function isTraceLog(): bool
    {
        return $this->traceLog;
    }
}