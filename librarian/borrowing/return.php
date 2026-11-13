<?php
/**
 * Librarian: Return Physical Book & Automated Reservation Activation
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Return Book Check-In - OpenShelf';
$currentLibrarian = current_user();

$scannedBarcode = trim($_GET['barcode'] ?? '');
$activeLoan = null;
$calculatedFine = ['days' => 0, 'fine' => 0.00];

if (!empty($scannedBarcode)) {
    $activeLoan = db_fetch_one(
        "SELECT br.*, bc.id as copy_id, bc.copy_number, bc.barcode, bc.shelf_location, bc.book_id,
                b.title, b.isbn, b.cover_image,
                u.id as member_id, u.username, p.full_name, p.student_id, p.phone
         FROM borrowings br
         JOIN book_copies bc ON br.book_copy_id = bc.id
         JOIN books b ON bc.book_id = b.id
         JOIN users u ON br.user_id = u.id
         LEFT JOIN user_profiles p ON u.id = p.user_id
         WHERE bc.barcode = ? AND br.status IN ('Issued', 'Overdue')
         ORDER BY br.id DESC LIMIT 1",
        [$scannedBarcode],
        "s"
    );

    if ($activeLoan) {
        $calculatedFine = calculate_overdue_fine($activeLoan['due_date']);
    } else {
        set_flash('warning', "No active loan found for barcode: " . htmlspecialchars($scannedBarcode) . ". The copy may already have been returned.");
    }
}

// Handle Return Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_return') {
    $borrowingId = (int)($_POST['borrowing_id'] ?? 0);
    $fineSettlement = trim($_POST['fine_settlement'] ?? 'none'); // 'paid_now', 'unpaid', 'waived', 'none'
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
    $conditionNotes = trim($_POST['condition_notes'] ?? 'Good condition');

    $loan = db_fetch_one(
        "SELECT br.*, bc.id as copy_id, bc.book_id, bc.barcode, b.title, u.id as member_id, u.username
         FROM borrowings br
         JOIN book_copies bc ON br.book_copy_id = bc.id
         JOIN books b ON bc.book_id = b.id
         JOIN users u ON br.user_id = u.id
         WHERE br.id = ? AND br.status IN ('Issued', 'Overdue')",
        [$borrowingId],
        "i"
    );

    if (!$loan) {
        set_flash('danger', 'Active loan record not found.');
        header("Location: " . BASE_URL . "/librarian/borrowing/return.php");
        exit;
    }

    $calc = calculate_overdue_fine($loan['due_date']);
    $fineAmount = $calc['fine'];
    $today = date('Y-m-d');

    // 1. Update borrowing record
    db_execute(
        "UPDATE borrowings SET status = 'Returned', return_date = ?, received_by = ? WHERE id = ?",
        [$today, $currentLibrarian['id'], $borrowingId],
        "sii"
    );

    // 2. Handle Fine
    if ($fineAmount > 0) {
        $fineStatus = match($fineSettlement) {
            'paid_now' => 'Paid',
            'waived' => 'Waived',
            default => 'Unpaid'
        };
        $paidAmount = ($fineStatus === 'Paid') ? $fineAmount : 0.00;
        $receiptNo = ($fineStatus === 'Paid') ? 'REC-' . date('Ymd') . '-' . rand(1000, 9999) : null;
        $paidDate = ($fineStatus === 'Paid') ? $today : null;

        // Check if fine row exists
        $existingFine = db_fetch_one("SELECT id FROM fines WHERE borrowing_id = ?", [$borrowingId], "i");
        if ($existingFine) {
            db_execute(
                "UPDATE fines SET fine_amount = ?, paid_amount = ?, status = ?, fine_date = ?, paid_date = ?, payment_method = ?, receipt_no = ? WHERE id = ?",
                [$fineAmount, $paidAmount, $fineStatus, $today, $paidDate, $paymentMethod, $receiptNo, $existingFine['id']],
                "ddsssssi"
            );
        } else {
            db_execute(
                "INSERT INTO fines (borrowing_id, user_id, fine_amount, paid_amount, status, fine_date, paid_date, payment_method, receipt_no, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Overdue return fine')",
                [$borrowingId, $loan['member_id'], $fineAmount, $paidAmount, $fineStatus, $today, $paidDate, $paymentMethod, $receiptNo],
                "iidssssss"
            );
        }

        if ($fineStatus === 'Paid') {
            send_notification(
                $loan['member_id'],
                'Fine Payment Received',
                "Your overdue fine of " . format_currency($fineAmount) . " for '{$loan['title']}' was paid (Receipt: {$receiptNo}).",
                'fine',
                'borrowed-books.php'
            );
        }
    }

    // 3. Mark copy Available
    db_execute("UPDATE book_copies SET status = 'Available', condition_notes = ? WHERE id = ?", [$conditionNotes, $loan['copy_id']], "si");

    // 4. Send Return Notification to Member
    send_notification(
        $loan['member_id'],
        'Book Return Confirmed: ' . $loan['title'],
        "Your returned copy of '{$loan['title']}' (Barcode: {$loan['barcode']}) has been checked in by the librarian.",
        'general',
        'borrowed-books.php'
    );

    // 5. CORE WORKFLOW 6.2: Auto-activate next reservation if pending!
    $activatedRes = activate_next_reservation($loan['book_id']);

    log_audit('BOOK_RETURNED', "Checked in copy #{$loan['copy_id']} ({$loan['barcode']}) from User #{$loan['member_id']}. Fine: {$fineAmount}");

    if ($activatedRes) {
        set_flash('success', "Book '{$loan['title']}' returned successfully! Alert: A pending reservation was automatically activated for member {$activatedRes['username']}!");
    } else {
        set_flash('success', "Book '{$loan['title']}' returned successfully! Copy is now Available on shelf.");
    }

    if (!headers_sent()) {
        header("Location: " . BASE_URL . "/librarian/borrowing/index.php");
        exit;
    }
}

// Fetch all active borrowings to populate quick select
$activeLoansList = db_fetch_all(
    "SELECT br.id, br.due_date, bc.barcode, bc.copy_number, b.title, u.username, p.full_name
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     JOIN users u ON br.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE br.status IN ('Issued', 'Overdue')
     ORDER BY br.due_date ASC"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-arrow-rotate-left text-primary me-2"></i> Receive Book Return Check-In</h3>
        <p class="text-muted text-sm mb-0">Scan barcode, compute overdue fees, mark copy available, and trigger hold notifications</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/borrowing/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Desk
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Step 1: Scan / Enter Barcode Card -->
    <div class="card p-4 border shadow-sm mb-4" style="max-width: 760px;">
      <h5 class="fw-bold mb-3"><i class="fas fa-barcode text-primary me-2"></i> Scan Barcode or Select Active Loan</h5>
      <form action="<?= BASE_URL ?>/librarian/borrowing/return.php" method="GET" class="row g-3 mb-3">
        <div class="col-md-8">
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="fas fa-qrcode text-muted"></i></span>
            <input type="text" class="form-control" name="barcode" value="<?= clean_output($scannedBarcode) ?>" placeholder="Scan or enter copy barcode (e.g. BAR-1005-01)..." required autofocus>
            <button class="btn btn-primary" type="submit">Lookup</button>
          </div>
        </div>
        <div class="col-md-4">
          <select class="form-select" onchange="if(this.value) window.location.href='<?= BASE_URL ?>/librarian/borrowing/return.php?barcode=' + encodeURIComponent(this.value)">
            <option value="">Quick Pick Active Loan...</option>
            <?php foreach ($activeLoansList as $al): ?>
              <option value="<?= $al['barcode'] ?>" <?= $scannedBarcode === $al['barcode'] ? 'selected' : '' ?>>
                <?= clean_output($al['barcode']) ?> &bull; <?= clean_output($al['title']) ?> (<?= clean_output($al['full_name'] ?: $al['username']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>

    <!-- Step 2: Return Transaction Details & Settlement -->
    <?php if ($activeLoan): ?>
      <?php
        $due = new DateTime($activeLoan['due_date']);
        $today = new DateTime(date('Y-m-d'));
        $isOverdue = ($today > $due);
        $diff = $today->diff($due);
      ?>
      <div class="card p-4 border shadow-sm" style="max-width: 760px;">
        <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-clipboard-check text-success me-2"></i> Confirm Return Check-In</h5>

        <div class="p-3 bg-light rounded-3 border mb-4">
          <div class="row g-3">
            <div class="col-md-8">
              <h5 class="fw-bold mb-1"><?= clean_output($activeLoan['title']) ?></h5>
              <div class="text-sm text-muted mb-2">Barcode: <code><?= clean_output($activeLoan['barcode']) ?></code> &bull; Copy: <?= clean_output($activeLoan['copy_number']) ?></div>
              <div class="text-sm">
                Borrower: <strong><?= clean_output($activeLoan['full_name'] ?: $activeLoan['username']) ?></strong>
                <span class="text-muted text-xs ms-2">(Student ID: <?= clean_output($activeLoan['student_id'] ?: 'N/A') ?>)</span>
              </div>
              <div class="text-sm text-muted">Issued: <?= format_date($activeLoan['issue_date']) ?> &bull; Due: <strong><?= format_date($activeLoan['due_date']) ?></strong></div>
            </div>
            <div class="col-md-4 text-md-end">
              <?php if ($isOverdue): ?>
                <span class="badge badge-overdue fs-6 mb-2 d-inline-block">
                  <i class="fas fa-triangle-exclamation me-1"></i> <?= $diff->days ?> Days Overdue
                </span>
                <div class="text-danger fw-bold fs-5">
                  Fine: <?= format_currency($calculatedFine['fine']) ?>
                </div>
                <small class="text-muted text-xs"><?= format_currency(get_setting('fine_per_day', 5)) ?> / day</small>
              <?php else: ?>
                <span class="badge badge-available fs-6 mb-2 d-inline-block">
                  <i class="fas fa-circle-check me-1"></i> Returned on Time
                </span>
                <div class="text-success fw-bold">No Late Fees</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <form action="<?= BASE_URL ?>/librarian/borrowing/return.php" method="POST">
          <input type="hidden" name="action" value="process_return">
          <input type="hidden" name="borrowing_id" value="<?= $activeLoan['id'] ?>">

          <?php if ($calculatedFine['fine'] > 0): ?>
            <!-- Fine Settlement Options -->
            <div class="card p-3 border border-warning bg-warning bg-opacity-10 mb-4">
              <h6 class="fw-bold text-dark mb-2"><i class="fas fa-receipt text-warning me-2"></i> Overdue Fine Settlement</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label text-xs fw-bold text-uppercase text-muted">Settlement Action</label>
                  <select class="form-select form-select-sm" name="fine_settlement" id="fineSettlementSelect" onchange="document.getElementById('paymentMethodBox').style.display = (this.value === 'paid_now') ? 'block' : 'none';">
                    <option value="paid_now" selected>Collect & Settle Payment Now (<?= format_currency($calculatedFine['fine']) ?>)</option>
                    <option value="unpaid">Record as Unpaid Balance on Student Account</option>
                    <option value="waived">Waive Fine (Administrative Exemption)</option>
                  </select>
                </div>
                <div class="col-md-6" id="paymentMethodBox">
                  <label class="form-label text-xs fw-bold text-uppercase text-muted">Payment Tender Method</label>
                  <select class="form-select form-select-sm" name="payment_method">
                    <option value="Cash" selected>Cash Payment at Desk</option>
                    <option value="UPI / QR">UPI / QR Code</option>
                    <option value="Credit / Debit Card">Credit / Debit Card</option>
                    <option value="Online Portal">Online Student Account</option>
                  </select>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="mb-4">
            <label for="condition_notes" class="form-label text-sm fw-semibold">Physical Condition Check on Return</label>
            <input type="text" class="form-control" id="condition_notes" name="condition_notes" value="Returned in good condition" required>
          </div>

          <button type="submit" class="btn btn-primary btn-rounded px-4 py-2 fw-bold shadow-sm">
            <i class="fas fa-arrow-rotate-left me-1"></i> Complete Return & Check In
          </button>
        </form>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
