<?php
/**
 * DELETE /api/blog-categories/delete
 * Delete a blog category (auth required). CASCADE removes linked blog posts.
 */

if (getRequestMethod() !== 'DELETE') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use DELETE.');
}

$auth  = requireAuth();
$input = getJsonInput();

$id = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Valid category id is required.');
}

// Verify category exists
$check = $conn->prepare("SELECT id FROM blog_categories WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    jsonResponse(404, 'error', null, 'Blog category not found.');
}
$check->close();

// Delete associated blog post images from disk before cascade delete
$imgResult = $conn->prepare("SELECT featured_image FROM blog_posts WHERE category_id = ? AND featured_image IS NOT NULL AND featured_image != ''");
$imgResult->bind_param('i', $id);
$imgResult->execute();
$imgRes = $imgResult->get_result();
while ($row = $imgRes->fetch_assoc()) {
    $filename = basename($row['featured_image']);
    $path = __DIR__ . '/../../storage/blog/' . $filename;
    if (file_exists($path)) @unlink($path);
}
$imgResult->close();

$stmt = $conn->prepare("DELETE FROM blog_categories WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', null, 'Blog category and its posts deleted successfully.');
} else {
    logError('Blog category delete failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to delete blog category.');
}
