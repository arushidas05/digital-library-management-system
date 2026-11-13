<?php
/**
 * Reader API: Manage Reading Notes
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
$action = $_POST['action'] ?? ($_GET['action'] ?? 'list');
$bookId = (int)($_POST['book_id'] ?? ($_GET['book_id'] ?? 0));

if ($bookId <= 0) {
    echo json_encode(['error' => 'Invalid book']);
    exit;
}

if ($action === 'add') {
    $chapter = (int)($_POST['chapter_number'] ?? 1);
    $page = (int)($_POST['page_number'] ?? 1);
    $content = trim($_POST['content'] ?? '');

    if (empty($content)) {
        echo json_encode(['error' => 'Note content cannot be empty']);
        exit;
    }

    $ok = db_execute(
        "INSERT INTO notes (user_id, book_id, chapter_number, page_number, content) VALUES (?, ?, ?, ?, ?)",
        [$userId, $bookId, $chapter, $page, $content],
        "iiiis"
    );

    $newId = db_last_id();
    echo json_encode([
        'success' => (bool)$ok,
        'id' => $newId,
        'content' => $content,
        'chapter' => $chapter,
        'page' => $page,
        'created_at' => date('M d, Y h:i A')
    ]);
    exit;
} elseif ($action === 'delete') {
    $noteId = (int)($_POST['note_id'] ?? 0);
    $ok = db_execute("DELETE FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId], "ii");
    echo json_encode(['success' => (bool)$ok]);
    exit;
} else {
    // List notes for this book
    $notes = db_fetch_all(
        "SELECT * FROM notes WHERE user_id = ? AND book_id = ? ORDER BY chapter_number ASC, created_at DESC",
        [$userId, $bookId],
        "ii"
    );
    echo json_encode(['success' => true, 'notes' => $notes]);
    exit;
}
