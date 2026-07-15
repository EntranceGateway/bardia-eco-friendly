<?php
/**
 * Helper Functions
 */

/**
 * Send JSON response and exit
 */
function jsonResponse(int $statusCode, string $status, $data = null, string $message = ''): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow', true);
    header('Cache-Control: no-store, max-age=0', true);

    $response = ['status' => $status];

    if ($message) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

/**
 * Sanitize input string
 */
function sanitize(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function slugify(string $input): string
{
    $slug = strtolower(trim($input));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'page';
}

function frontend_url(string $path = ''): string
{
    $frontendUrl = rtrim($_ENV['FRONTEND_URL'] ?? 'https://www.bardiaecofriendlyhomestay.com', '/');
    if ($path === '') {
        return $frontendUrl;
    }

    return $frontendUrl . '/' . ltrim($path, '/');
}

/**
 * Get JSON input from request body
 */
function getJsonInput(): array
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(400, 'error', null, 'Invalid JSON input');
    }

    return $data ?? [];
}

/**
 * Set CORS headers for React frontend
 */
function setCorsHeaders(): void
{
    $allowedOrigin = $_ENV['FRONTEND_URL'] ?? 'https://www.bardiaecofriendlyhomestay.com';
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Vary: Origin');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Get the request method
 */
function getRequestMethod(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD']);
}

/**
 * Log errors to storage/logs
 */
function logError(string $message): void
{
    $logFile = __DIR__ . '/../storage/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Generate asset URL with proper base URL
 * Converts relative paths to full URLs using BASE_URL from .env
 * 
 * @param string $path Relative path (e.g., '/storage/gallery/image.jpg')
 * @return string Full URL with base URL
 */
function asset_url(string $path): string
{
    global $baseUrl;
    
    // Remove any existing base path or domain from the input
    $path = preg_replace('#^https?://[^/]+#', '', $path);
    $path = preg_replace('#^/[^/]+/storage/#', '/storage/', $path);
    
    // Ensure path starts with /
    if (empty($path) || $path[0] !== '/') {
        $path = '/' . $path;
    }
    
    // Combine base URL with the asset path
    return $baseUrl . $path;
}
