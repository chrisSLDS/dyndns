<?php

declare(strict_types=1);

namespace netcup\DNS\API;

/**
 * Handles logging operations for the DNS update system
 */
class Logger
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /** @var array<string, array<string>> */
    private static array $domainLogs = [];

    /**
     * Log DNS record updates in the original format
     */

    public static function debug(string $msg): void    {
        if (Config::isDebug()) {
            printf('[DEBUG] %s %s', $msg, PHP_EOL);
        }
    }

    public static function logDnsUpdate(string $domain, string $message): void
    {
        if (!Config::isLog()) {
            return;
        }

        if (!isset(self::$domainLogs[$domain])) {
            self::$domainLogs[$domain] = [];

            // Load existing logs if available
            $logFile = dirname(__DIR__, 4) . '/log.json';
            if (is_readable($logFile)) {
                $existingLogs = json_decode(file_get_contents($logFile), true);
                if (is_array($existingLogs)) {
                    self::$domainLogs = $existingLogs;
                    if (!isset(self::$domainLogs[$domain])) {
                        self::$domainLogs[$domain] = [];
                    }
                }
            }
        }

        // Add new log entry
        self::$domainLogs[$domain][] = sprintf('[%s] %s', date('c'), $message);

        // Keep only the newest 100 entries for each domain
        self::$domainLogs[$domain] = array_reverse(
            array_slice(
                array_reverse(self::$domainLogs[$domain]),
                0,
                100
            )
        );

        // Save to log.json
        $logFile = dirname(__DIR__, 4) . '/log.json';
        file_put_contents(
            $logFile,
            json_encode(self::$domainLogs, JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    /**
     * Log detailed trace information for debugging
     */
    public static function logTrace(array $data): void
    {
        if (!Config::isTraceLog()) {
            return;
        }

        $traceFile = dirname(__DIR__, 4) . '/trace.log';
        $entry = [
            'timestamp' => date(self::DATE_FORMAT),
            'data' => $data
        ];

        $json = json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = sprintf(
                '{"error": "Failed to encode trace data", "reason": "%s"}',
                json_last_error_msg()
            );
        }

        file_put_contents(
            $traceFile,
            $json . PHP_EOL . str_repeat('-', 80) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
