<?php
if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

$id = (int) ($input['id'] ?? 0);
$sourcePath = '/' . ltrim(sanitize($input['source_path'] ?? ''), '/');
$targetUrl = sanitize($input['target_url'] ?? '');
$statusCode = (int) ($input['status_code'] ?? 301);
$isActive = isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1;

if ($id <= 0 || $sourcePath === '/' || !$targetUrl) {
    jsonResponse(400, 'error', null, 'id, source_path and target_url are required.');
}
if (!in_array($statusCode, [301, 302, 307, 308], true)) {
    jsonResponse(400, 'error', null, 'Invalid redirect status code.');
}

$stmt = $conn->prepare("UPDATE seo_redirects SET source_path=?, target_url=?, status_code=?, is_active=? WHERE id=?");
$stmt->bind_param('ssiii', $sourcePath, $targetUrl, $statusCode, $isActive, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id], 'Redirect updated successfully.');
}

logError('Redirect update failed: ' . $stmt->error);
$stmt->close();
jsonResponse(500, 'error', null, 'Failed to update redirect.');
