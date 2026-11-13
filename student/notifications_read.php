<?php
/**
 * Mark Notification Read Endpoint
 */
require_once __DIR__ . '/../config.php';
if (!headers_sent()) {
    header('Content-Type: application/json');
}

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = current_user()['id'];
$notifId = (int)($_GET['id'] ?? 0);

if ($notifId > 0) {
    db_execute("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$notifId, $userId], "ii");
}

echo json_encode(['success' => true]);
