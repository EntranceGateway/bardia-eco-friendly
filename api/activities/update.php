<?php
/**
 * POST /api/activities/update
 * Admin endpoint to update an existing activity.
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

requireAdmin();

$data = getJsonInput();

$id               = (int)  ($data['id']               ?? $_POST['id']               ?? 0);
$title            = trim   ($data['title']            ?? $_POST['title']            ?? '');
$slug             = strtolower(trim($data['slug']  ?? $_POST['slug']             ?? ''));
$icon             = trim   ($data['icon']             ?? $_POST['icon']             ?? '');
$shortDescription = trim   ($data['short_description']?? $_POST['short_description']?? '');
$description      = trim   ($data['description']      ?? $_POST['description']      ?? '');
$metaTitle        = trim   ($data['meta_title']       ?? $_POST['meta_title']       ?? '');
$metaDesc         = trim   ($data['meta_description'] ?? $_POST['meta_description'] ?? '');
$metaKeywords     = trim   ($data['meta_keywords']    ?? $_POST['meta_keywords']    ?? '');
$metaImage        = trim   ($data['meta_image']       ?? $_POST['meta_image']       ?? '');
$canonicalUrl     = trim   ($data['canonical_url']    ?? $_POST['canonical_url']    ?? '');
$robots           = trim   ($data['robots']           ?? $_POST['robots']           ?? 'index,follow');
$displayOrder     = (int)  ($data['display_order']  ?? $_POST['display_order']  ?? 0);
$isActive         = isset  ($data['is_active']) ? (int)(bool)$data['is_active'] : (isset($_POST['is_active']) ? (int)(bool)$_POST['is_active'] : 1);

if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Activity ID is required.');
}

if (empty($title)) {
    jsonResponse(400, 'error', null, 'Title is required.');
}

if (empty($slug)) {
    $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $title));
}
$slug = preg_replace('/-+/', '-', trim($slug, '-'));

// Check duplicate slug for other records
$dup = $conn->prepare("SELECT id FROM activities WHERE slug = ? AND id != ? LIMIT 1");
$dup->bind_param('si', $slug, $id);
$dup->execute();
$dupRes = $dup->get_result();
if ($dupRes->fetch_assoc()) {
    $dup->close();
    jsonResponse(400, 'error', null, 'Another activity with this slug already exists.');
}
$dup->close();

$stmt = $conn->prepare(
    "UPDATE activities SET title=?, slug=?, icon=?, short_description=?, description=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?, canonical_url=?, robots=?, display_order=?, is_active=?
     WHERE id=?"
);
$stmt->bind_param('sssssssssssiii', $title, $slug, $icon, $shortDescription, $description, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $displayOrder, $isActive, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id, 'slug' => $slug], 'Activity updated successfully.');
} else {
    $error = $stmt->error;
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to update activity: ' . $error);
}
