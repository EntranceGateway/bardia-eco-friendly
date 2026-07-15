<?php
if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

$pageSlug = slugify($input['page_slug'] ?? '');
$schemaType = sanitize($input['schema_type'] ?? '');
$schema = $input['schema'] ?? null;
$isActive = isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1;

if (!$pageSlug || !$schemaType || !is_array($schema)) {
    jsonResponse(400, 'error', null, 'page_slug, schema_type, and schema object are required.');
}

$schemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (json_last_error() !== JSON_ERROR_NONE) {
    jsonResponse(400, 'error', null, 'Invalid schema JSON.');
}

$stmt = $conn->prepare("INSERT INTO seo_schemas (page_slug, schema_type, schema_json, is_active) VALUES (?, ?, ?, ?)");
$stmt->bind_param('sssi', $pageSlug, $schemaType, $schemaJson, $isActive);

if ($stmt->execute()) {
    $id = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => (int) $id], 'Schema created successfully.');
}

logError('Schema create failed: ' . $stmt->error);
$stmt->close();
jsonResponse(500, 'error', null, 'Failed to create schema.');
