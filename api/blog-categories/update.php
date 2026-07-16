<?php
/**
 * PUT /api/blog-categories/update
 * Update a blog category (auth required)
 * Body: { "id": 1, "name": "Wildlife", "slug": "wildlife", "description": "...", "display_order": 0, "is_active": true }
 */

if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

$auth  = requireAuth();
$input = getJsonInput();

$id           = (int)   ($input['id']            ?? 0);
$name         = sanitize($input['name']          ?? '');
$slug         = slugify($input['slug']           ?? $name);
$description  = sanitize($input['description']   ?? '');
$displayOrder = isset($input['display_order']) ? (int) $input['display_order'] : 0;
$isActive     = isset($input['is_active'])     ? (int)(bool) $input['is_active'] : 1;

if ($id <= 0)      { jsonResponse(400, 'error', null, 'Valid category id is required.'); }
if (empty($name))  { jsonResponse(400, 'error', null, 'Category name is required.'); }

// Verify category exists
$check = $conn->prepare("SELECT id FROM blog_categories WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) { $check->close(); jsonResponse(404, 'error', null, 'Blog category not found.'); }
$check->close();

// Check slug uniqueness (exclude current)
$slugCheck = $conn->prepare("SELECT id FROM blog_categories WHERE slug = ? AND id != ?");
$slugCheck->bind_param('si', $slug, $id);
$slugCheck->execute();
$slugCheck->store_result();
if ($slugCheck->num_rows > 0) {
    $slugCheck->close();
    jsonResponse(409, 'error', null, 'Another blog category with this slug already exists.');
}
$slugCheck->close();

$stmt = $conn->prepare(
    "UPDATE blog_categories SET name=?, slug=?, description=?, display_order=?, is_active=? WHERE id=?"
);
$stmt->bind_param('sssiii', $name, $slug, $description, $displayOrder, $isActive, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id], 'Blog category updated successfully.');
} else {
    logError('Blog category update failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to update blog category.');
}
