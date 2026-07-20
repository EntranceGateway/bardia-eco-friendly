<?php
/**
 * POST /api/packages/create
 * Create a new package (auth required)
 * Body: { "category_id":1, "icon":"🏡", "name":"Rustic", "duration":"2N·3D",
 *          "price":5000, "currency":"₹", "price_note":"Twin sharing",
 *          "description":"...", "is_featured":false, "display_order":0, "is_active":true }
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

$auth  = requireAuth();
$input = getJsonInput();

$allowedCurrencies = ['₹', '$', '€', '£'];

$categoryId   = (int)   ($input['category_id']  ?? 0);
$name         = sanitize($input['name']          ?? '');
$icon         = sanitize($input['icon']          ?? '');
$duration     = sanitize($input['duration']      ?? '');
$price        = (float) ($input['price']         ?? 0);
$currency     =          $input['currency']      ?? '₹';
$priceNote    = sanitize($input['price_note']    ?? '');
$description  = sanitize($input['description']   ?? '');
$slug            = slugify( $input['slug']               ?? $name);
$metaTitle       = sanitize($input['meta_title']         ?? '');
$metaDescription = sanitize($input['meta_description']   ?? '');
$metaKeywords    = sanitize($input['meta_keywords']      ?? '');
$metaImage       = sanitize($input['meta_image']         ?? '');
$canonicalUrl    = sanitize($input['canonical_url']      ?? '');
$robots          = sanitize($input['robots']             ?? 'index,follow');
$isFeatured   = isset($input['is_featured'])   ? (int)(bool)$input['is_featured']   : 0;
$displayOrder = isset($input['display_order']) ? (int)$input['display_order']       : 0;
$isActive     = isset($input['is_active'])     ? (int)(bool)$input['is_active']     : 1;

if ($categoryId <= 0) {
    jsonResponse(400, 'error', null, 'Valid category_id is required.');
}
if (empty($name)) {
    jsonResponse(400, 'error', null, 'Package name is required.');
}
if ($price < 0) {
    jsonResponse(400, 'error', null, 'Price must be 0 or greater.');
}
if (!in_array($currency, $allowedCurrencies, true)) {
    jsonResponse(400, 'error', null, 'Invalid currency. Allowed: ₹, $, €, £');
}

// Verify category exists
$catCheck = $conn->prepare("SELECT id FROM package_categories WHERE id = ?");
$catCheck->bind_param('i', $categoryId);
$catCheck->execute();
$catCheck->store_result();
if ($catCheck->num_rows === 0) {
    $catCheck->close();
    jsonResponse(404, 'error', null, 'Package category not found.');
}
$catCheck->close();

// Types: issssdssssssssiiii
// category_id, icon, name, slug, duration, price, currency, price_note, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, is_featured, display_order, is_active
$stmt = $conn->prepare(
    "INSERT INTO packages (category_id, icon, name, slug, duration, price, currency, price_note, description, meta_title, meta_description, meta_keywords, meta_image, canonical_url, robots, is_featured, display_order, is_active)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('issssdsssssssssiii', $categoryId, $icon, $name, $slug, $duration, $price, $currency, $priceNote, $description, $metaTitle, $metaDescription, $metaKeywords, $metaImage, $canonicalUrl, $robots, $isFeatured, $displayOrder, $isActive);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => $newId], 'Package created successfully.');
} else {
    logError('Package create failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to create package.');
}
