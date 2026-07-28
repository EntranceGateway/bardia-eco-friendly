<?php
/**
 * GET /api/activities/get?slug=jeep-safari (or ?id=1)
 * Fetch details for a single activity.
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$id   = (int) ($_GET['id']   ?? 0);
$slug = trim($_GET['slug'] ?? '');

if ($id <= 0 && empty($slug)) {
    jsonResponse(400, 'error', null, 'Parameter id or slug is required.');
}

if ($id > 0) {
    $stmt = $conn->prepare("SELECT id, title, slug, icon, short_description, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, display_order, is_active, created_at, updated_at FROM activities WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
} else {
    $stmt = $conn->prepare("SELECT id, title, slug, icon, short_description, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, display_order, is_active, created_at, updated_at FROM activities WHERE slug = ? LIMIT 1");
    $stmt->bind_param('s', $slug);
}

$stmt->execute();
$res = $stmt->get_result();
$activity = $res->fetch_assoc();
$stmt->close();

if (!$activity) {
    jsonResponse(404, 'error', null, 'Activity not found.');
}

$activity['id']            = (int) $activity['id'];
$activity['display_order'] = (int) $activity['display_order'];
$activity['is_active']     = (bool) $activity['is_active'];

jsonResponse(200, 'success', $activity);
