<?php
/**
 * POST /api/activities/create
 * Admin endpoint to create a new activity.
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

requireAdmin();

$data = getJsonInput();

$title            = trim($data['title']             ?? $_POST['title']             ?? '');
$slug             = strtolower(trim($data['slug']   ?? $_POST['slug']              ?? ''));
$icon             = trim($data['icon']              ?? $_POST['icon']              ?? '');
$shortDescription = trim($data['short_description'] ?? $_POST['short_description'] ?? '');
$description      = trim($data['description']       ?? $_POST['description']       ?? '');
$metaTitle        = trim($data['meta_title']        ?? $_POST['meta_title']        ?? '');
$metaDesc         = trim($data['meta_description']  ?? $_POST['meta_description']  ?? '');
$metaKeywords     = trim($data['meta_keywords']     ?? $_POST['meta_keywords']     ?? '');
$metaImage        = trim($data['meta_image']        ?? $_POST['meta_image']        ?? '');
$canonicalUrl     = trim($data['canonical_url']     ?? $_POST['canonical_url']     ?? '');
$robots           = trim($data['robots']            ?? $_POST['robots']            ?? 'index,follow');
$displayOrder     = (int) ($data['display_order']   ?? $_POST['display_order']   ?? 0);
$isActive         = isset($data['is_active']) ? (int)(bool)$data['is_active'] : (isset($_POST['is_active']) ? (int)(bool)$_POST['is_active'] : 1);

if (empty($title)) {
    jsonResponse(400, 'error', null, 'Title is required.');
}

if (empty($slug)) {
    $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $title));
}
$slug = preg_replace('/-+/', '-', trim($slug, '-'));

// Check duplicate slug
$dup = $conn->prepare("SELECT id FROM activities WHERE slug = ? LIMIT 1");
$dup->bind_param('s', $slug);
$dup->execute();
$dupRes = $dup->get_result();
if ($dupRes->fetch_assoc()) {
    $dup->close();
    jsonResponse(400, 'error', null, 'An activity with this slug already exists.');
}
$dup->close();

$stmt = $conn->prepare(
    "INSERT INTO activities (title, slug, icon, short_description, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, display_order, is_active)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssssssssssii', $title, $slug, $icon, $shortDescription, $description, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $displayOrder, $isActive);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => $newId, 'slug' => $slug], 'Activity created successfully.');
} else {
    $error = $stmt->error;
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to create activity: ' . $error);
}
