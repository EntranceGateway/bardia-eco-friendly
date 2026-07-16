<?php
/**
 * POST /api/blog-posts/create
 * Create a new blog post (auth required, multipart/form-data)
 * Fields: category_id, title, slug, excerpt, content, author_name,
 *         meta_title, meta_description, meta_image, canonical_url, robots,
 *         is_featured, status, display_order, is_active
 * File:   featured_image (optional, jpg/jpeg/png/webp, max 5MB)
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

$auth = requireAuth();

$categoryId       = (int)    ($_POST['category_id']      ?? 0);
$title            = trim(    $_POST['title']              ?? '');
$slug             = slugify( $_POST['slug']               ?? $title);
$excerpt          = trim(    $_POST['excerpt']            ?? '');
$content          =          $_POST['content']            ?? '';
$authorName       = sanitize($_POST['author_name']        ?? '');
$metaTitle        = sanitize($_POST['meta_title']         ?? '');
$metaDescription  = sanitize($_POST['meta_description']   ?? '');
$metaImage        = sanitize($_POST['meta_image']         ?? '');
$canonicalUrl     = sanitize($_POST['canonical_url']      ?? '');
$robots           = sanitize($_POST['robots']             ?? 'index,follow');
$isFeatured       = isset($_POST['is_featured'])   ? (int)(bool) $_POST['is_featured']   : 0;
$status           =          $_POST['status']             ?? 'draft';
$displayOrder     = isset($_POST['display_order']) ? (int) $_POST['display_order']       : 0;
$isActive         = isset($_POST['is_active'])     ? (int)(bool) $_POST['is_active']     : 1;

// Validation
if ($categoryId <= 0) { jsonResponse(400, 'error', null, 'Valid category_id is required.'); }
if (empty($title))    { jsonResponse(400, 'error', null, 'Post title is required.'); }

$allowedStatuses = ['draft', 'published', 'archived'];
if (!in_array($status, $allowedStatuses, true)) {
    jsonResponse(400, 'error', null, 'Invalid status. Allowed: draft, published, archived.');
}

$allowedRobots = ['index,follow', 'noindex,follow', 'noindex,nofollow'];
if (!in_array($robots, $allowedRobots, true)) {
    jsonResponse(400, 'error', null, 'Invalid robots value.');
}

// Verify category exists
$catCheck = $conn->prepare("SELECT id FROM blog_categories WHERE id = ?");
$catCheck->bind_param('i', $categoryId);
$catCheck->execute();
$catCheck->store_result();
if ($catCheck->num_rows === 0) { $catCheck->close(); jsonResponse(404, 'error', null, 'Blog category not found.'); }
$catCheck->close();

// Check slug uniqueness
$slugCheck = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
$slugCheck->bind_param('s', $slug);
$slugCheck->execute();
$slugCheck->store_result();
if ($slugCheck->num_rows > 0) { $slugCheck->close(); jsonResponse(409, 'error', null, 'A blog post with this slug already exists.'); }
$slugCheck->close();

// Handle featured image upload
$featuredImage    = '';
$featuredImageAlt = sanitize($_POST['featured_image_alt'] ?? '');

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
        logError('Blog featured image move_uploaded_file failed for: ' . $filename);
        jsonResponse(500, 'error', null, 'Failed to save featured image.');
    }

    $featuredImage = '/storage/blog/' . $filename;
}

// Auto-set published_at
$publishedAt = null;
if ($status === 'published') {
    $publishedAt = date('Y-m-d H:i:s');
}

$stmt = $conn->prepare(
    "INSERT INTO blog_posts (category_id, title, slug, excerpt, content, featured_image, featured_image_alt, author_name, meta_title, meta_description, meta_image, canonical_url, robots, is_featured, status, published_at, display_order, is_active)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'issssssssssssisiii',
    $categoryId, $title, $slug, $excerpt, $content,
    $featuredImage, $featuredImageAlt, $authorName,
    $metaTitle, $metaDescription, $metaImage, $canonicalUrl, $robots,
    $isFeatured, $status, $publishedAt, $displayOrder, $isActive
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', [
        'id'             => (int) $newId,
        'slug'           => $slug,
        'featured_image' => $featuredImage ? asset_url($featuredImage) : null,
    ], 'Blog post created successfully.');
} else {
    // Clean up uploaded file if DB insert fails
    if ($featuredImage) {
        @unlink(__DIR__ . '/../../' . ltrim($featuredImage, '/'));
    }
    logError('Blog post create failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to create blog post.');
}
