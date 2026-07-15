<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$path = '/' . ltrim(sanitize($_GET['path'] ?? ''), '/');
if ($path === '/') {
    jsonResponse(400, 'error', null, 'path is required.');
}

$stmt = $conn->prepare("SELECT target_url, status_code FROM seo_redirects WHERE source_path = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param('s', $path);
$stmt->execute();
$result = $stmt->get_result();
$redirect = $result->fetch_assoc();
$stmt->close();

if (!$redirect) {
    jsonResponse(404, 'error', null, 'Redirect not found.');
}

jsonResponse(200, 'success', [
    'sourcePath' => $path,
    'targetUrl' => $redirect['target_url'],
    'statusCode' => (int) $redirect['status_code'],
]);
