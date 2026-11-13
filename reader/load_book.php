<?php
/**
 * Reader API: Load Book Chapter Content
 */
require_once __DIR__ . '/../config.php';
if (!headers_sent()) {
    header('Content-Type: application/json');
}

if (!is_logged_in()) {
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$bookId = (int)($_GET['book_id'] ?? 0);
$chapterNum = (int)($_GET['chapter'] ?? 1);

if ($bookId <= 0) {
    echo json_encode(['error' => 'Invalid book ID']);
    exit;
}

// Fetch book and digital book
$book = db_fetch_one("SELECT * FROM books WHERE id = ? AND is_digital = 1", [$bookId], "i");
if (!$book) {
    echo json_encode(['error' => 'Digital book not found']);
    exit;
}

$digitalBook = db_fetch_one("SELECT * FROM digital_books WHERE book_id = ?", [$bookId], "i");
if (!$digitalBook) {
    echo json_encode(['error' => 'Digital book file records not initialized']);
    exit;
}

// Fetch all chapters list (TOC)
$chapters = db_fetch_all(
    "SELECT id, chapter_number, title FROM digital_book_chapters WHERE digital_book_id = ? ORDER BY chapter_number ASC",
    [$digitalBook['id']],
    "i"
);

// Fetch specific chapter requested
$currentChapter = db_fetch_one(
    "SELECT * FROM digital_book_chapters WHERE digital_book_id = ? AND chapter_number = ?",
    [$digitalBook['id'], $chapterNum],
    "ii"
);

if (!$currentChapter && !empty($chapters)) {
    $currentChapter = $chapters[0];
}

echo json_encode([
    'success' => true,
    'book_title' => $book['title'],
    'total_chapters' => count($chapters),
    'chapters_toc' => $chapters,
    'current_chapter' => $currentChapter
]);
