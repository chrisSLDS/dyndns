<?php

error_reporting(-1);
ini_set('html_errors', '0');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');

header('Content-Type: text/plain; charset=utf-8');

// Custom error handler to show clean messages but log full stack traces
set_exception_handler(function (Throwable $e) {
    // Log the full stack trace
    error_log($e->__toString());

    // Show only the clean message to the user
    echo $e->getMessage() . PHP_EOL;
    exit(1);
});

// Register autoloader
require_once __DIR__ . '/src/autoload.php';

use netcup\DNS\API\Handler;
use netcup\DNS\API\Config;
use netcup\DNS\API\Logger;
use netcup\DNS\API\Payload;
use netcup\DNS\API\Exception\ApiException;
use netcup\DNS\API\Exception\ConfigurationException;
use netcup\DNS\API\Exception\PayloadException;

if (!file_exists('.env')) {
    throw new RuntimeException('.env file missing');
}

$config = parse_ini_file('.env', false, INI_SCANNER_TYPED);
Config::init($config);


/**
 * Extract credentials from HTTP Basic Authorization header if present.
 * Some routers (including certain Speedport configurations) send credentials via HTTP Basic auth instead
 * of request parameters. This helper returns an associative array with keys 'user' and 'password' when found.
 */
function getAuthFromServer(): array
{
    // PHP sets these when using HTTP Basic Auth
    if (!empty($_SERVER['PHP_AUTH_USER']) || !empty($_SERVER['PHP_AUTH_PW'])) {
        return [
            'user' => $_SERVER['PHP_AUTH_USER'] ?? '',
            'password' => $_SERVER['PHP_AUTH_PW'] ?? '',
        ];
    }

    // Some setups put the Authorization header into HTTP_AUTHORIZATION or REDIRECT_HTTP_AUTHORIZATION
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    if ($authHeader) {
        if (stripos($authHeader, 'basic ') === 0) {
            $b64 = substr($authHeader, 6);
            $decoded = base64_decode($b64);
            if ($decoded !== false) {
                $parts = explode(':', $decoded, 2);
                return [
                    'user' => $parts[0] ?? '',
                    'password' => $parts[1] ?? '',
                ];
            }
        }
    }

    return [];
}


/**
 * Normalize request parameters from various clients (Speedport, standard DynDNS, custom clients)
 * into the keys expected by Payload: user, password, domain, mode, ipv4, ipv6, force
 */
function normalizeRequest(array $req, array $serverAuth = []): array
{
    $map = [
        'user'     => ['username', 'user', 'usr', 'login', 'sysuser'],
        'password' => ['password', 'pass', 'passwd', 'pwd', 'pw'],
        // domain can come as a fully qualified domain or as hostname + domain
        'domain'   => ['domain', 'hostname', 'host', 'name', 'system', 'host-name'],
        'ipv4'     => ['ipv4', 'ip', 'myip', 'ipv4addr'],
        'ipv6'     => ['ipv6', 'myipv6', 'ipv6addr'],
        'mode'     => ['mode'],
        'force'    => ['force', 'all', 'updateall'],
    ];

    $out = [];

    // start with canonical keys if provided
    foreach (['user', 'password', 'domain', 'mode', 'ipv4', 'ipv6', 'force'] as $k) {
        if (isset($req[$k])) {
            $out[$k] = $req[$k];
        }
    }

    // map aliases
    foreach ($map as $canonical => $aliases) {
        foreach ($aliases as $alias) {
            if (!isset($out[$canonical]) && isset($req[$alias])) {
                $out[$canonical] = $req[$alias];
                break;
            }
        }
    }

    // If hostname and domain are provided separately (hostname=www, domain=example.com), combine them
    if (isset($req['hostname']) && isset($req['domain'])) {
        $combined = rtrim($req['hostname'], '.') . '.' . ltrim($req['domain'], '.');
        // only override if domain wasn't already set to something else
        if (empty($out['domain'])) {
            $out['domain'] = $combined;
        }
    }

    // Trim strings
    foreach ($out as $k => $v) {
        if (is_string($v)) {
            $out[$k] = trim($v);
        }
    }

    // Normalize force to boolean
    if (isset($out['force'])) {
        $val = strtolower((string)$out['force']);
        $out['force'] = in_array($val, ['1', 'true', 'yes', 'on'], true) ? true : false;
    }

    // If server-provided Basic auth credentials exist and no explicit credentials were provided in parameters,
    // use the Basic auth values. This handles routers that authenticate via HTTP Basic (Speedport variants).
    if (!empty($serverAuth)) {
        if (empty($out['user']) && !empty($serverAuth['user'])) {
            $out['user'] = $serverAuth['user'];
        }
        if (empty($out['password']) && !empty($serverAuth['password'])) {
            $out['password'] = $serverAuth['password'];
        }
    }

    return $out;
}

// Helper function to write trace log
function writeTraceLog(array $entry): void
{
    $traceFile = __DIR__ . '/trace.log';
    $json = json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = "Failed to JSON-encode trace entry: " . json_last_error_msg();
    }
    file_put_contents($traceFile, $json . PHP_EOL . str_repeat('-', 80) . PHP_EOL, FILE_APPEND | LOCK_EX);
}


// Choose raw domains parameter from a list of known names (Speedport may use 'system')
$rawDomains = '';
foreach (['domain', 'hostname', 'host', 'name', 'system'] as $candidate) {
    if (isset($_REQUEST[$candidate]) && trim($_REQUEST[$candidate]) !== '') {
        $rawDomains = $_REQUEST[$candidate];
        break;
    }
}

if ($rawDomains === '') {
    printf('No domain parameter provided' . PHP_EOL);

    writeTraceLog([
        'time'    => date('c'),
        'server'  => $_SERVER,
    ]);

    exit(0);
}

// split comma separated domains
$domains = array_map('trim', explode(',', $rawDomains));

// get Basic auth creds (if any)
$serverAuth = getAuthFromServer();


// Loop through each domain and call the Handler
foreach ($domains as $domain) {
    if ($domain === '') {
        continue;
    }

    // Create a normalized request array for this domain
    $request = normalizeRequest($_REQUEST, $serverAuth);

    // Ensure the domain for this iteration is the fully qualified domain requested
    $request['domain'] = $domain;

    // Log detailed trace information
    Logger::logTrace([
        'config'  => $config,
        'request' => $request,
        'domain'  => $domain,
        'server'  => $_SERVER,
        'post'  => $_POST,
        'raw'    => file_get_contents('php://input')    ]);

    // Call the Handler with the current domain
    (new Handler($config, $request))->doRun();
}
