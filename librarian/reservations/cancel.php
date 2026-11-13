<?php
/**
 * Librarian: Cancel Reservation
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$resId = (int)($_GET['id'] ?? 0);
if ($resId <= 0) {
    header("Location: " . BASE_URL . "/librarian/reservations/index.php");
    exit;
}

$res = db_fetch_one("SELECT * FROM reservations WHERE id = ?", [$resId], "i");
if ($res && in_array($res['status'], ['Pending', 'Ready'])) {
    db_execute("UPDATE reservations SET status = 'Cancelled' WHERE id = ?", [$resId], "i");
    log_audit('RESERVATION_CANCELLED', "Librarian cancelled reservation #{$resId}");
    set_flash('info', 'Reservation has been cancelled.');
}

header("Location: " . BASE_URL . "/librarian/reservations/index.php");
exit;
