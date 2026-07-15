<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$pageSlug = isset($_GET['page_slug']) ? sanitize($_GET['page_slug']) : null;
$sql = "SELECT id, page_slug, schema_type, schema_json, is_active, created_at, updated_at FROM seo_schemas";
if ($pageSlug) {
    $sql .= " WHERE page_slug = '" . $conn->real_escape_string($pageSlug) . "'";
}
$sql .= " ORDER BY page_slug ASC, schema_type ASC, id ASC";

$result = $conn->query($sql);
if (!$result) {
    logError('Schema list failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch schemas.');
}

$schemas = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['schema'] = json_decode($row['schema_json'], true);
    $row['is_active'] = (bool) $row['is_active'];
    unset($row['schema_json']);
    $schemas[] = $row;
}

jsonResponse(200, 'success', $schemas);
