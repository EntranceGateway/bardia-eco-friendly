<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$result = $conn->query("SELECT setting_value FROM seo_settings WHERE setting_key = 'robots_txt' LIMIT 1");
$row = $result ? $result->fetch_assoc() : null;
$robots = $row['setting_value'] ?? "User-agent: *\nAllow: /\n\nSitemap: " . frontend_url('/sitemap.xml');

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo $robots;
exit;
