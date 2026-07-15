<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$result = $conn->query("SELECT setting_key, setting_value FROM seo_settings ORDER BY setting_key ASC");
if (!$result) {
    logError('SEO settings fetch failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch SEO settings.');
}

$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

jsonResponse(200, 'success', $settings);
