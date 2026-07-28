<?php
/**
 * Database Configuration
 * Loads .env variables, establishes MySQL connection, and installs
 * a global error/exception handler so raw PHP errors never reach CMS users.
 */

// ── 1. Set global timezone and suppress error output ─────────────────────────
date_default_timezone_set('Asia/Kathmandu');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);                         // still log everything
ini_set('log_errors', '1');                     // write to Apache/PHP error log

// ── 2. Global exception/error handler — friendly CMS page ────────────────────
// Guard: if config.php is included via two different path strings PHP may
// execute it twice despite require_once — the function_exists check prevents
// a fatal "Cannot redeclare" error in that case.
if (!function_exists('cms_friendly_error')) {

    function cms_friendly_error(string $title, string $detail = '', string $hint = ''): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            $detailHtml = $detail
                ? '<p style="margin-top:6px;font-size:.88rem;color:#b91c1c;font-weight:500;">' . htmlspecialchars($detail, ENT_QUOTES) . '</p>'
                : '';
            $hintHtml = $hint
                ? '<div style="margin-top:16px;padding:12px 16px;background:#f0f9f1;border:1px solid #a7d7ab;'
                  . 'border-radius:8px;font-size:.82rem;color:#1b5e20;text-align:left;line-height:1.6;word-break:break-word;">'
                  . nl2br(htmlspecialchars($hint, ENT_QUOTES)) . '</div>'
                : '';
            echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$title} — Bardiya Eco CMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f0f4f0;min-height:100vh;
         display:flex;align-items:center;justify-content:center;padding:24px}
    .box{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.1);
         padding:40px 36px;max-width:520px;width:100%;text-align:center}
    .icon{font-size:2.8rem;margin-bottom:12px}
    h1{font-size:1.25rem;color:#1b5e20;margin-bottom:8px}
    p{font-size:.9rem;color:#6b7280;line-height:1.6}
    a{display:inline-block;margin-top:20px;padding:10px 20px;background:#2e7d32;
      color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.88rem}
    a:hover{background:#1b5e20}
  </style>
</head>
<body>
  <div class="box">
    <div class="icon">⚠️</div>
    <h1>{$title}</h1>
    {$detailHtml}
    {$hintHtml}
    <a href="javascript:history.back()">← Go Back</a>
  </div>
</body>
</html>
HTML;
        }
        exit;
    }

    set_exception_handler(function (Throwable $e): void {
        error_log('[CMS Exception] ' . get_class($e) . ': ' . $e->getMessage()
                  . ' in ' . $e->getFile() . ':' . $e->getLine());
        cms_friendly_error(
            'Application Exception',
            $e->getMessage(),
            "Error in " . basename($e->getFile()) . " on line " . $e->getLine()
        );
    });

    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
        if ($errno & (E_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        error_log("[PHP {$errno}] {$errstr} in {$errfile}:{$errline}");
        return true;
    });

    register_shutdown_function(function (): void {
        $e = error_get_last();
        if ($e && ($e['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
            error_log('[CMS Fatal] ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line']);
            cms_friendly_error(
                'Fatal Server Error',
                $e['message'],
                "Error in " . basename($e['file']) . " on line " . $e['line']
            );
        }
    });

} // end if (!function_exists('cms_friendly_error'))

// ── 3. Load environment ───────────────────────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
} else {
    error_log('[CMS Env] .env file not found at project root.');
}

// ── 4. Database connection ────────────────────────────────────────────────────
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'bardiya_eco_friendly';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';

mysqli_report(MYSQLI_REPORT_OFF);   // we handle errors ourselves

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    error_log('[CMS DB] Connection failed: ' . $conn->connect_error);
    $hasEnv = file_exists(__DIR__ . '/../.env');
    $envNotice = $hasEnv 
        ? "Check that credentials in your .env file are correct." 
        : "⚠️ .env file is missing on the server! Please create a .env file in your server's root directory with DB_HOST, DB_NAME, DB_USER, DB_PASS, and BASE_URL.";
    cms_friendly_error(
        'Database unavailable',
        '',
        "🔴 Cannot connect to MySQL.\n"
        . "Error: " . $conn->connect_error . "\n\n"
        . $envNotice
    );
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+05:45'");


// ── 6. Base URL configuration ─────────────────────────────────────────────────
// Use BASE_URL from .env for generating asset URLs
// This should be the full URL without trailing slash
// Example: https://api.bardiaecofriendlyhomestay.com
if (!isset($baseUrl)) {
    $baseUrl = $_ENV['BASE_URL'] ?? '';
    
    // Auto-detect if not set in .env (fallback)
    if (empty($baseUrl)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;
    }
    
    // Ensure no trailing slash
    $baseUrl = rtrim($baseUrl, '/');
}


// ── 7. Auto-reconnect helper ──────────────────────────────────────────────────
if (!function_exists('db_query')) {
    /**
     * Execute a raw SQL query with automatic reconnect on "MySQL server has gone away".
     * Throws RuntimeException on failure — catch it in the calling page.
     */
    function db_query(mysqli $conn, string $sql): mysqli_result|bool
    {
        $result = $conn->query($sql);

        // errno 2006 = MySQL server has gone away — try once to reconnect
        if ($result === false && $conn->errno === 2006) {
            $conn->close();
            global $db_host, $db_user, $db_pass, $db_name;
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if ($conn->connect_error) {
                throw new RuntimeException('Database reconnect failed: ' . $conn->connect_error);
            }
            $conn->set_charset('utf8mb4');
            $result = $conn->query($sql);
        }

        if ($result === false) {
            throw new RuntimeException('Query failed (' . $conn->errno . '): ' . $conn->error);
        }

        return $result;
    }
}
