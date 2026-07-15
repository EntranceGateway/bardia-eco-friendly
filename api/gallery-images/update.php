<?php
/**
 * PUT /api/gallery-images/update
 * Update gallery image metadata (auth required)
 * Does NOT replace the image file; for that, delete + re-upload.
 */

if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

$auth  = requireAuth();
$input = getJsonInput();

$id           = (int)   ($input['id']            ?? 0);
$categoryId   = (int)   ($input['category_id']   ?? 0);
$altText      = sanitize($input['alt_text']       ?? '');
$titleText    = sanitize($input['title_text']     ?? '');
$caption      = sanitize($input['caption']        ?? '');
$displayOrder = (int)   ($input['display_order']  ?? 0);
$isActive     = isset($input['is_active']) ? (int)(bool)$input['is_active'] : 1;

if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Valid image id is required.');
}
if ($categoryId <= 0) {
    jsonResponse(400, 'error', null, 'Valid category_id is required.');
}

// Verify image exists
$check = $conn->prepare("SELECT id FROM gallery_images WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    jsonResponse(404, 'error', null, 'Gallery image not found.');
}
$check->close();

// Verify category exists
$catCheck = $conn->prepare("SELECT id FROM gallery_categories WHERE id = ?");
$catCheck->bind_param('i', $categoryId);
$catCheck->execute();
$catCheck->store_result();
if ($catCheck->num_rows === 0) {
    $catCheck->close();
    jsonResponse(404, 'error', null, 'Gallery category not found.');
}
$catCheck->close();

$stmt = $conn->prepare(
    "UPDATE gallery_images SET category_id=?, alt_text=?, title_text=?, caption=?, display_order=?, is_active=? WHERE id=?"
);
$stmt->bind_param('isssiii', $categoryId, $altText, $titleText, $caption, $displayOrder, $isActive, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id], 'Gallery image updated successfully.');
} else {
    logError('Gallery image update failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to update gallery image.');
}
