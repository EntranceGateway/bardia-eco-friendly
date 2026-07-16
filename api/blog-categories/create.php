<?php
/**
 * POST /api/blog-categories/create
 * Create a new blog category (auth required)
 * Body: { "name": "Wildlife", "slug": "wildlife", "description": "...", "display_order": 0, "is_active": true }
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

$auth  = requireAuth();
$input = getJsonInput();

$name         = sanitize($input['name']        ?? '');
$slug         = slugify($input['slug']         ?? $name);
$description  = sanitize($input['description'] ?? '');
$displayOrder = isset($input['display_order']) ? (int) $input['display_order'] : 0;
$isActive     = isset($input['is_active'])     ? (int)(bool) $input['is_active'] : 1;

if (empty($name)) {
    jsonResponse(400, 'error', null, 'Category name is required.');
}

// Check for duplicate slug
$check = $conn->prepare("SELECT id FROM blog_categories WHERE slug = ?");
$check->bind_param('s', $slug);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $check->close();
    jsonResponse(409, 'error', null, 'A blog category with this slug already exists.');
}
$check->close();

$stmt = $conn->prepare(
    "INSERT INTO blog_categories (name, slug, description, display_order, is_active)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssii', $name, $slug, $description, $displayOrder, $isActive);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => $newId], 'Blog category created successfully.');
} else {
    logError('Blog category create failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to create blog category.');
}
