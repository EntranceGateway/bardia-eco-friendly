<?php
/**
 * POST /api/package-categories/create
 * Create a new package category (auth required)
 * Body: { "name": "Homestay", "slug": "homestay", "display_order": 1, "is_active": true }
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

$auth  = requireAuth();
$input = getJsonInput();

$name         = sanitize($input['name']  ?? '');
$slug         = sanitize($input['slug']  ?? '');
$displayOrder = (int)  ($input['display_order'] ?? 0);
$isActive     = isset($input['is_active']) ? (int)(bool)$input['is_active'] : 1;
$metaTitle       = sanitize($input['meta_title']         ?? '');
$metaDescription = sanitize($input['meta_description']   ?? '');
$metaKeywords    = sanitize($input['meta_keywords']      ?? '');
$metaImage       = sanitize($input['meta_image']         ?? '');
$canonicalUrl    = sanitize($input['canonical_url']      ?? '');
$robots          = sanitize($input['robots']             ?? 'index,follow');

if (empty($name)) {
    jsonResponse(400, 'error', null, 'Category name is required.');
}
if (empty($slug)) {
    jsonResponse(400, 'error', null, 'Slug is required.');
}

// Ensure slug is URL-safe
$slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $slug));

// Check slug uniqueness
$dup = $conn->prepare("SELECT id FROM package_categories WHERE slug = ?");
$dup->bind_param('s', $slug);
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) {
    $dup->close();
    jsonResponse(409, 'error', null, 'A category with this slug already exists.');
}
$dup->close();

$stmt = $conn->prepare(
    "INSERT INTO package_categories (name, slug, display_order, is_active, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('ssiissssss', $name, $slug, $displayOrder, $isActive, $metaTitle, $metaDescription, $metaKeywords, $metaImage, $canonicalUrl, $robots);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => $newId], 'Package category created successfully.');
} else {
    logError('Package category create failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to create package category.');
}
