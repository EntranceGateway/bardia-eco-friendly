<?php
/**
 * POST /api/gallery-categories/create
 * Create a gallery category (auth required)
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

$auth  = requireAuth();
$input = getJsonInput();

$name         = sanitize($input['name']          ?? '');
$slug         = slugify($input['slug']          ?? $name);
$displayOrder = (int) ($input['display_order']   ?? 0);
$isActive     = isset($input['is_active']) ? (int)(bool)$input['is_active'] : 1;

if (empty($name)) {
    jsonResponse(400, 'error', null, 'Category name is required.');
}

// Check slug uniqueness
$dup = $conn->prepare("SELECT id FROM gallery_categories WHERE slug = ?");
$dup->bind_param('s', $slug);
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) {
    $dup->close();
    jsonResponse(409, 'error', null, 'A category with this slug already exists.');
}
$dup->close();

$stmt = $conn->prepare(
    "INSERT INTO gallery_categories (name, slug, display_order, is_active) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('ssii', $name, $slug, $displayOrder, $isActive);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => $newId], 'Gallery category created successfully.');
} else {
    logError('Gallery category create failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to create gallery category.');
}
