<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$path = '/' . trim(sanitize($_GET['path'] ?? '/'), '/');
if ($path === '/') {
    $items = [
        ['label' => 'Home', 'href' => '/'],
    ];
} else {
    $slug = trim($path, '/');
    $label = ucwords(str_replace('-', ' ', $slug));

    $stmt = $conn->prepare("SELECT title FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $page = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($page && !empty($page['title'])) {
        $label = $page['title'];
    }

    $items = [
        ['label' => 'Home', 'href' => '/'],
        ['label' => $label, 'href' => $path],
    ];
}

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_map(function ($item, $index) {
        return [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['label'],
            'item' => frontend_url($item['href']),
        ];
    }, $items, array_keys($items)),
];

jsonResponse(200, 'success', [
    'items' => $items,
    'jsonLd' => $jsonLd,
]);
