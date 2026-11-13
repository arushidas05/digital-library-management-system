<?php
/**
 * Librarian: Edit Physical Copy
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

$pageTitle = 'Edit Copy: ' . clean_output($copy['copy_number']) . ' - OpenShelf';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $copyNumber = trim($_POST['copy_number'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $shelfLocation = trim($_POST['shelf_location'] ?? '');
    $condition = trim($_POST['condition_notes'] ?? '');

    if (empty($copyNumber) || empty($barcode) || empty($shelfLocation)) {
        set_flash('danger', 'Copy number, barcode, and shelf location are required.');
    } else {
        db_execute(
            "UPDATE book_copies SET copy_number = ?, barcode = ?, shelf_location = ?, condition_notes = ? WHERE id = ?",
            [$copyNumber, $barcode, $shelfLocation, $condition, $copyId],
            "ssssi"
        );
        log_audit('COPY_UPDATED', "Updated copy #{$copyId}: {$copyNumber}");
        set_flash('success', 'Physical copy details updated successfully!');
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
        <h3 class="fw-bold mb-1"><i class="fas fa-pen text-primary me-2"></i> Edit Copy Details</h3>
        <p class="text-muted text-sm mb-0">Book: <strong><?= clean_output($copy['title']) ?></strong></p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/copies/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Copies
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 680px;">
      <form action="<?= BASE_URL ?>/librarian/copies/edit.php?id=<?= $copyId ?>" method="POST">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="copy_number" class="form-label text-sm fw-semibold">Copy Code</label>
            <input type="text" class="form-control" id="copy_number" name="copy_number" value="<?= clean_output($copy['copy_number']) ?>" required>
          </div>
          <div class="col-md-6">
            <label for="barcode" class="form-label text-sm fw-semibold">Barcode</label>
            <input type="text" class="form-control" id="barcode" name="barcode" value="<?= clean_output($copy['barcode']) ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label for="shelf_location" class="form-label text-sm fw-semibold">Shelf Location</label>
          <input type="text" class="form-control" id="shelf_location" name="shelf_location" value="<?= clean_output($copy['shelf_location']) ?>" required>
        </div>

        <div class="mb-4">
          <label for="condition_notes" class="form-label text-sm fw-semibold">Condition Notes</label>
          <input type="text" class="form-control" id="condition_notes" name="condition_notes" value="<?= clean_output($copy['condition_notes']) ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-rounded px-4 py-2 fw-bold">
          <i class="fas fa-save me-1"></i> Save Changes
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
