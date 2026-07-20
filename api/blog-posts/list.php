<?php
/**
 * GET /api/blog-posts/list
 * List blog posts (public, no auth)
 * Optional filters: ?category_id=1, ?status=published, ?is_featured=1
 * Pagination: ?page=1&per_page=10
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$filterCatId    = (int) ($_GET['category_id'] ?? 0);
$filterStatus   = $_GET['status']      ?? '';
$filterFeatured = $_GET['is_featured'] ?? '';
$page           = max(1, (int) ($_GET['page']     ?? 1));
$perPage        = max(1, min(50, (int) ($_GET['per_page'] ?? 10)));
$offset         = ($page - 1) * $perPage;

$conditions = [];
$params     = [];
$types      = '';

if ($filterCatId > 0) {
    $conditions[] = "bp.category_id = ?";
    $params[]     = $filterCatId;
    $types       .= 'i';
}
if (in_array($filterStatus, ['draft', 'published', 'archived'], true)) {
    $conditions[] = "bp.status = ?";
    $params[]     = $filterStatus;
    $types       .= 's';
} else {
    // Default: show only published for public requests
    $conditions[] = "bp.status = 'published'";
}
if ($filterFeatured !== '') {
    $conditions[] = "bp.is_featured = ?";
    $params[]     = (int)(bool) $filterFeatured;
    $types       .= 'i';
}

$conditions[] = "bp.is_active = 1";

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Count total for pagination
$countSql = "SELECT COUNT(*) AS total FROM blog_posts bp $whereClause";
if (!empty($params)) {
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $countResult = $conn->query($countSql);
    $total = (int) $countResult->fetch_assoc()['total'];
}

// Fetch posts
$sql = "SELECT bp.id, bp.category_id, bp.title, bp.slug, bp.excerpt,
               bp.featured_image, bp.featured_image_alt, bp.author_name,
               bp.meta_title, bp.meta_description, bp.meta_keywords,
               bp.is_featured, bp.status, bp.published_at,
               bp.display_order, bp.is_active, bp.created_at, bp.updated_at,
               bc.name AS category_name, bc.slug AS category_slug
        FROM blog_posts bp
        LEFT JOIN blog_categories bc ON bp.category_id = bc.id
        $whereClause
        ORDER BY bp.published_at DESC, bp.created_at DESC
        LIMIT ? OFFSET ?";

$allParams = $params;
$allParams[] = $perPage;
$allParams[] = $offset;
$allTypes    = $types . 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    $row['id']            = (int)  $row['id'];
    $row['category_id']   = (int)  $row['category_id'];
    $row['is_featured']   = (bool) $row['is_featured'];
    $row['display_order'] = (int)  $row['display_order'];
    $row['is_active']     = (bool) $row['is_active'];
    if ($row['featured_image']) {
        $row['featured_image_url'] = asset_url($row['featured_image']);
    } else {
        $row['featured_image_url'] = null;
    }
    $posts[] = $row;
}
$stmt->close();

jsonResponse(200, 'success', [
    'posts'       => $posts,
    'pagination'  => [
        'page'      => $page,
        'per_page'  => $perPage,
        'total'     => $total,
        'pages'     => (int) ceil($total / $perPage),
    ],
]);
