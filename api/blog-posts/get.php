<?php
/**
 * GET /api/blog-posts/get?slug=my-post
 * Get a single blog post by slug (public, no auth)
 * Returns full content + SEO meta fields + related posts
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    jsonResponse(400, 'error', null, 'Slug parameter is required.');
}

$stmt = $conn->prepare(
    "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug
     FROM blog_posts bp
     LEFT JOIN blog_categories bc ON bp.category_id = bc.id
     WHERE bp.slug = ? AND bp.is_active = 1
     LIMIT 1"
);
$stmt->bind_param('s', $slug);
$stmt->execute();
$result = $stmt->get_result();
$post   = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    jsonResponse(404, 'error', null, 'Blog post not found.');
}

// Type casting
$post['id']            = (int)  $post['id'];
$post['category_id']   = (int)  $post['category_id'];
$post['is_featured']   = (bool) $post['is_featured'];
$post['display_order'] = (int)  $post['display_order'];
$post['is_active']     = (bool) $post['is_active'];
if ($post['featured_image']) {
    $post['featured_image_url'] = asset_url($post['featured_image']);
} else {
    $post['featured_image_url'] = null;
}

// Fetch up to 3 related posts from the same category
$related = [];
$relStmt = $conn->prepare(
    "SELECT bp.id, bp.title, bp.slug, bp.excerpt, bp.featured_image, bp.published_at,
            bc.name AS category_name
     FROM blog_posts bp
     LEFT JOIN blog_categories bc ON bp.category_id = bc.id
     WHERE bp.category_id = ? AND bp.id != ? AND bp.status = 'published' AND bp.is_active = 1
     ORDER BY bp.published_at DESC
     LIMIT 3"
);
$relStmt->bind_param('ii', $post['category_id'], $post['id']);
$relStmt->execute();
$relResult = $relStmt->get_result();
while ($r = $relResult->fetch_assoc()) {
    $r['id'] = (int) $r['id'];
    if ($r['featured_image']) {
        $r['featured_image_url'] = asset_url($r['featured_image']);
    } else {
        $r['featured_image_url'] = null;
    }
    $related[] = $r;
}
$relStmt->close();

$post['related_posts'] = $related;

jsonResponse(200, 'success', $post);
