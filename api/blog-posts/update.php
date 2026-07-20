<?php
/**
 * POST /api/blog-posts/update
 * Update an existing blog post (auth required, multipart/form-data)
 * Uses POST (not PUT) for file upload compatibility
 * Fields: id (required), + same fields as create
 * File:   featured_image (optional — replaces existing image)
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

$auth = requireAuth();

$id               = (int)    ($_POST['id']                ?? 0);
$categoryId       = (int)    ($_POST['category_id']       ?? 0);
$title            = trim(    $_POST['title']              ?? '');
$slug             = slugify( $_POST['slug']               ?? $title);
$excerpt          = trim(    $_POST['excerpt']            ?? '');
$content          =          $_POST['content']            ?? '';
$authorName       = sanitize($_POST['author_name']        ?? '');
$featuredImageAlt = sanitize($_POST['featured_image_alt'] ?? '');
$metaTitle        = sanitize($_POST['meta_title']         ?? '');
$metaDescription  = sanitize($_POST['meta_description']   ?? '');
$metaKeywords     = sanitize($_POST['meta_keywords']      ?? '');
$metaImage        = sanitize($_POST['meta_image']         ?? '');
$canonicalUrl     = sanitize($_POST['canonical_url']      ?? '');
$robots           = sanitize($_POST['robots']             ?? 'index,follow');
$isFeatured       = isset($_POST['is_featured'])   ? (int)(bool) $_POST['is_featured']   : 0;
$status           =          $_POST['status']             ?? 'draft';
$displayOrder     = isset($_POST['display_order']) ? (int) $_POST['display_order']       : 0;
$isActive         = isset($_POST['is_active'])     ? (int)(bool) $_POST['is_active']     : 1;

// Validation
if ($id <= 0)          { jsonResponse(400, 'error', null, 'Valid post id is required.'); }
if ($categoryId <= 0)  { jsonResponse(400, 'error', null, 'Valid category_id is required.'); }
if (empty($title))     { jsonResponse(400, 'error', null, 'Post title is required.'); }

$allowedStatuses = ['draft', 'published', 'archived'];
if (!in_array($status, $allowedStatuses, true)) {
    jsonResponse(400, 'error', null, 'Invalid status. Allowed: draft, published, archived.');
}

$allowedRobots = ['index,follow', 'noindex,follow', 'noindex,nofollow'];
if (!in_array($robots, $allowedRobots, true)) {
    jsonResponse(400, 'error', null, 'Invalid robots value.');
}

// Verify post exists + get current data
$check = $conn->prepare("SELECT id, featured_image, status, published_at FROM blog_posts WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();
if (!$existing) { jsonResponse(404, 'error', null, 'Blog post not found.'); }

// Verify category exists
$catCheck = $conn->prepare("SELECT id FROM blog_categories WHERE id = ?");
$catCheck->bind_param('i', $categoryId);
$catCheck->execute();
$catCheck->store_result();
if ($catCheck->num_rows === 0) { $catCheck->close(); jsonResponse(404, 'error', null, 'Blog category not found.'); }
$catCheck->close();

// Check slug uniqueness (exclude current)
$slugCheck = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
$slugCheck->bind_param('si', $slug, $id);
$slugCheck->execute();
$slugCheck->store_result();
if ($slugCheck->num_rows > 0) { $slugCheck->close(); jsonResponse(409, 'error', null, 'Another blog post with this slug already exists.'); }
$slugCheck->close();

$conn->begin_transaction();

try {
    // Handle featured image replacement
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['featured_image'];
        $maxBytes = 5 * 1024 * 1024;
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];

        if ($file['size'] > $maxBytes) {
            jsonResponse(400, 'error', null, 'Featured image is too large. Maximum size is 5MB.');
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed, true)) {
            jsonResponse(400, 'error', null, 'Invalid image type. Allowed: jpg, jpeg, png, webp.');
        }

        $storageDir = __DIR__ . '/../../storage/blog/';
        if (!is_dir($storageDir)) { mkdir($storageDir, 0755, true); }

        $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $filename     = time() . '_' . $originalName;
        $destination  = $storageDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to save featured image.');
        }

        $newImageUrl = '/storage/blog/' . $filename;

        // Update image in DB
        $imgStmt = $conn->prepare("UPDATE blog_posts SET featured_image = ?, featured_image_alt = ? WHERE id = ?");
        $imgStmt->bind_param('ssi', $newImageUrl, $featuredImageAlt, $id);
        if (!$imgStmt->execute()) { throw new RuntimeException('Image update failed: ' . $imgStmt->error); }
        $imgStmt->close();

        // Delete old image file
        if ($existing['featured_image']) {
            $oldPath = __DIR__ . '/../../' . ltrim($existing['featured_image'], '/');
            if (file_exists($oldPath)) @unlink($oldPath);
        }
    } else {
        // Just update the alt text even if no new image
        $altStmt = $conn->prepare("UPDATE blog_posts SET featured_image_alt = ? WHERE id = ?");
        $altStmt->bind_param('si', $featuredImageAlt, $id);
        $altStmt->execute();
        $altStmt->close();
    }

    // Auto-set published_at if transitioning to published
    $publishedAt = $existing['published_at'];
    if ($status === 'published' && $existing['status'] !== 'published') {
        $publishedAt = date('Y-m-d H:i:s');
    }

    // Update remaining fields
    $stmt = $conn->prepare(
        "UPDATE blog_posts SET category_id=?, title=?, slug=?, excerpt=?, content=?,
                author_name=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?,
                canonical_url=?, robots=?, is_featured=?, status=?, published_at=?,
                display_order=?, is_active=?
         WHERE id=?"
    );
    $stmt->bind_param(
        'isssssssssssissiii',
        $categoryId, $title, $slug, $excerpt, $content,
        $authorName, $metaTitle, $metaDescription, $metaKeywords, $metaImage,
        $canonicalUrl, $robots, $isFeatured, $status, $publishedAt,
        $displayOrder, $isActive, $id
    );

    if (!$stmt->execute()) { throw new RuntimeException('Post update failed: ' . $stmt->error); }
    $stmt->close();

    $conn->commit();
    jsonResponse(200, 'success', ['id' => $id, 'slug' => $slug], 'Blog post updated successfully.');

} catch (Exception $e) {
    $conn->rollback();
    // Clean up newly uploaded file if transaction failed
    if (isset($destination) && file_exists($destination)) {
        @unlink($destination);
    }
    logError('Blog post update failed: ' . $e->getMessage());
    jsonResponse(500, 'error', null, 'Failed to update blog post.');
}
