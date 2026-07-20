<?php
/**
 * GET /api/packages/list
 * List all packages with features (public, no auth)
 * Optional filter: ?category_id=1
 */

if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$filterCatId = (int) ($_GET['category_id'] ?? 0);

$sql = "SELECT p.id, p.category_id, p.icon, p.name, p.slug, p.duration,
               p.price, p.currency, p.price_note, p.description,
               p.meta_title, p.meta_description, p.meta_keywords, p.meta_image, p.canonical_url, p.robots,
               p.is_featured, p.display_order, p.is_active,
               pc.name AS category_name, pc.slug AS category_slug
        FROM packages p
        LEFT JOIN package_categories pc ON p.category_id = pc.id";

if ($filterCatId > 0) {
    $sql .= " WHERE p.category_id = " . $filterCatId;
}

$sql .= " ORDER BY p.display_order ASC, p.id ASC";

$result = $conn->query($sql);

if (!$result) {
    logError('Packages list query failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch packages.');
}

$packages = [];
while ($row = $result->fetch_assoc()) {
    $row['id']            = (int)   $row['id'];
    $row['category_id']   = (int)   $row['category_id'];
    $row['price']         = (float) $row['price'];
    $row['is_featured']   = (bool)  $row['is_featured'];
    $row['display_order'] = (int)   $row['display_order'];
    $row['is_active']     = (bool)  $row['is_active'];
    $row['features']      = [];
    $packages[$row['id']] = $row;
}

// Fetch all features in one query
if (!empty($packages)) {
    $ids     = implode(',', array_keys($packages));
    $fResult = $conn->query(
        "SELECT package_id, feature_text
         FROM package_features
         WHERE package_id IN ($ids)
         ORDER BY display_order ASC, id ASC"
    );
    if ($fResult) {
        while ($f = $fResult->fetch_assoc()) {
            $packages[(int)$f['package_id']]['features'][] = $f['feature_text'];
        }
    }
}

jsonResponse(200, 'success', array_values($packages));
