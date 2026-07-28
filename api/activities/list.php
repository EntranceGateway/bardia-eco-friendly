<?php
/**
 * GET /api/activities/list
 * List all activities / wildlife experiences.
 * Supports public parameter `active_only=1` (default 1 for public, optional 0 for admin).
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$activeOnly = isset($_GET['all']) && $_GET['all'] === '1' ? false : true;

$query = "SELECT id, title, slug, icon, short_description, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, display_order, is_active, created_at, updated_at FROM activities";
if ($activeOnly) {
    $query .= " WHERE is_active = 1";
}
$query .= " ORDER BY display_order ASC, id ASC";

$result = $conn->query($query);

if (!$result) {
    logError('Activities list query failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch activities.');
}

$activities = [];
while ($row = $result->fetch_assoc()) {
    $row['id']            = (int) $row['id'];
    $row['display_order'] = (int) $row['display_order'];
    $row['is_active']     = (bool) $row['is_active'];
    $activities[]         = $row;
}

jsonResponse(200, 'success', $activities);
