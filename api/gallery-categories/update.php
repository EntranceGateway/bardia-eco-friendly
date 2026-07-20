<?php
/**
 * PUT /api/gallery-categories/update
 * Update a gallery category (auth required)
 */

if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

$auth  = requireAuth();
$input = getJsonInput();

$id           = (int)   ($input['id']           ?? 0);
$name         = sanitize($input['name']         ?? '');
$slug         = slugify($input['slug']         ?? $name);
$displayOrder = (int)   ($input['display_order'] ?? 0);
$isActive     = isset($input['is_active']) ? (int)(bool)$input['is_active'] : 1;
$metaTitle       = sanitize($input['meta_title']         ?? '');
$metaDescription = sanitize($input['meta_description']   ?? '');
$metaKeywords    = sanitize($input['meta_keywords']      ?? '');
$metaImage       = sanitize($input['meta_image']         ?? '');
$canonicalUrl    = sanitize($input['canonical_url']      ?? '');
$robots          = sanitize($input['robots']             ?? 'index,follow');

if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Valid category id is required.');
}
if (empty($name)) {
    jsonResponse(400, 'error', null, 'Category name is required.');
}

// Check exists
$check = $conn->prepare("SELECT id FROM gallery_categories WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    jsonResponse(404, 'error', null, 'Gallery category not found.');
}
$check->close();

// Check slug uniqueness (excluding current)
$dup = $conn->prepare("SELECT id FROM gallery_categories WHERE slug = ? AND id != ?");
$dup->bind_param('si', $slug, $id);
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) {
    $dup->close();
    jsonResponse(409, 'error', null, 'Another category with this slug already exists.');
}
$dup->close();

$stmt = $conn->prepare(
    "UPDATE gallery_categories SET name=?, slug=?, display_order=?, is_active=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?, canonical_url=?, robots=? WHERE id=?"
);
$stmt->bind_param('ssiissssssi', $name, $slug, $displayOrder, $isActive, $metaTitle, $metaDescription, $metaKeywords, $metaImage, $canonicalUrl, $robots, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id], 'Gallery category updated successfully.');
} else {
    logError('Gallery category update failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to update gallery category.');
}
