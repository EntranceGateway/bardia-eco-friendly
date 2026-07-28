<?php
/**
 * Entry Point - Routes API Requests
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/jwt.php';
require_once __DIR__ . '/../includes/auth.php';

// Set CORS headers
setCorsHeaders();

// Get request URI and clean it
// Support PATH_INFO for local XAMPP (e.g. /public/index.php/api/...)
if (!empty($_SERVER['PATH_INFO'])) {
    $route = rtrim($_SERVER['PATH_INFO'], '/');
} else {
    $requestUri = $_SERVER['REQUEST_URI'];
    $basePath = str_replace('/bardiya-eco-friendly/public', '', parse_url($requestUri, PHP_URL_PATH));
    $basePath = str_replace('/bardiya-eco-friendly', '', $basePath);
    $basePath = str_replace('/public/index.php', '', $basePath);
    $basePath = str_replace('/index.php', '', $basePath);
    $route = rtrim($basePath, '/');
}

// Route mapping
$apiDir = __DIR__ . '/../api';

$routes = [
    // Search engine files
    '/robots.txt'            => $apiDir . '/robots/get.php',
    '/sitemap.xml'           => $apiDir . '/sitemap/generate.php',

    // Auth
    '/api/auth/login'        => $apiDir . '/auth/login.php',
    '/api/auth/verify'       => $apiDir . '/auth/verify.php',

    // Homestays
    '/api/homestays/list'    => $apiDir . '/homestays/list.php',
    '/api/homestays/create'  => $apiDir . '/homestays/create.php',
    '/api/homestays/update'  => $apiDir . '/homestays/update.php',
    '/api/homestays/delete'  => $apiDir . '/homestays/delete.php',

    // Pages
    '/api/pages/list'        => $apiDir . '/pages/list.php',
    '/api/pages/create'      => $apiDir . '/pages/create.php',
    '/api/pages/update'      => $apiDir . '/pages/update.php',
    '/api/pages/delete'      => $apiDir . '/pages/delete.php',

    // Backend SEO
    '/api/page-seo/get'        => $apiDir . '/page-seo/get.php',
    '/api/page-seo/update'     => $apiDir . '/page-seo/update.php',

    // Activities / Wildlife Experiences
    '/api/activities/list'     => $apiDir . '/activities/list.php',
    '/api/activities/get'      => $apiDir . '/activities/get.php',
    '/api/activities/create'   => $apiDir . '/activities/create.php',
    '/api/activities/update'   => $apiDir . '/activities/update.php',
    '/api/activities/delete'   => $apiDir . '/activities/delete.php',

    '/api/seo-settings/get'    => $apiDir . '/seo-settings/get.php',
    '/api/seo-settings/update' => $apiDir . '/seo-settings/update.php',
    '/api/faqs/list'           => $apiDir . '/faqs/list.php',
    '/api/faqs/create'         => $apiDir . '/faqs/create.php',
    '/api/faqs/update'         => $apiDir . '/faqs/update.php',
    '/api/faqs/delete'         => $apiDir . '/faqs/delete.php',
    '/api/redirects/list'      => $apiDir . '/redirects/list.php',
    '/api/redirects/create'    => $apiDir . '/redirects/create.php',
    '/api/redirects/update'    => $apiDir . '/redirects/update.php',
    '/api/redirects/delete'    => $apiDir . '/redirects/delete.php',
    '/api/redirects/resolve'   => $apiDir . '/redirects/resolve.php',
    '/api/robots/get'          => $apiDir . '/robots/get.php',
    '/api/robots/update'       => $apiDir . '/robots/update.php',
    '/api/sitemap/generate'    => $apiDir . '/sitemap/generate.php',
    '/api/schemas/list'        => $apiDir . '/schemas/list.php',
    '/api/schemas/create'      => $apiDir . '/schemas/create.php',
    '/api/schemas/update'      => $apiDir . '/schemas/update.php',
    '/api/schemas/delete'      => $apiDir . '/schemas/delete.php',
    '/api/reviews/settings'    => $apiDir . '/reviews/settings.php',
    '/api/reviews/update-settings' => $apiDir . '/reviews/update-settings.php',
    '/api/breadcrumbs/generate' => $apiDir . '/breadcrumbs/generate.php',
    '/api/seo-validation/check' => $apiDir . '/seo-validation/check.php',

    // Package Categories
    '/api/package-categories/list'   => $apiDir . '/package-categories/list.php',
    '/api/package-categories/create' => $apiDir . '/package-categories/create.php',
    '/api/package-categories/update' => $apiDir . '/package-categories/update.php',
    '/api/package-categories/delete' => $apiDir . '/package-categories/delete.php',

    // Packages
    '/api/packages/list'          => $apiDir . '/packages/list.php',
    '/api/packages/create'        => $apiDir . '/packages/create.php',
    '/api/packages/update'        => $apiDir . '/packages/update.php',
    '/api/packages/delete'        => $apiDir . '/packages/delete.php',

    // Package Features
    '/api/package-features/list'  => $apiDir . '/package-features/list.php',
    '/api/package-features/save'  => $apiDir . '/package-features/save.php',

    // Gallery Categories
    '/api/gallery-categories/list'   => $apiDir . '/gallery-categories/list.php',
    '/api/gallery-categories/create' => $apiDir . '/gallery-categories/create.php',
    '/api/gallery-categories/update' => $apiDir . '/gallery-categories/update.php',
    '/api/gallery-categories/delete' => $apiDir . '/gallery-categories/delete.php',

    // Gallery Images
    '/api/gallery-images/list'    => $apiDir . '/gallery-images/list.php',
    '/api/gallery-images/upload'  => $apiDir . '/gallery-images/upload.php',
    '/api/gallery-images/update'  => $apiDir . '/gallery-images/update.php',
    '/api/gallery-images/delete'  => $apiDir . '/gallery-images/delete.php',

    // Contact Methods
    '/api/contact-methods/list'   => $apiDir . '/contact-methods/list.php',
    '/api/contact-methods/create' => $apiDir . '/contact-methods/create.php',
    '/api/contact-methods/update' => $apiDir . '/contact-methods/update.php',
    '/api/contact-methods/delete' => $apiDir . '/contact-methods/delete.php',

    // Contact Submissions
    '/api/contact-submissions/submit'        => $apiDir . '/contact-submissions/submit.php',
    '/api/contact-submissions/list'          => $apiDir . '/contact-submissions/list.php',
    '/api/contact-submissions/update-status' => $apiDir . '/contact-submissions/update-status.php',
    '/api/contact-submissions/delete'        => $apiDir . '/contact-submissions/delete.php',

    // Social Links
    '/api/social-links/list'   => $apiDir . '/social-links/list.php',
    '/api/social-links/create' => $apiDir . '/social-links/create.php',
    '/api/social-links/update' => $apiDir . '/social-links/update.php',
    '/api/social-links/delete' => $apiDir . '/social-links/delete.php',

    // Emails
    '/api/emails/send-reply'   => $apiDir . '/emails/send-reply.php',
    '/api/emails/history'      => $apiDir . '/emails/history.php',

    // Email Templates
    '/api/email-templates/list' => $apiDir . '/email-templates/list.php',
    '/api/email-templates/get'  => $apiDir . '/email-templates/get.php',

    // Blog Categories
    '/api/blog-categories/list'   => $apiDir . '/blog-categories/list.php',
    '/api/blog-categories/create' => $apiDir . '/blog-categories/create.php',
    '/api/blog-categories/update' => $apiDir . '/blog-categories/update.php',
    '/api/blog-categories/delete' => $apiDir . '/blog-categories/delete.php',

    // Blog Posts
    '/api/blog-posts/list'   => $apiDir . '/blog-posts/list.php',
    '/api/blog-posts/get'    => $apiDir . '/blog-posts/get.php',
    '/api/blog-posts/create' => $apiDir . '/blog-posts/create.php',
    '/api/blog-posts/update' => $apiDir . '/blog-posts/update.php',
    '/api/blog-posts/delete' => $apiDir . '/blog-posts/delete.php',
];

// Match route
if (isset($routes[$route])) {
    require_once $routes[$route];
} else {
    $sourcePath = '/' . ltrim($route, '/');
    $redirect = $conn->prepare("SELECT target_url, status_code FROM seo_redirects WHERE source_path = ? AND is_active = 1 LIMIT 1");
    if ($redirect) {
        $redirect->bind_param('s', $sourcePath);
        $redirect->execute();
        $redirectResult = $redirect->get_result();
        $redirectRow = $redirectResult->fetch_assoc();
        $redirect->close();

        if ($redirectRow) {
            $statusCode = (int) $redirectRow['status_code'];
            if (!in_array($statusCode, [301, 302, 307, 308], true)) {
                $statusCode = 301;
            }

            header('Location: ' . $redirectRow['target_url'], true, $statusCode);
            exit;
        }
    }

    jsonResponse(404, 'error', null, 'Endpoint not found.');
}
