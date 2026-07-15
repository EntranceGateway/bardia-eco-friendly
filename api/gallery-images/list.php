<?php
/**
 * GET /api/gallery-images/list
 * List gallery images, optionally filtered by category_id (public)
 * Query: ?category_id=X  (optional)
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

if ($categoryId !== null && $categoryId <= 0) {
    jsonResponse(400, 'error', null, 'category_id must be a positive integer.');
}

$sql = "SELECT gi.id, gi.category_id, gi.image_url, gi.alt_text, gi.title_text, gi.caption, gi.width, gi.height, gi.display_order, gi.is_active, gi.created_at,
               gc.slug AS category_slug, gc.name AS category_name
        FROM gallery_images gi
        LEFT JOIN gallery_categories gc ON gi.category_id = gc.id";

if ($categoryId !== null) {
    $sql .= " WHERE gi.category_id = " . $categoryId;
}

$sql .= " ORDER BY gi.display_order ASC, gi.id ASC";

$result = $conn->query($sql);

if (!$result) {
    logError('Gallery images list failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch gallery images.');
}

$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = [
        'id'            => (int)  $row['id'],
        'categoryId'    => (int)  $row['category_id'],
        'categorySlug'  => $row['category_slug'],
        'categoryName'  => $row['category_name'],
        'imageUrl'      => asset_url($row['image_url']),
        'altText'       => $row['alt_text'],
        'title'         => $row['title_text'],
        'caption'       => $row['caption'],
        'width'         => $row['width'] !== null ? (int) $row['width'] : null,
        'height'        => $row['height'] !== null ? (int) $row['height'] : null,
        'displayOrder'  => (int)  $row['display_order'],
        'isActive'      => (bool) $row['is_active'],
        'createdAt'     => $row['created_at'],
    ];
}

jsonResponse(200, 'success', $images);
