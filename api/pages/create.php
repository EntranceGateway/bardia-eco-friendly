<?php
/**
 * POST /api/pages/create
 * Create a new CMS page (Protected)
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

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

// Check for duplicate slug
$check = $conn->prepare("SELECT id FROM pages WHERE slug = ?");
$check->bind_param('s', $slug);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    jsonResponse(409, 'error', null, 'A page with this slug already exists.');
}
$check->close();

$allowedRobots = ['index,follow', 'noindex,follow', 'noindex,nofollow'];
if (!in_array($robots, $allowedRobots, true)) {
    jsonResponse(400, 'error', null, 'Invalid robots value.');
}
if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $languageCode)) {
    jsonResponse(400, 'error', null, 'Invalid language_code. Use values like en or ne.');
}

$stmt = $conn->prepare("INSERT INTO pages (title, slug, content, meta_title, meta_description, meta_image, canonical_url, robots, language_code, translation_group, hreflang_url, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->bind_param('ssssssssssss', $title, $slug, $content, $metaTitle, $metaDescription, $metaImage, $canonicalUrl, $robots, $languageCode, $translationGroup, $hreflangUrl, $status);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => $newId], 'Page created successfully.');
} else {
    logError('Page create failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to create page.');
}
