<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

requireRole(['admin', 'manager', 'seo']);

$issues = [];

$pagesResult = $conn->query("SELECT slug, title, meta_title, meta_description, canonical_url, robots, language_code, translation_group, hreflang_url FROM pages WHERE status = 'published'");
if ($pagesResult) {
    while ($page = $pagesResult->fetch_assoc()) {
        $label = $page['slug'];
        $title = $page['meta_title'] ?: $page['title'];
        $description = $page['meta_description'] ?? '';

        if (strlen($title) < 10 || strlen($title) > 70) {
            $issues[] = [
                'severity' => 'medium',
                'type' => 'title_length',
                'page' => $label,
                'message' => 'SEO title should be between 10 and 70 characters.',
            ];
        }

        if (strlen($description) < 50 || strlen($description) > 180) {
            $issues[] = [
                'severity' => 'high',
                'type' => 'description_length',
                'page' => $label,
                'message' => 'Meta description should be between 50 and 180 characters.',
            ];
        }

        if (!$page['canonical_url']) {
            $issues[] = [
                'severity' => 'low',
                'type' => 'missing_canonical',
                'page' => $label,
                'message' => 'Canonical URL is not set in CMS metadata.',
            ];
        }

        if (!in_array($page['robots'], ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            $issues[] = [
                'severity' => 'high',
                'type' => 'invalid_robots',
                'page' => $label,
                'message' => 'Robots value is invalid.',
            ];
        }

        if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $page['language_code'])) {
            $issues[] = [
                'severity' => 'high',
                'type' => 'invalid_language_code',
                'page' => $label,
                'message' => 'language_code must use a valid hreflang-style value such as en or ne.',
            ];
        }

        if ($page['translation_group'] && !$page['hreflang_url']) {
            $issues[] = [
                'severity' => 'low',
                'type' => 'missing_hreflang_url',
                'page' => $label,
                'message' => 'Translation group is set but hreflang_url is empty; sitemap will fall back to slug URL.',
            ];
        }
    }
}

$redirectResult = $conn->query("SELECT source_path, target_url FROM seo_redirects WHERE is_active = 1");
if ($redirectResult) {
    while ($redirect = $redirectResult->fetch_assoc()) {
        if ($redirect['source_path'] === $redirect['target_url']) {
            $issues[] = [
                'severity' => 'critical',
                'type' => 'redirect_loop',
                'page' => $redirect['source_path'],
                'message' => 'Redirect source and target are identical.',
            ];
        }
    }
}

$imageResult = $conn->query("SELECT id, image_url, alt_text, width, height FROM gallery_images WHERE is_active = 1");
if ($imageResult) {
    while ($image = $imageResult->fetch_assoc()) {
        if (!$image['alt_text']) {
            $issues[] = [
                'severity' => 'medium',
                'type' => 'missing_image_alt',
                'page' => $image['image_url'],
                'message' => 'Gallery image is missing alt text.',
            ];
        }
        if (!$image['width'] || !$image['height']) {
            $issues[] = [
                'severity' => 'medium',
                'type' => 'missing_image_dimensions',
                'page' => $image['image_url'],
                'message' => 'Gallery image is missing intrinsic dimensions.',
            ];
        }
    }
}

jsonResponse(200, 'success', [
    'passed' => count($issues) === 0,
    'issueCount' => count($issues),
    'issues' => $issues,
]);
