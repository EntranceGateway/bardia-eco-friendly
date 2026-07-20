<?php
/**
 * POST /api/gallery-images/upload
 * Upload a gallery image (auth required, multipart/form-data)
 * Fields: category_id (required), alt_text, display_order
 * File:   image (required, jpg/jpeg/png/webp, max 5MB)
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

$auth = requireAuth();

$categoryId   = (int)    ($_POST['category_id']   ?? 0);
$altText      = sanitize(($_POST['alt_text']       ?? ''));
$titleText    = sanitize(($_POST['title_text']     ?? ''));
$caption      = sanitize(($_POST['caption']        ?? ''));
$displayOrder = (int)    ($_POST['display_order']  ?? 0);
$slug            = slugify($_POST['slug']               ?? $titleText);
$metaTitle       = sanitize($_POST['meta_title']         ?? '');
$metaDescription = sanitize($_POST['meta_description']   ?? '');
$metaKeywords    = sanitize($_POST['meta_keywords']      ?? '');
$metaImage       = sanitize($_POST['meta_image']         ?? '');
$canonicalUrl    = sanitize($_POST['canonical_url']      ?? '');
$robots          = sanitize($_POST['robots']             ?? 'index,follow');

if ($categoryId <= 0) {
    jsonResponse(400, 'error', null, 'Valid category_id is required.');
}

// Validate category exists
$check = $conn->prepare("SELECT id FROM gallery_categories WHERE id = ?");
$check->bind_param('i', $categoryId);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    jsonResponse(404, 'error', null, 'Gallery category not found.');
}
$check->close();

// Validate uploaded file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $uploadError = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    jsonResponse(400, 'error', null, 'Image upload failed. Error code: ' . $uploadError);
}

$file     = $_FILES['image'];
$maxBytes = 5 * 1024 * 1024; // 5 MB
$allowed  = ['image/jpeg', 'image/png', 'image/webp'];

if ($file['size'] > $maxBytes) {
    jsonResponse(400, 'error', null, 'Image file is too large. Maximum size is 5MB.');
}

// Use finfo for reliable MIME detection (do not trust $_FILES['type'])
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowed, true)) {
    jsonResponse(400, 'error', null, 'Invalid file type. Allowed: jpg, jpeg, png, webp.');
}

$imageSize = getimagesize($file['tmp_name']);
if ($imageSize === false) {
    jsonResponse(400, 'error', null, 'Uploaded file is not a valid image.');
}
$width  = (int) $imageSize[0];
$height = (int) $imageSize[1];

// Build destination path
$storageDir = __DIR__ . '/../../storage/gallery/';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
$filename     = time() . '_' . $originalName;
$destination  = $storageDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    logError('Gallery image move_uploaded_file failed for: ' . $filename);
    jsonResponse(500, 'error', null, 'Failed to save image file.');
}

// Relative URL (no base path in database - will be added dynamically)
$imageUrl = '/storage/gallery/' . $filename;

$stmt = $conn->prepare(
    "INSERT INTO gallery_images (category_id, image_url, slug, alt_text, title_text, caption, width, height, display_order, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('isssssiiissssss', $categoryId, $imageUrl, $slug, $altText, $titleText, $caption, $width, $height, $displayOrder, $metaTitle, $metaDescription, $metaKeywords, $metaImage, $canonicalUrl, $robots);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', [
        'id'       => (int) $newId,
        'imageUrl' => $imageUrl,
        'title'    => $titleText,
        'caption'  => $caption,
        'width'    => $width,
        'height'   => $height,
    ], 'Image uploaded successfully.');
} else {
    // Clean up file if DB insert fails
    @unlink($destination);
    logError('Gallery image insert failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to save image record.');
}
