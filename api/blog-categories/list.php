<?php
/**
 * GET /api/blog-categories/list
 * List all blog categories (public, no auth)
 * Includes post count per category
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$sql = "SELECT bc.id, bc.name, bc.slug, bc.description, bc.display_order, bc.is_active,
               bc.created_at, bc.updated_at,
               COUNT(bp.id) AS post_count
        FROM blog_categories bc
        LEFT JOIN blog_posts bp ON bc.id = bp.category_id AND bp.status = 'published' AND bp.is_active = 1
        GROUP BY bc.id
        ORDER BY bc.display_order ASC, bc.id ASC";

$result = $conn->query($sql);

if (!$result) {
    logError('Blog categories list query failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch blog categories.');
}

$categories = [];
while ($row = $result->fetch_assoc()) {
    $row['id']            = (int)  $row['id'];
    $row['display_order'] = (int)  $row['display_order'];
    $row['is_active']     = (bool) $row['is_active'];
    $row['post_count']    = (int)  $row['post_count'];
    $categories[] = $row;
}

jsonResponse(200, 'success', $categories);
