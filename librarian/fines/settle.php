<?php
/**
 * Librarian: Settle / Collect Overdue Fine
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$fineId = (int)($_GET['id'] ?? 0);
if ($fineId <= 0) {
    header("Location: " . BASE_URL . "/librarian/fines/index.php");
    exit;
}

$fine = db_fetch_one(
    "SELECT f.*, b.title, bc.barcode, u.username, p.full_name, p.student_id
     FROM fines f
     JOIN borrowings br ON f.borrowing_id = br.id
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     JOIN users u ON f.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE f.id = ?",
    [$fineId],
    "i"
);

if (!$fine || $fine['status'] === 'Paid') {
    set_flash('info', 'This fine is already settled.');
    header("Location: " . BASE_URL . "/librarian/fines/index.php");
    exit;
}

$pageTitle = 'Settle Fine: ' . clean_output($fine['full_name'] ?: $fine['username']) . ' - OpenShelf';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = trim($_POST['action_type'] ?? 'pay'); // 'pay', 'waive'
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
    $notes = trim($_POST['notes'] ?? '');

    $today = date('Y-m-d');
    if ($actionType === 'waive') {
        db_execute(
            "UPDATE fines SET status = 'Waived', notes = ?, paid_date = ? WHERE id = ?",
            [$notes ?: 'Waived by authorized staff', $today, $fineId],
            "ssi"
        );
        log_audit('FINE_WAIVED', "Waived fine #{$fineId} of {$fine['fine_amount']} for User #{$fine['user_id']}");
        set_flash('info', 'Fine has been officially waived.');
    } else {
        $receiptNo = 'REC-' . date('Ymd') . '-' . rand(1000, 9999);
        db_execute(
            "UPDATE fines SET status = 'Paid', paid_amount = fine_amount, payment_method = ?, receipt_no = ?, paid_date = ?, notes = ? WHERE id = ?",
            [$paymentMethod, $receiptNo, $today, $notes, $fineId],
            "ssssi"
        );

        send_notification(
            $fine['user_id'],
            'Fine Payment Confirmed',
            "Your fine payment of " . format_currency($fine['fine_amount']) . " has been recorded. Receipt: {$receiptNo}.",
            'fine',
            'borrowed-books.php'
        );

        log_audit('FINE_COLLECTED', "Collected {$fine['fine_amount']} on fine #{$fineId} (Receipt: {$receiptNo})");
        set_flash('success', "Fine of " . format_currency($fine['fine_amount']) . " collected successfully! Receipt No: {$receiptNo}");
    }

    header("Location: " . BASE_URL . "/librarian/fines/index.php");
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-hand-holding-dollar text-success me-2"></i> Record Fine Settlement</h3>
        <p class="text-muted text-sm mb-0">Issue payment receipt and clear student library dues balance</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/fines/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Ledger
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 650px;">
      <div class="p-3 bg-light rounded-3 border mb-4">
        <div class="row g-2 text-sm">
          <div class="col-sm-4 text-muted">Member Name:</div>
          <div class="col-sm-8 fw-bold text-dark"><?= clean_output($fine['full_name'] ?: $fine['username']) ?> (@<?= clean_output($fine['username']) ?>)</div>

          <div class="col-sm-4 text-muted">Overdue Book:</div>
          <div class="col-sm-8 fw-bold text-dark"><?= clean_output($fine['title']) ?> (<code><?= clean_output($fine['barcode']) ?></code>)</div>

          <div class="col-sm-4 text-muted">Fine Amount:</div>
          <div class="col-sm-8 text-danger fw-bold fs-5"><?= format_currency($fine['fine_amount']) ?></div>

          <div class="col-sm-4 text-muted">Date Assessed:</div>
          <div class="col-sm-8"><?= format_date($fine['fine_date']) ?></div>
        </div>
      </div>

      <form action="<?= BASE_URL ?>/librarian/fines/settle.php?id=<?= $fineId ?>" method="POST">
        <div class="mb-3">
          <label class="form-label text-sm fw-semibold">Settlement Decision <span class="text-danger">*</span></label>
          <div class="d-flex gap-4">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="action_type" id="actPay" value="pay" checked onchange="document.getElementById('methodGroup').style.display='block';">
              <label class="form-check-label fw-semibold text-sm" for="actPay">Collect Payment in Full</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="action_type" id="actWaive" value="waive" onchange="document.getElementById('methodGroup').style.display='none';">
              <label class="form-check-label fw-semibold text-sm" for="actWaive">Waive / Forgive Fine</label>
            </div>
          </div>
        </div>

        <div class="mb-3" id="methodGroup">
          <label for="payment_method" class="form-label text-sm fw-semibold">Payment Method</label>
          <select class="form-select" id="payment_method" name="payment_method">
            <option value="Cash" selected>Cash Payment at Desk</option>
            <option value="UPI / QR">UPI / QR Code Transfer</option>
            <option value="Debit / Credit Card">Debit / Credit Card</option>
            <option value="Online Portal">Student Campus Account Deduction</option>
          </select>
        </div>

        <div class="mb-4">
          <label for="notes" class="form-label text-sm fw-semibold">Desk Remarks / Notes</label>
          <input type="text" class="form-control" id="notes" name="notes" placeholder="Optional settlement remark">
        </div>

        <button type="submit" class="btn btn-success btn-rounded px-4 py-2 fw-bold text-white shadow-sm">
          <i class="fas fa-check-circle me-1"></i> Confirm & Generate Receipt
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
