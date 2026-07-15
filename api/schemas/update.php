<?php
if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

$id = (int) ($input['id'] ?? 0);
$pageSlug = slugify($input['page_slug'] ?? '');
$schemaType = sanitize($input['schema_type'] ?? '');
$schema = $input['schema'] ?? null;
$isActive = isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1;

if ($id <= 0 || !$pageSlug || !$schemaType || !is_array($schema)) {
    jsonResponse(400, 'error', null, 'id, page_slug, schema_type, and schema object are required.');
}

$schemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$stmt = $conn->prepare("UPDATE seo_schemas SET page_slug=?, schema_type=?, schema_json=?, is_active=? WHERE id=?");
$stmt->bind_param('sssii', $pageSlug, $schemaType, $schemaJson, $isActive, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id], 'Schema updated successfully.');
}

logError('Schema update failed: ' . $stmt->error);
$stmt->close();
jsonResponse(500, 'error', null, 'Failed to update schema.');
