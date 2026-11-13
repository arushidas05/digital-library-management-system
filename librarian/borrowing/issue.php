<?php
/**
 * Librarian: Issue Physical Book to Member
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Issue Physical Book - OpenShelf';
$currentLibrarian = current_user();

$preselectedBarcode = trim($_GET['barcode'] ?? '');
$preselectedUserId = (int)($_GET['user_id'] ?? 0);

// Fetch active members (Students)
$members = db_fetch_all(
    "SELECT u.id, u.username, u.email, p.full_name, p.student_id,
            (SELECT COUNT(*) FROM borrowings br WHERE br.user_id = u.id AND br.status IN ('Issued', 'Overdue')) as active_loans_count
     FROM users u
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE u.status = 'active'
     ORDER BY p.full_name ASC, u.username ASC"
);

// Fetch all available physical copies
$availableCopies = db_fetch_all(
    "SELECT bc.*, b.title, b.isbn
     FROM book_copies bc
     JOIN books b ON bc.book_id = b.id
     WHERE bc.status = 'Available'
     ORDER BY b.title ASC, bc.copy_number ASC"
);

// Form submission handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $copyId = (int)($_POST['copy_id'] ?? 0);
    $issueDate = trim($_POST['issue_date'] ?? date('Y-m-d'));
    $dueDate = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days')));
    $remarks = trim($_POST['remarks'] ?? '');

    if ($userId <= 0 || $copyId <= 0 || empty($dueDate)) {
        set_flash('danger', 'Please select a member, an available book copy, and a valid due date.');
    } else {
        // Check member active loans count against max books per member limit
        $maxLimit = (int)get_setting('max_books_per_member', 3);
        $activeLoans = db_count('borrowings', "user_id = ? AND status IN ('Issued', 'Overdue')", [$userId], "i");

        if ($activeLoans >= $maxLimit) {
            set_flash('danger', "Member has reached the maximum borrowing limit of {$maxLimit} concurrent books.");
        } else {
            // Verify copy is currently available
            $copy = db_fetch_one("SELECT bc.*, b.title FROM book_copies bc JOIN books b ON bc.book_id = b.id WHERE bc.id = ?", [$copyId], "i");
            if (!$copy || $copy['status'] !== 'Available') {
                set_flash('danger', 'This physical copy is not available (it may have just been issued or under maintenance).');
            } else {
                // Begin transaction-like sequence
                // 1. Update copy status to 'Borrowed'
                db_execute("UPDATE book_copies SET status = 'Borrowed' WHERE id = ?", [$copyId], "i");

                // 2. Insert borrowing record
                db_execute(
                    "INSERT INTO borrowings (user_id, book_copy_id, issue_date, due_date, status, issued_by, remarks) VALUES (?, ?, ?, ?, 'Issued', ?, ?)",
                    [$userId, $copyId, $issueDate, $dueDate, $currentLibrarian['id'], $remarks],
                    "iissis"
                );
                $borrowingId = db_last_id();

                // 3. Send in-app notification to member
                send_notification(
                    $userId,
                    'Book Issued: ' . $copy['title'],
                    "Copy #{$copy['copy_number']} (Barcode: {$copy['barcode']}) has been issued to you. Due date is " . format_date($dueDate) . ".",
                    'general',
                    'borrowed-books.php'
                );

                log_audit('BOOK_ISSUED', "Issued copy #{$copyId} ({$copy['barcode']}) of '{$copy['title']}' to User #{$userId}. Due: {$dueDate}");

                set_flash('success', "Book '{$copy['title']}' (Barcode: {$copy['barcode']}) successfully issued! Due date: " . format_date($dueDate));
                header("Location: " . BASE_URL . "/librarian/borrowing/index.php");
                exit;
            }
        }
    }
}

$standardBorrowDays = (int)get_setting('max_borrow_days', 14);
$defaultDueDate = date('Y-m-d', strtotime("+{$standardBorrowDays} days"));

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-plus-circle text-success me-2"></i> Issue Book to Member</h3>
        <p class="text-muted text-sm mb-0">Record physical circulation checkout, assign barcode, and establish loan due date</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/borrowing/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Desk
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 720px;">
      <form action="<?= BASE_URL ?>/librarian/borrowing/issue.php" method="POST">
        <!-- Step 1: Select Member -->
        <div class="mb-4">
          <label for="user_id" class="form-label text-sm fw-semibold">1. Select Borrower / Member <span class="text-danger">*</span></label>
          <select class="form-select" id="user_id" name="user_id" required>
            <option value="">Choose member...</option>
            <?php foreach ($members as $m): ?>
              <option value="<?= $m['id'] ?>" <?= $preselectedUserId === (int)$m['id'] ? 'selected' : '' ?>>
                <?= clean_output($m['full_name'] ?: $m['username']) ?> 
                (ID: <?= clean_output($m['student_id'] ?: 'N/A') ?> &bull; Active Loans: <?= $m['active_loans_count'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Step 2: Select Available Copy -->
        <div class="mb-4">
          <label for="copy_id" class="form-label text-sm fw-semibold">2. Select Available Book Copy & Barcode <span class="text-danger">*</span></label>
          <?php if (empty($availableCopies)): ?>
            <div class="alert alert-warning mb-0 text-sm">
              <i class="fas fa-exclamation-triangle me-1"></i> No physical copies are currently marked as 'Available' in the library inventory.
            </div>
          <?php else: ?>
            <select class="form-select" id="copy_id" name="copy_id" required>
              <option value="">Select an available physical copy...</option>
              <?php foreach ($availableCopies as $ac): ?>
                <option value="<?= $ac['id'] ?>" <?= ($preselectedBarcode === $ac['barcode']) ? 'selected' : '' ?>>
                  <?= clean_output($ac['title']) ?> &mdash; Barcode: [<?= clean_output($ac['barcode']) ?>] &bull; <?= clean_output($ac['shelf_location']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>

        <!-- Step 3: Dates -->
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="issue_date" class="form-label text-sm fw-semibold">Issue Date</label>
            <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-6">
            <label for="due_date" class="form-label text-sm fw-semibold">Due Date (<?= $standardBorrowDays ?> Days Standard)</label>
            <input type="date" class="form-control" id="due_date" name="due_date" value="<?= $defaultDueDate ?>" required>
          </div>
        </div>

        <div class="mb-4">
          <label for="remarks" class="form-label text-sm fw-semibold">Circulation Remarks</label>
          <input type="text" class="form-control" id="remarks" name="remarks" placeholder="Optional notes (e.g. academic project checkout, thesis reading)">
        </div>

        <button type="submit" class="btn btn-success btn-rounded px-4 py-2 fw-bold text-white shadow-sm" <?= empty($availableCopies) ? 'disabled' : '' ?>>
          <i class="fas fa-check-circle me-1"></i> Confirm & Issue Book
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
