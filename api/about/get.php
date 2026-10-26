<?php
/**
 * GET /api/about/get
 * Public About Us page content (active rows only), grouped by section:
 * { hero: {...}|null, story: {...}|null, values: [...], team: [...] }
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$result = $conn->query(
    "SELECT id, section_type, title, subtitle, body, image_url, image_alt, icon, display_order
     FROM about_sections
     WHERE is_active = 1
     ORDER BY display_order ASC, id ASC"
);

if (!$result) {
    logError('About sections query failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch About page content.');
}

$about = ['hero' => null, 'story' => null, 'values' => [], 'team' => []];

while ($row = $result->fetch_assoc()) {
    $row['id']            = (int) $row['id'];
    $row['display_order'] = (int) $row['display_order'];
    // Uploaded images live in backend storage; bundled ones (/gallery/...) are served by the frontend
    if (!empty($row['image_url']) && strpos($row['image_url'], '/storage/') === 0) {
        $row['image_url'] = asset_url($row['image_url']);
    }

    $type = $row['section_type'];
    unset($row['section_type']);

    if (($type === 'hero' || $type === 'story') && $about[$type] === null) {
        $about[$type] = $row;
    } elseif ($type === 'value') {
        $about['values'][] = $row;
    } elseif ($type === 'team') {
        $about['team'][] = $row;
    }
}

jsonResponse(200, 'success', $about);
