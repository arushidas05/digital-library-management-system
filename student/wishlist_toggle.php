<?php
/**
 * Wishlist Toggle AJAX Endpoint
 */
require_once __DIR__ . '/../config.php';
if (!headers_sent()) {
    header('Content-Type: application/json');
}

if (!is_logged_in()) {
    echo json_encode(['error' => 'Please sign in to add to wishlist.']);
    exit;
}

$userId = current_user()['id'];
$bookId = (int)($_POST['book_id'] ?? 0);

if ($bookId <= 0) {
    echo json_encode(['error' => 'Invalid book.']);
    exit;
}

$exists = db_fetch_one("SELECT id FROM wishlists WHERE user_id = ? AND book_id = ?", [$userId, $bookId], "ii");

if ($exists) {
    db_execute("DELETE FROM wishlists WHERE id = ?", [$exists['id']], "i");
    echo json_encode(['status' => 'removed', 'message' => 'Removed from wishlist.']);
} else {
    db_execute("INSERT INTO wishlists (user_id, book_id) VALUES (?, ?)", [$userId, $bookId], "ii");
    echo json_encode(['status' => 'added', 'message' => 'Added to wishlist.']);
}
