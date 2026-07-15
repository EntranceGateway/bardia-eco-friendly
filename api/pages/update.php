<?php
/**
 * PUT /api/pages/update
 * Update a CMS page (Protected)
 */

if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

$id = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Valid page ID is required.');
}

// Check if page exists
$check = $conn->prepare("SELECT id, slug FROM pages WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$existingPage = $check->get_result()->fetch_assoc();
if (!$existingPage) {
    $check->close();
    jsonResponse(404, 'error', null, 'Page not found.');
}
$check->close();

$title = sanitize($input['title'] ?? '');
$slug = slugify($input['slug'] ?? $title);
$content = $input['content'] ?? '';
$metaTitle = sanitize($input['meta_title'] ?? '');
$metaDescription = sanitize($input['meta_description'] ?? '');
$metaImage = sanitize($input['meta_image'] ?? '');
$canonicalUrl = sanitize($input['canonical_url'] ?? '');
$robots = sanitize($input['robots'] ?? 'index,follow');
$languageCode = strtolower(sanitize($input['language_code'] ?? 'en'));
$translationGroup = sanitize($input['translation_group'] ?? $slug);
$hreflangUrl = sanitize($input['hreflang_url'] ?? '');
$status = sanitize($input['status'] ?? 'draft');

if (empty($title) || empty($slug)) {
    jsonResponse(400, 'error', null, 'Title and slug are required.');
}

// Check for duplicate slug (excluding current page)
$slugCheck = $conn->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
$slugCheck->bind_param('si', $slug, $id);
$slugCheck->execute();
if ($slugCheck->get_result()->num_rows > 0) {
    $slugCheck->close();
    jsonResponse(409, 'error', null, 'A page with this slug already exists.');
}
$slugCheck->close();

$allowedRobots = ['index,follow', 'noindex,follow', 'noindex,nofollow'];
if (!in_array($robots, $allowedRobots, true)) {
    jsonResponse(400, 'error', null, 'Invalid robots value.');
}
if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $languageCode)) {
    jsonResponse(400, 'error', null, 'Invalid language_code. Use values like en or ne.');
}

$stmt = $conn->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, meta_title = ?, meta_description = ?, meta_image = ?, canonical_url = ?, robots = ?, language_code = ?, translation_group = ?, hreflang_url = ?, status = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param('ssssssssssssi', $title, $slug, $content, $metaTitle, $metaDescription, $metaImage, $canonicalUrl, $robots, $languageCode, $translationGroup, $hreflangUrl, $status, $id);

if ($stmt->execute()) {
    if ($existingPage['slug'] !== $slug) {
        $sourcePath = '/' . trim($existingPage['slug'], '/');
        $targetUrl = frontend_url('/' . $slug);
        $statusCode = 301;
        $isActive = 1;
        $redirect = $conn->prepare(
            "INSERT INTO seo_redirects (source_path, target_url, status_code, is_active) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE target_url = VALUES(target_url), status_code = VALUES(status_code), is_active = VALUES(is_active)"
        );
        if ($redirect) {
            $redirect->bind_param('ssii', $sourcePath, $targetUrl, $statusCode, $isActive);
            $redirect->execute();
            $redirect->close();
        }
    }
    $stmt->close();
    jsonResponse(200, 'success', null, 'Page updated successfully.');
} else {
    logError('Page update failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to update page.');
}
