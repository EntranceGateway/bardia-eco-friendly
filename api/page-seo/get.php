<?php
/**
 * GET /api/page-seo/get?slug=my-page
 * Unified SEO endpoint for the frontend.
 * Returns meta tags, open graph data, hreflang alternates, and JSON-LD schemas
 * for a given page or blog post slug.
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    jsonResponse(400, 'error', null, 'Slug parameter is required.');
}

// 1. Fetch global SEO settings for fallbacks
$settings = [];
$settingsRes = $conn->query("SELECT setting_key, setting_value FROM seo_settings");
if ($settingsRes) {
    while ($row = $settingsRes->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$defaultTitle = $settings['default_title'] ?? 'Bardia Eco-Friendly Homestay';
$defaultDesc = $settings['default_description'] ?? '';
$defaultKeywords = $settings['default_keywords'] ?? '';
$defaultImage = $settings['default_image'] ?? '/logo.png';

$pageData = null;
$type = 'page';

// 2. Look in pages table
$stmt = $conn->prepare(
    "SELECT id, title, slug, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, language_code, translation_group, hreflang_url, status
     FROM pages WHERE slug = ? AND status = 'published' LIMIT 1"
);
$stmt->bind_param('s', $slug);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pageData = $row;
}
$stmt->close();

// 3. If not found, look in blog_posts table
if (!$pageData) {
    $stmt = $conn->prepare(
        "SELECT id, title, slug, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, status
         FROM blog_posts WHERE slug = ? AND status = 'published' AND is_active = 1 LIMIT 1"
    );
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pageData = $row;
        $type = 'blog_post';
    }
    $stmt->close();
}

// 3.5 Look in package_categories
if (!$pageData) {
    $stmt = $conn->prepare("SELECT id, name AS title, slug, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots FROM package_categories WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pageData = $row;
        $type = 'package_category';
    }
    $stmt->close();
}

// 3.6 Look in packages
if (!$pageData) {
    $stmt = $conn->prepare("SELECT id, name AS title, slug, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots FROM packages WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pageData = $row;
        $type = 'package';
    }
    $stmt->close();
}

// 3.7 Look in gallery_categories
if (!$pageData) {
    $stmt = $conn->prepare("SELECT id, name AS title, slug, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots FROM gallery_categories WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pageData = $row;
        $type = 'gallery_category';
    }
    $stmt->close();
}

// 3.8 Look in gallery_images
if (!$pageData) {
    $stmt = $conn->prepare("SELECT id, title_text AS title, slug, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots FROM gallery_images WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pageData = $row;
        $type = 'gallery_image';
    }
    $stmt->close();
}

if (!$pageData) {
    jsonResponse(404, 'error', null, 'Page not found.');
}

// 4. Fallback resolution
$metaTitle = !empty($pageData['meta_title']) ? $pageData['meta_title'] : ($pageData['title'] . ' | ' . $defaultTitle);
$metaDescription = !empty($pageData['meta_description']) ? $pageData['meta_description'] : $defaultDesc;
$metaKeywords = !empty($pageData['meta_keywords']) ? $pageData['meta_keywords'] : $defaultKeywords;
$metaImage = !empty($pageData['meta_image']) ? $pageData['meta_image'] : $defaultImage;

// Ensure full URL for image
$metaImageUrl = $metaImage ? asset_url($metaImage) : '';

$urlPath = '/' . ltrim($slug, '/');
if ($type === 'blog_post') {
    $urlPath = '/blog/' . $slug;
} elseif ($type === 'package') {
    $urlPath = '/package/' . $slug;
} elseif ($type === 'package_category') {
    $urlPath = '/packages/' . $slug;
} elseif ($type === 'gallery_category') {
    $urlPath = '/gallery/' . $slug;
} elseif ($type === 'gallery_image') {
    $urlPath = '/gallery/image/' . $slug;
}
if ($slug === 'home') $urlPath = '/';

$canonicalUrl = !empty($pageData['canonical_url']) ? $pageData['canonical_url'] : frontend_url($urlPath);
$robots = !empty($pageData['robots']) ? $pageData['robots'] : 'index,follow';
$languageCode = !empty($pageData['language_code']) ? $pageData['language_code'] : 'en';

// 5. Fetch hreflang alternates (only applies to pages with translation groups)
$hreflang = [];
if ($type === 'page' && !empty($pageData['translation_group'])) {
    $altStmt = $conn->prepare(
        "SELECT language_code, slug, hreflang_url 
         FROM pages 
         WHERE translation_group = ? AND status = 'published'"
    );
    $altStmt->bind_param('s', $pageData['translation_group']);
    $altStmt->execute();
    $altResult = $altStmt->get_result();
    while ($altRow = $altResult->fetch_assoc()) {
        $href = !empty($altRow['hreflang_url']) ? $altRow['hreflang_url'] : frontend_url('/' . ltrim($altRow['slug'], '/'));
        $hreflang[] = [
            'lang' => $altRow['language_code'],
            'url' => $href
        ];
    }
    $altStmt->close();
}

// 6. Fetch schemas for this page slug
$schemas = [];
$schemaStmt = $conn->prepare("SELECT schema_json FROM seo_schemas WHERE page_slug = ? AND is_active = 1");
$schemaStmt->bind_param('s', $slug);
$schemaStmt->execute();
$schemaRes = $schemaStmt->get_result();
while ($sRow = $schemaRes->fetch_assoc()) {
    $decoded = json_decode($sRow['schema_json'], true);
    if ($decoded) {
        $schemas[] = $decoded;
    }
}
$schemaStmt->close();

$response = [
    'meta_title' => $metaTitle,
    'meta_description' => $metaDescription,
    'meta_keywords' => $metaKeywords,
    'meta_image' => $metaImageUrl,
    'canonical_url' => $canonicalUrl,
    'robots' => $robots,
    'language_code' => $languageCode,
    'hreflang' => $hreflang,
    'schemas' => $schemas,
    'og' => [
        'title' => $metaTitle,
        'description' => $metaDescription,
        'image' => $metaImageUrl,
        'url' => $canonicalUrl,
        'type' => ($type === 'blog_post') ? 'article' : 'website'
    ]
];

jsonResponse(200, 'success', $response);
