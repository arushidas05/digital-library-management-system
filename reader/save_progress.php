<?php
/**
 * Reader API: Save Reading Progress
 */
require_once __DIR__ . '/../config.php';
if (!headers_sent()) {
    header('Content-Type: application/json');
}

if (!is_logged_in()) {
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$userId = current_user()['id'];
$bookId = (int)($_POST['book_id'] ?? 0);
$currentChapter = (int)($_POST['current_chapter'] ?? 1);
$currentPage = (int)($_POST['current_page'] ?? 1);
$percentage = (float)($_POST['progress_percentage'] ?? 0);

if ($bookId <= 0) {
    echo json_encode(['error' => 'Invalid book']);
    exit;
}

if ($percentage < 0) $percentage = 0;
if ($percentage > 100) $percentage = 100;

// Check existing progress
$existing = db_fetch_one("SELECT id FROM reading_progress WHERE user_id = ? AND book_id = ?", [$userId, $bookId], "ii");

if ($existing) {
    $ok = db_execute(
        "UPDATE reading_progress SET current_chapter = ?, current_page = ?, progress_percentage = ?, last_read_at = NOW() WHERE id = ?",
        [$currentChapter, $currentPage, $percentage, $existing['id']],
        "iidi"
    );
} else {
    $ok = db_execute(
        "INSERT INTO reading_progress (user_id, book_id, current_chapter, current_page, progress_percentage, last_read_at) VALUES (?, ?, ?, ?, ?, NOW())",
        [$userId, $bookId, $currentChapter, $currentPage, $percentage],
        "iiiid"
    );
}

echo json_encode([
    'success' => (bool)$ok,
    'percentage' => $percentage,
    'chapter' => $currentChapter
]);
