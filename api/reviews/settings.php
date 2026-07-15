<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$keys = ['google_reviews_enabled', 'google_reviews_provider', 'aggregate_rating_value', 'aggregate_rating_count'];
$result = $conn->query("SELECT setting_key, setting_value FROM seo_settings WHERE setting_key IN ('" . implode("','", $keys) . "')");
$settings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$ratingValue = (float) ($settings['aggregate_rating_value'] ?? 0);
$ratingCount = (int) ($settings['aggregate_rating_count'] ?? 0);
$schema = null;
if ($ratingValue > 0 && $ratingCount > 0) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LodgingBusiness',
        'name' => 'Bardia Eco-Friendly Homestay',
        'url' => frontend_url('/'),
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => $ratingValue,
            'reviewCount' => $ratingCount,
        ],
    ];
}

jsonResponse(200, 'success', [
    'enabled' => ($settings['google_reviews_enabled'] ?? '0') === '1',
    'provider' => $settings['google_reviews_provider'] ?? '',
    'aggregateRating' => $schema,
]);
