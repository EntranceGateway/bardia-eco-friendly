<?php
/**
 * DELETE /api/blog-posts/delete
 * Delete a blog post (auth required). Also removes featured image from disk.
 */

if (getRequestMethod() !== 'DELETE') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use DELETE.');
}

$auth  = requireAuth();
$input = getJsonInput();

$id = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Valid post id is required.');
}

// Fetch post to get image path
$check = $conn->prepare("SELECT id, featured_image FROM blog_posts WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$post = $check->get_result()->fetch_assoc();
$check->close();

if (!$post) {
    jsonResponse(404, 'error', null, 'Blog post not found.');
}

$stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();

    // Delete featured image from disk
    if ($post['featured_image']) {
        $filename = basename($post['featured_image']);
        $path = __DIR__ . '/../../storage/blog/' . $filename;
        if (file_exists($path)) @unlink($path);
    }

    jsonResponse(200, 'success', null, 'Blog post deleted successfully.');
} else {
    logError('Blog post delete failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to delete blog post.');
}
