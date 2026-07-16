<?php
if (getRequestMethod() !== 'GET') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method not allowed. Use GET.';
    exit;
}

$urls = [
    ['slug' => 'home', 'loc' => frontend_url('/'), 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d'), 'alternates' => []],
    ['slug' => 'gallery', 'loc' => frontend_url('/gallery'), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d'), 'alternates' => []],
    ['slug' => 'farm-to-table', 'loc' => frontend_url('/farm-to-table'), 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-d'), 'alternates' => []],
    ['slug' => 'packages', 'loc' => frontend_url('/packages'), 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => date('Y-m-d'), 'alternates' => []],
    ['slug' => 'blog', 'loc' => frontend_url('/blog'), 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-d'), 'alternates' => []],
    ['slug' => 'contact', 'loc' => frontend_url('/contact'), 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => date('Y-m-d'), 'alternates' => []],
];

$alternateGroups = [];
$pageGroupsBySlug = [];
$altResult = $conn->query("SELECT translation_group, language_code, slug, hreflang_url FROM pages WHERE status = 'published' AND robots NOT LIKE 'noindex%' AND translation_group IS NOT NULL");
if ($altResult) {
    while ($row = $altResult->fetch_assoc()) {
        $group = $row['translation_group'];
        $pageGroupsBySlug[trim($row['slug'], '/')] = $group;
        $alternateGroups[$group][] = [
            'hreflang' => $row['language_code'],
            'href' => $row['hreflang_url'] ?: frontend_url('/' . trim($row['slug'], '/')),
        ];
    }
}

foreach ($urls as &$url) {
    $group = $pageGroupsBySlug[$url['slug']] ?? '';
    $url['alternates'] = $alternateGroups[$group] ?? [];
}
unset($url);

$result = $conn->query("SELECT slug, updated_at, translation_group FROM pages WHERE status = 'published' AND robots NOT LIKE 'noindex%' ORDER BY updated_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $slug = trim($row['slug'], '/');
        if (in_array($slug, ['home', 'gallery', 'farm-to-table', 'packages', 'blog', 'contact'], true)) {
            continue;
        }
        $urls[] = [
            'loc' => frontend_url('/' . $slug),
            'priority' => '0.5',
            'changefreq' => 'monthly',
            'lastmod' => substr($row['updated_at'], 0, 10),
            'alternates' => $alternateGroups[$row['translation_group'] ?? ''] ?? [],
        ];
    }
}

// ── Published blog posts ──────────────────────────────────────────────────
$blogResult = $conn->query(
    "SELECT slug, published_at, updated_at
     FROM blog_posts
     WHERE status = 'published' AND is_active = 1 AND robots NOT LIKE 'noindex%'
     ORDER BY published_at DESC"
);
if ($blogResult) {
    while ($row = $blogResult->fetch_assoc()) {
        $lastmod = $row['updated_at'] ? substr($row['updated_at'], 0, 10)
                 : ($row['published_at'] ? substr($row['published_at'], 0, 10) : date('Y-m-d'));
        $urls[] = [
            'loc'        => frontend_url('/blog/' . trim($row['slug'], '/')),
            'priority'   => '0.6',
            'changefreq' => 'monthly',
            'lastmod'    => $lastmod,
            'alternates' => [],
        ];
    }
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">\n";
foreach ($urls as $url) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
    foreach ($url['alternates'] as $alternate) {
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"" . htmlspecialchars($alternate['hreflang'], ENT_XML1) . "\" href=\"" . htmlspecialchars($alternate['href'], ENT_XML1) . "\" />\n";
    }
    echo "    <lastmod>" . htmlspecialchars($url['lastmod'], ENT_XML1) . "</lastmod>\n";
    echo "    <changefreq>" . htmlspecialchars($url['changefreq'], ENT_XML1) . "</changefreq>\n";
    echo "    <priority>" . htmlspecialchars($url['priority'], ENT_XML1) . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
exit;
