<?php
/**
 * DELETE /api/pages/delete
 * Delete a CMS page (Protected)
 */

if (getRequestMethod() !== 'DELETE') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use DELETE.');
}

requireRole(['admin', 'manager']);
$input = getJsonInput();

$id = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Valid page ID is required.');
}

// Check if page exists
$check = $conn->prepare("SELECT id FROM pages WHERE id = ?");
$check->bind_param('i', $id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    $check->close();
    jsonResponse(404, 'error', null, 'Page not found.');
}
$check->close();

$stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', null, 'Page deleted successfully.');
} else {
    logError('Page delete failed: ' . $stmt->error);
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to delete page.');
}
