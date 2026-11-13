<?php
/**
 * Admin: Delete User
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0 || $userId === (int)$_SESSION['user_id']) {
    set_flash('danger', 'Cannot delete this user account.');
    header("Location: " . BASE_URL . "/admin/users/index.php");
    exit;
}

// Check active borrowings
$activeLoans = db_count('borrowings', "user_id = ? AND status IN ('Issued', 'Overdue')", [$userId], "i");
if ($activeLoans > 0) {
    set_flash('danger', 'Cannot delete user: member has active borrowed books in their possession.');
    header("Location: " . BASE_URL . "/admin/users/index.php");
    exit;
}

$u = db_fetch_one("SELECT username FROM users WHERE id = ?", [$userId], "i");
if ($u) {
    db_execute("DELETE FROM users WHERE id = ?", [$userId], "i");
    log_audit('USER_DELETED', "Admin deleted user #{$userId} (@{$u['username']})");
    set_flash('success', "User account '{$u['username']}' has been permanently deleted.");
}

header("Location: " . BASE_URL . "/admin/users/index.php");
exit;
