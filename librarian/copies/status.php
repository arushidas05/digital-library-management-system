<?php
/**
 * Librarian: Quick Physical Copy Status Changer
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$copyId = (int)($_GET['id'] ?? 0);
if ($copyId <= 0) {
    header("Location: " . BASE_URL . "/librarian/copies/index.php");
    exit;
}

$copy = db_fetch_one("SELECT bc.*, b.title FROM book_copies bc JOIN books b ON bc.book_id = b.id WHERE bc.id = ?", [$copyId], "i");
if (!$copy) {
    set_flash('danger', 'Copy not found.');
    header("Location: " . BASE_URL . "/librarian/copies/index.php");
    exit;
}

$pageTitle = 'Update Status: ' . clean_output($copy['copy_number']) . ' - OpenShelf';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = trim($_POST['status'] ?? 'Available');
    $condition = trim($_POST['condition_notes'] ?? '');

    $allowed = ['Available', 'Borrowed', 'Reserved', 'Maintenance', 'Damaged', 'Lost'];
    if (in_array($newStatus, $allowed, true)) {
        db_execute("UPDATE book_copies SET status = ?, condition_notes = ? WHERE id = ?", [$newStatus, $condition, $copyId], "ssi");
        log_audit('COPY_STATUS_CHANGED', "Changed copy #{$copyId} status to {$newStatus}");
        set_flash('success', "Copy {$copy['copy_number']} status changed to '{$newStatus}'.");
        header("Location: " . BASE_URL . "/librarian/copies/index.php");
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-rotate text-warning me-2"></i> Update Physical Copy Status</h3>
        <p class="text-muted text-sm mb-0">Book: <strong><?= clean_output($copy['title']) ?></strong> (Barcode: <code><?= clean_output($copy['barcode']) ?></code>)</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/copies/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Copies
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 600px;">
      <form action="<?= BASE_URL ?>/librarian/copies/status.php?id=<?= $copyId ?>" method="POST">
        <div class="mb-3">
          <label for="status" class="form-label text-sm fw-semibold">Inventory Status <span class="text-danger">*</span></label>
          <select class="form-select" id="status" name="status" required>
            <option value="Available" <?= $copy['status'] === 'Available' ? 'selected' : '' ?>>Available on Shelf</option>
            <option value="Borrowed" <?= $copy['status'] === 'Borrowed' ? 'selected' : '' ?>>Borrowed (Circulation)</option>
            <option value="Reserved" <?= $copy['status'] === 'Reserved' ? 'selected' : '' ?>>Reserved (Hold for Student)</option>
            <option value="Maintenance" <?= $copy['status'] === 'Maintenance' ? 'selected' : '' ?>>Under Maintenance / Repair</option>
            <option value="Damaged" <?= $copy['status'] === 'Damaged' ? 'selected' : '' ?>>Damaged / Unusable</option>
            <option value="Lost" <?= $copy['status'] === 'Lost' ? 'selected' : '' ?>>Reported Lost</option>
          </select>
        </div>

        <div class="mb-4">
          <label for="condition_notes" class="form-label text-sm fw-semibold">Condition Notes</label>
          <textarea class="form-control" id="condition_notes" name="condition_notes" rows="3"><?= clean_output($copy['condition_notes']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-rounded px-4 py-2 fw-bold">
          <i class="fas fa-check me-1"></i> Update Status
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
