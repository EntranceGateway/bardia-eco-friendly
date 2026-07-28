<?php
/**
 * POST /api/activities/delete
 * Admin endpoint to delete an activity.
 */

if (getRequestMethod() !== 'POST') {
    jsonResponse(405, 'error', null, 'Method not allowed. Use POST.');
}

requireAdmin();

$data = getJsonInput();
$id   = (int) ($data['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    jsonResponse(400, 'error', null, 'Activity ID is required.');
}

$stmt = $conn->prepare("DELETE FROM activities WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(200, 'success', null, 'Activity deleted successfully.');
} else {
    $error = $stmt->error;
    $stmt->close();
    jsonResponse(500, 'error', null, 'Failed to delete activity: ' . $error);
}
