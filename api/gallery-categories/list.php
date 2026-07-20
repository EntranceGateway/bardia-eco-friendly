<?php
/**
 * GET /api/gallery-categories/list
 * List active gallery categories ordered by display_order (public)
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$result = $conn->query(
    "SELECT id, name, slug, display_order, is_active, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, created_at FROM gallery_categories ORDER BY display_order ASC, id ASC"
);

if (!$result) {
    logError('Gallery categories list failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch gallery categories.');
}

$categories = [];
while ($row = $result->fetch_assoc()) {
    $row['id']            = (int)  $row['id'];
    $row['display_order'] = (int)  $row['display_order'];
    $row['is_active']     = (bool) $row['is_active'];
    $categories[] = $row;
}

jsonResponse(200, 'success', $categories);
