<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$result = $conn->query("SELECT id, source_path, target_url, status_code, is_active, created_at, updated_at FROM seo_redirects ORDER BY source_path ASC");
if (!$result) {
    logError('Redirect list failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch redirects.');
}

$redirects = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['status_code'] = (int) $row['status_code'];
    $row['is_active'] = (bool) $row['is_active'];
    $redirects[] = $row;
}

jsonResponse(200, 'success', $redirects);
