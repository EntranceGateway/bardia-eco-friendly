<?php
/**
 * PUT /api/packages/update
 * Update an existing package (auth required)
 * Body: { "id":1, "category_id":1, "icon":"🏡", "name":"Rustic", "duration":"2N·3D",
 *          "price":5000, "currency":"₹", "price_note":"Twin sharing",
 *          "description":"...", "is_featured":false, "display_order":0, "is_active":true }
 */

if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

$auth  = requireAuth();
$input = getJsonInput();

$allowedCurrencies = ['₹', '$', '€', '£'];

$id           = (int)   ($input['id']            ?? 0);
$categoryId   = (int)   ($input['category_id']   ?? 0);
$name         = sanitize($input['name']           ?? '');
$icon         = sanitize($input['icon']           ?? '');
$duration     = sanitize($input['duration']       ?? '');
$price        = (float) ($input['price']          ?? 0);
$currency     =          $input['currency']       ?? '₹';
$priceNote    = sanitize($input['price_note']     ?? '');
$description  = sanitize($input['description']    ?? '');
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

if ($id <= 0)         { jsonResponse(400, 'error', null, 'Valid package id is required.'); }
if ($categoryId <= 0) { jsonResponse(400, 'error', null, 'Valid category_id is required.'); }
if (empty($name))     { jsonResponse(400, 'error', null, 'Package name is required.'); }
if ($price < 0)       { jsonResponse(400, 'error', null, 'Price must be 0 or greater.'); }
if (!in_array($currency, $allowedCurrencies, true)) {
    jsonResponse(400, 'error', null, 'Invalid currency. Allowed: ₹, $, €, £');
}

// Verify package exists
$check = $conn->prepare("SELECT id FROM packages WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) { $check->close(); jsonResponse(404, 'error', null, 'Package not found.'); }
$check->close();

// Verify category exists
$catCheck = $conn->prepare("SELECT id FROM package_categories WHERE id = ?");
$catCheck->bind_param('i', $categoryId);
$catCheck->execute();
$catCheck->store_result();
if ($catCheck->num_rows === 0) { $catCheck->close(); jsonResponse(404, 'error', null, 'Package category not found.'); }
$catCheck->close();

// Types: issssdssssssssiiiii (19 params)
$stmt = $conn->prepare(
    "UPDATE packages
     SET category_id=?, icon=?, name=?, slug=?, duration=?, price=?, currency=?, price_note=?, description=?, meta_title=?, meta_description=?, meta_keywords=?, meta_image=?, canonical_url=?, robots=?, is_featured=?, display_order=?, is_active=?
     WHERE id=?"
);
$stmt->bind_param('issssdsssssssssiiii', $categoryId, $icon, $name, $slug, $duration, $price, $currency, $priceNote, $description, $metaTitle, $metaDescription, $metaKeywords, $metaImage, $canonicalUrl, $robots, $isFeatured, $displayOrder, $isActive, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id], 'Package updated successfully.');
} else {
    logError('Package update failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to update package.');
}
