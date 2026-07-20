<?php
/**
 * PUT /api/package-categories/update
 * Update a package category (auth required)
 * Body: { "id": 1, "name": "Homestay", "slug": "homestay", "display_order": 1, "is_active": true }
 */

if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

$auth  = requireAuth();
$input = getJsonInput();

$id           = (int)   ($input['id']            ?? 0);
$name         = sanitize($input['name']           ?? '');
$slug         = sanitize($input['slug']           ?? '');
$displayOrder = (int)   ($input['display_order']  ?? 0);
$isActive     = isset($input['is_active']) ? (int)(bool)$input['is_active'] : 1;
$metaTitle       = sanitize($input['meta_title']         ?? '');
$metaDescription = sanitize($input['meta_description']   ?? '');
$metaKeywords    = sanitize($input['meta_keywords']      ?? '');
$metaImage       = sanitize($input['meta_image']         ?? '');
$canonicalUrl    = sanitize($input['canonical_url']      ?? '');
$robots          = sanitize($input['robots']             ?? 'index,follow');

if ($id <= 0)     { jsonResponse(400, 'error', null, 'Valid id is required.'); }
if (empty($name)) { jsonResponse(400, 'error', null, 'Name is required.'); }
if (empty($slug)) { jsonResponse(400, 'error', null, 'Slug is required.'); }

$slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $slug));

// Verify exists
$check = $conn->prepare("SELECT id FROM package_categories WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    jsonResponse(404, 'error', null, 'Package category not found.');
}
$check->close();

// Check slug uniqueness (excluding self)
$dup = $conn->prepare("SELECT id FROM package_categories WHERE slug = ? AND id != ?");
$dup->bind_param('si', $slug, $id);
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) {
    $dup->close();
    jsonResponse(409, 'error', null, 'A category with this slug already exists.');
}
$dup->close();

$stmt = $conn->prepare(
    "UPDATE package_categories SET name=?, slug=?, display_order=?, is_active=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?, canonical_url=?, robots=? WHERE id=?"
);
$stmt->bind_param('ssiissssssi', $name, $slug, $displayOrder, $isActive, $metaTitle, $metaDescription, $metaKeywords, $metaImage, $canonicalUrl, $robots, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id], 'Package category updated successfully.');
} else {
    logError('Package category update failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to update package category.');
}
