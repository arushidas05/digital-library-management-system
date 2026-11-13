<?php
/**
 * Reader API: Manage Bookmarks
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
    $title = trim($_POST['title'] ?? 'Chapter ' . $chapter . ' Bookmark');
    $note = trim($_POST['note'] ?? '');

    $ok = db_execute(
        "INSERT INTO bookmarks (user_id, book_id, chapter_number, page_number, title, note) VALUES (?, ?, ?, ?, ?, ?)",
        [$userId, $bookId, $chapter, $page, $title, $note],
        "iiiiss"
    );

    $newId = db_last_id();
    echo json_encode([
        'success' => (bool)$ok,
        'id' => $newId,
        'title' => $title,
        'chapter' => $chapter,
        'page' => $page
    ]);
    exit;
} elseif ($action === 'delete') {
    $bookmarkId = (int)($_POST['bookmark_id'] ?? 0);
    $ok = db_execute("DELETE FROM bookmarks WHERE id = ? AND user_id = ?", [$bookmarkId, $userId], "ii");
    echo json_encode(['success' => (bool)$ok]);
    exit;
} else {
    // List bookmarks for this book
    $bookmarks = db_fetch_all(
        "SELECT * FROM bookmarks WHERE user_id = ? AND book_id = ? ORDER BY chapter_number ASC, page_number ASC",
        [$userId, $bookId],
        "ii"
    );
    echo json_encode(['success' => true, 'bookmarks' => $bookmarks]);
    exit;
}
