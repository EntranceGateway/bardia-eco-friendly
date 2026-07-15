<?php
if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

$sourcePath = '/' . ltrim(sanitize($input['source_path'] ?? ''), '/');
$targetUrl = sanitize($input['target_url'] ?? '');
$statusCode = (int) ($input['status_code'] ?? 301);
$isActive = isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1;

if ($sourcePath === '/' || !$targetUrl) {
    jsonResponse(400, 'error', null, 'source_path and target_url are required.');
}
if (!in_array($statusCode, [301, 302, 307, 308], true)) {
    jsonResponse(400, 'error', null, 'Invalid redirect status code.');
}

$stmt = $conn->prepare("INSERT INTO seo_redirects (source_path, target_url, status_code, is_active) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssii', $sourcePath, $targetUrl, $statusCode, $isActive);

if ($stmt->execute()) {
    $id = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => (int) $id], 'Redirect created successfully.');
}

logError('Redirect create failed: ' . $stmt->error);
$stmt->close();
jsonResponse(500, 'error', null, 'Failed to create redirect.');
