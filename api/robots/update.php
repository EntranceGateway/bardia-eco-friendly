<?php
if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();
$robots = (string) ($input['robots_txt'] ?? '');

if (trim($robots) === '') {
    jsonResponse(400, 'error', null, 'robots_txt is required.');
}

$stmt = $conn->prepare(
    "INSERT INTO seo_settings (setting_key, setting_value) VALUES ('robots_txt', ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);
$stmt->bind_param('s', $robots);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', null, 'robots.txt updated successfully.');
}

logError('robots.txt update failed: ' . $stmt->error);
$stmt->close();
jsonResponse(500, 'error', null, 'Failed to update robots.txt.');
