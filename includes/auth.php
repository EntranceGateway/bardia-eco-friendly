<?php
/**
 * Authentication Middleware
 * Validates JWT token on protected routes
 */

/**
 * Require authentication - call this at the top of protected endpoints
 * Returns the decoded token payload or a session-based user object
 */
function requireAuth(): object
{
    // 1. Try JWT token first
    $token = getBearerToken();
    if ($token) {
        $decoded = decodeToken($token);
        if ($decoded) {
            return $decoded;
        }
        jsonResponse(401, 'error', null, 'Invalid or expired token.');
    }

    // 2. Fallback to PHP Session (for CMS usage)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!empty($_SESSION['cms_user_id'])) {
        // Cookie auth is sent by the browser automatically, so writes must prove they came from the CMS
        if (getRequestMethod() !== 'GET' && !csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            jsonResponse(403, 'error', null, 'Invalid or missing CSRF token.');
        }
        return (object)[
            'user_id' => (int)$_SESSION['cms_user_id'],
            'role'    => 'admin' // CMS users are typically admins
        ];
    }

    jsonResponse(401, 'error', null, 'Access denied. No authentication provided.');
    exit; // Should be unreachable due to jsonResponse()
}

function requireRole(array $allowedRoles): object
{
    $auth = requireAuth();
    $role = $auth->role ?? '';

    if (!in_array($role, $allowedRoles, true)) {
        jsonResponse(403, 'error', null, 'You do not have permission to perform this action.');
    }

    return $auth;
}

// Used by admin-only endpoints (activities, page SEO)
function requireAdmin(): object
{
    return requireRole(['admin']);
}
