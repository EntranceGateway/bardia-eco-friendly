<?php
if (getRequestMethod() !== 'PUT') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use PUT.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();

$id = (int) ($input['id'] ?? 0);
$pageSlug = slugify($input['page_slug'] ?? '');
$question = sanitize($input['question'] ?? '');
$answer = sanitize($input['answer'] ?? '');
$displayOrder = (int) ($input['display_order'] ?? 0);
$isActive = isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1;

if ($id <= 0 || !$pageSlug || !$question || !$answer) {
    jsonResponse(400, 'error', null, 'id, page_slug, question, and answer are required.');
}

$stmt = $conn->prepare("UPDATE faqs SET page_slug=?, question=?, answer=?, display_order=?, is_active=? WHERE id=?");
$stmt->bind_param('sssiii', $pageSlug, $question, $answer, $displayOrder, $isActive, $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', ['id' => $id], 'FAQ updated successfully.');
}

logError('FAQ update failed: ' . $stmt->error);
$stmt->close();
jsonResponse(500, 'error', null, 'Failed to update FAQ.');
