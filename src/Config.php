<?php

declare(strict_types=1);

namespace netcup\DNS\API;

use netcup\DNS\API\Exception\ConfigurationException;

final class Config
{
    // Init-Guard
    private static bool $initialized = false;

    private static string $username;
    private static string $password;
    private static string $apiKey;
    private static string $apiPassword;
    private static int    $customerId;
    private static bool   $log;
    private static string $logFile;
    private static bool   $debug;
    private static bool   $traceLog;

    private function __construct() {} // no instances

    /**
     * @param array<string,mixed> $config
     * @throws ConfigurationException|\LogicException
     */
    public static function init(array $config): void
    {
        if (self::$initialized) {
            throw new \LogicException('Config already initialized.');
        }

        self::$username    = (string)($config['username']   ?? '');
        self::$password    = (string)($config['password']   ?? '');
        self::$apiKey      = (string)($config['apiKey']     ?? '');
        self::$apiPassword = (string)($config['apiPassword'] ?? '');
        self::$customerId  = (int)   ($config['customerId'] ?? 0);
        self::$log         = (bool)  ($config['log']        ?? true);
        self::$logFile     = (string)($config['logFile']    ?? '');
        self::$debug       = (bool)  ($config['debug']      ?? false);
        self::$traceLog    = (bool)  ($config['traceLog']   ?? false);

        self::validate();
        self::$initialized = true;
    }

    private static function ensureInit(): void
    {
        if (!self::$initialized) {
            throw new \LogicException('Config not initialized. Call Config::init() first.');
        }
    }

    /**
     * @throws ConfigurationException
     */
    private static function validate(): void
    {
        if (self::$username === '')   throw ConfigurationException::missingField('username');
        if (self::$password === '')   throw ConfigurationException::missingField('password');
        if (self::$apiKey === '')     throw ConfigurationException::missingField('apiKey');
        if (self::$apiPassword === '') throw ConfigurationException::missingField('apiPassword');
        if (self::$customerId === 0)  throw ConfigurationException::missingField('customerId');
        if (self::$logFile === '')    throw ConfigurationException::missingField('logFile');
    }

    public static function getUsername(): string
    {
        self::ensureInit();
        return self::$username;
    }
    public static function getPassword(): string
    {
        self::ensureInit();
        return self::$password;
    }
    public static function getApiKey(): string
    {
        self::ensureInit();
        return self::$apiKey;
    }
    public static function getApiPassword(): string
    {
        self::ensureInit();
        return self::$apiPassword;
    }
    public static function getCustomerId(): int
    {
        self::ensureInit();
        return self::$customerId;
    }
    public static function isLog(): bool
    {
        self::ensureInit();
        return self::$log;
    }
    public static function getLogFile(): string
    {
        self::ensureInit();
        return self::$logFile;
    }
    public static function isDebug(): bool
    {
        self::ensureInit();
        return self::$debug;
    }
    public static function isTraceLog(): bool
    {
        self::ensureInit();
        return self::$traceLog;
    }
}
