<?php
if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

$pageSlug = slugify($input['page_slug'] ?? '');
$question = sanitize($input['question'] ?? '');
$answer = sanitize($input['answer'] ?? '');
$displayOrder = (int) ($input['display_order'] ?? 0);
$isActive = isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1;

if (!$pageSlug || !$question || !$answer) {
    jsonResponse(400, 'error', null, 'page_slug, question, and answer are required.');
}

$stmt = $conn->prepare("INSERT INTO faqs (page_slug, question, answer, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('sssii', $pageSlug, $question, $answer, $displayOrder, $isActive);

if ($stmt->execute()) {
    $id = $stmt->insert_id;
    $stmt->close();
    jsonResponse(201, 'success', ['id' => (int) $id], 'FAQ created successfully.');
}

logError('FAQ create failed: ' . $stmt->error);
$stmt->close();
jsonResponse(500, 'error', null, 'Failed to create FAQ.');
