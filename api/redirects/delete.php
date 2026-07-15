<?php
if (getRequestMethod() !== 'DELETE') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use DELETE.');
}

requireRole(['admin', 'manager', 'seo']);
$input = getJsonInput();
$id = (int) ($input['id'] ?? ($_GET['id'] ?? 0));

if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Valid id is required.');
}

$stmt = $conn->prepare("DELETE FROM seo_redirects WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', null, 'Redirect deleted successfully.');
}

logError('Redirect delete failed: ' . $stmt->error);
$stmt->close();
jsonResponse(500, 'error', null, 'Failed to delete redirect.');
