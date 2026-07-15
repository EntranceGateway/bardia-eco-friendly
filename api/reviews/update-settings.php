<?php
if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();
$allowed = [
    'google_reviews_enabled',
    'google_reviews_provider',
    'aggregate_rating_value',
    'aggregate_rating_count',
];

$stmt = $conn->prepare(
    "INSERT INTO seo_settings (setting_key, setting_value) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);

foreach ($allowed as $key) {
    if (!array_key_exists($key, $input)) {
        continue;
    }
    $value = sanitize((string) $input[$key]);
    $stmt->bind_param('ss', $key, $value);
    if (!$stmt->execute()) {
        logError('Review setting update failed: ' . $stmt->error);
        $stmt->close();
        jsonResponse(500, 'error', null, 'Failed to update review settings.');
    }
}

$stmt->close();
jsonResponse(200, 'success', null, 'Review settings updated successfully.');
