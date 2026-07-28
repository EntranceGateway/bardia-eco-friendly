<?php
/**
 * POST /api/page-seo/update
 * Update or insert page-specific SEO settings for a given slug.
 * Requires admin authorization.
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

requireAdmin();

$data = getJsonInput();

$slug = strtolower(trim($data['slug'] ?? $_POST['slug'] ?? ''));
if (empty($slug)) {
    jsonResponse(400, 'error', null, 'Slug is required.');
}

$title        = trim($data['title']            ?? $_POST['title']            ?? ucfirst(str_replace('-', ' ', $slug)));
$metaTitle    = trim($data['meta_title']       ?? $_POST['meta_title']       ?? '');
$metaDesc     = trim($data['meta_description']  ?? $_POST['meta_description']  ?? '');
$metaKeywords = trim($data['meta_keywords']    ?? $_POST['meta_keywords']    ?? '');
$metaImage    = trim($data['meta_image']       ?? $_POST['meta_image']       ?? '');
$canonicalUrl = trim($data['canonical_url']    ?? $_POST['canonical_url']    ?? '');
$robots       = trim($data['robots']           ?? $_POST['robots']           ?? 'index,follow');
$languageCode = trim($data['language_code']    ?? $_POST['language_code']    ?? 'en');
$hreflangUrl  = trim($data['hreflang_url']     ?? $_POST['hreflang_url']     ?? '');
$status       = trim($data['status']           ?? $_POST['status']           ?? 'published');

// Check if page with slug exists
$checkStmt = $conn->prepare("SELECT id FROM pages WHERE slug = ? LIMIT 1");
$checkStmt->bind_param('s', $slug);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
$existing = $checkRes->fetch_assoc();
$checkStmt->close();

if ($existing) {
    // Update existing page SEO fields
    $stmt = $conn->prepare(
        "UPDATE pages SET 
            meta_title = ?, 
            meta_description = ?, 
            meta_keywords = ?, 
            meta_image = ?, 
            canonical_url = ?, 
            robots = ?, 
            language_code = ?, 
            hreflang_url = ?, 
            status = ? 
         WHERE slug = ?"
    );
    $stmt->bind_param('ssssssssss', $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $languageCode, $hreflangUrl, $status, $slug);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        jsonResponse(200, 'success', ['slug' => $slug], 'Page SEO updated successfully.');
    } else {
        jsonResponse(500, 'error', null, 'Failed to update Page SEO.');
    }
} else {
    // Insert new page with SEO fields
    $stmt = $conn->prepare(
        "INSERT INTO pages (title, slug, content, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, language_code, hreflang_url, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $dummyContent = '<h2>' . htmlspecialchars($title) . '</h2>';
    $stmt->bind_param('ssssssssssss', $title, $slug, $dummyContent, $metaTitle, $metaDesc, $metaKeywords, $metaImage, $canonicalUrl, $robots, $languageCode, $hreflangUrl, $status);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        jsonResponse(201, 'success', ['slug' => $slug], 'Page SEO created successfully.');
    } else {
        jsonResponse(500, 'error', null, 'Failed to create Page SEO record.');
    }
}
