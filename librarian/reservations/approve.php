<?php
/**
 * Librarian: Fulfill Reservation (Issue Reserved Copy to Member)
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$resId = (int)($_GET['id'] ?? 0);
if ($resId <= 0) {
    header("Location: " . BASE_URL . "/librarian/reservations/index.php");
    exit;
}

$reservation = db_fetch_one(
    "SELECT r.*, b.title, u.id as member_id, u.username, p.full_name
     FROM reservations r
     JOIN books b ON r.book_id = b.id
     JOIN users u ON r.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE r.id = ? AND r.status IN ('Pending', 'Ready')",
    [$resId],
    "i"
);

if (!$reservation) {
    set_flash('danger', 'Active reservation not found.');
    header("Location: " . BASE_URL . "/librarian/reservations/index.php");
    exit;
}

// Find an available physical copy for this book
$availableCopy = db_fetch_one(
    "SELECT * FROM book_copies WHERE book_id = ? AND status = 'Available' ORDER BY copy_number ASC LIMIT 1",
    [$reservation['book_id']],
    "i"
);

if (!$availableCopy) {
    set_flash('danger', "Cannot fulfill reservation yet: no physical copy of '{$reservation['title']}' is currently available in the stacks.");
    header("Location: " . BASE_URL . "/librarian/reservations/index.php");
    exit;
}

$currentLibrarian = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrowDays = (int)get_setting('max_borrow_days', 14);
    $issueDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime("+{$borrowDays} days"));

    // 1. Mark copy Borrowed
    db_execute("UPDATE book_copies SET status = 'Borrowed' WHERE id = ?", [$availableCopy['id']], "i");

    // 2. Insert borrowing transaction
    db_execute(
        "INSERT INTO borrowings (user_id, book_copy_id, issue_date, due_date, status, issued_by, remarks) VALUES (?, ?, ?, ?, 'Issued', ?, 'Fulfilled from reservation hold')",
        [$reservation['member_id'], $availableCopy['id'], $issueDate, $dueDate, $currentLibrarian['id']],
        "iissi"
    );

    // 3. Mark reservation Completed
    db_execute("UPDATE reservations SET status = 'Completed' WHERE id = ?", [$resId], "i");

    // 4. Notify member
    send_notification(
        $reservation['member_id'],
        'Reservation Collected: ' . $reservation['title'],
        "You have collected your reserved copy (Barcode: {$availableCopy['barcode']}). Due date is " . format_date($dueDate) . ".",
        'general',
        'borrowed-books.php'
    );

    log_audit('RESERVATION_FULFILLED', "Fulfilled reservation #{$resId} for {$reservation['username']} on '{$reservation['title']}'");
    set_flash('success', "Reservation fulfilled! Copy {$availableCopy['barcode']} successfully issued to {$reservation['username']}.");
    header("Location: " . BASE_URL . "/librarian/borrowing/index.php");
    exit;
}

$pageTitle = 'Fulfill Hold: ' . clean_output($reservation['title']) . ' - OpenShelf';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-hand-holding-hand text-success me-2"></i> Fulfill Reservation Hold</h3>
        <p class="text-muted text-sm mb-0">Hand over reserved physical copy to the member</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/reservations/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Queue
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 650px;">
      <h5 class="fw-bold mb-3 text-dark">Confirm Hand-Over Details</h5>

      <div class="p-3 bg-light rounded-3 border mb-4">
        <div class="row g-2 text-sm">
          <div class="col-sm-4 text-muted">Book Title:</div>
          <div class="col-sm-8 fw-bold text-dark"><?= clean_output($reservation['title']) ?></div>

          <div class="col-sm-4 text-muted">Member Name:</div>
          <div class="col-sm-8 fw-bold text-dark"><?= clean_output($reservation['full_name'] ?: $reservation['username']) ?> (@<?= clean_output($reservation['username']) ?>)</div>

          <div class="col-sm-4 text-muted">Assigned Copy:</div>
          <div class="col-sm-8 text-primary fw-bold"><code><?= clean_output($availableCopy['barcode']) ?></code> (<?= clean_output($availableCopy['copy_number']) ?>)</div>

          <div class="col-sm-4 text-muted">Shelf Location:</div>
          <div class="col-sm-8"><?= clean_output($availableCopy['shelf_location']) ?></div>

          <div class="col-sm-4 text-muted">Loan Duration:</div>
          <div class="col-sm-8 fw-semibold text-dark"><?= (int)get_setting('max_borrow_days', 14) ?> Days (Due: <?= date('M d, Y', strtotime('+' . get_setting('max_borrow_days', 14) . ' days')) ?>)</div>
        </div>
      </div>

      <form action="<?= BASE_URL ?>/librarian/reservations/approve.php?id=<?= $resId ?>" method="POST">
        <button type="submit" class="btn btn-success btn-rounded px-4 py-2 fw-bold text-white shadow-sm">
          <i class="fas fa-check-circle me-1"></i> Confirm Issue & Complete Hold
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
