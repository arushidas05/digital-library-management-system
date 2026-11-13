<?php
/**
 * Librarian: Archive / Delete Book
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$bookId = (int)($_GET['id'] ?? 0);
if ($bookId <= 0) {
    header("Location: " . BASE_URL . "/librarian/books/index.php");
    exit;
}

$book = db_fetch_one("SELECT * FROM books WHERE id = ?", [$bookId], "i");
if ($book) {
    // Check if any active borrowings exist
    $activeLoans = db_fetch_one(
        "SELECT COUNT(*) as cnt FROM borrowings br JOIN book_copies bc ON br.book_copy_id = bc.id WHERE bc.book_id = ? AND br.status IN ('Issued', 'Overdue')",
        [$bookId],
        "i"
    );

    if ($activeLoans && $activeLoans['cnt'] > 0) {
        set_flash('danger', 'Cannot archive this title: active physical copies are currently on loan to members. Process returns first.');
    } else {
        db_execute("UPDATE books SET status = 'archived' WHERE id = ?", [$bookId], "i");
        log_audit('BOOK_ARCHIVED', "Archived book #{$bookId}: {$book['title']}");
        set_flash('success', "Book '{$book['title']}' has been archived.");
    }
}

header("Location: " . BASE_URL . "/librarian/books/index.php");
exit;
