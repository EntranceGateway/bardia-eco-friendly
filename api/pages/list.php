<?php
/**
 * GET /api/pages/list
 * List all CMS pages
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$result = $conn->query("SELECT id, title, slug, content, meta_title, meta_description, meta_image, canonical_url, robots, language_code, translation_group, hreflang_url, status, created_at, updated_at FROM pages ORDER BY created_at DESC");

if (!$result) {
    logError('Pages list query failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch pages.');
}

$pages = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $pages[] = $row;
}

jsonResponse(200, 'success', $pages);
