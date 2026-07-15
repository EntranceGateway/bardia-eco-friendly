<?php
if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

if (!is_array($input) || empty($input)) {
    jsonResponse(400, 'error', null, 'Settings object is required.');
}

$stmt = $conn->prepare(
    "INSERT INTO seo_settings (setting_key, setting_value) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);

foreach ($input as $key => $value) {
    $settingKey = slugify((string) $key);
    $settingKey = str_replace('-', '_', $settingKey);
    $settingValue = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
    $stmt->bind_param('ss', $settingKey, $settingValue);
    if (!$stmt->execute()) {
        logError('SEO setting update failed: ' . $stmt->error);
        $stmt->close();
        jsonResponse(500, 'error', null, 'Failed to update SEO settings.');
    }
}

$stmt->close();
jsonResponse(200, 'success', null, 'SEO settings updated successfully.');
