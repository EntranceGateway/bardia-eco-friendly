<?php
if (getRequestMethod() !== 'GET') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use GET.');
}

$pageSlug = isset($_GET['page_slug']) ? sanitize($_GET['page_slug']) : null;

$sql = "SELECT id, page_slug, question, answer, display_order, is_active, created_at, updated_at FROM faqs";
if ($pageSlug) {
    $sql .= " WHERE page_slug = '" . $conn->real_escape_string($pageSlug) . "'";
}
$sql .= " ORDER BY page_slug ASC, display_order ASC, id ASC";

$result = $conn->query($sql);
if (!$result) {
    logError('FAQ list failed: ' . $conn->error);
    jsonResponse(500, 'error', null, 'Failed to fetch FAQs.');
}

$faqs = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['display_order'] = (int) $row['display_order'];
    $row['is_active'] = (bool) $row['is_active'];
    $faqs[] = $row;
}

jsonResponse(200, 'success', $faqs);
