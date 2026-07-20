<?php
/**
 * GET /api/package-categories/list
 * List all active package categories (public, no auth)
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$result = $conn->query(
    "SELECT id, name, slug, display_order, is_active, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots
     FROM package_categories
     ORDER BY display_order ASC, id ASC"
);

if (!$result) {
    logError('Package categories list failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch package categories.');
}

$categories = [];
while ($row = $result->fetch_assoc()) {
    $row['id']            = (int)  $row['id'];
    $row['display_order'] = (int)  $row['display_order'];
    $row['is_active']     = (bool) $row['is_active'];
    $categories[] = $row;
}

jsonResponse(200, 'success', $categories);
