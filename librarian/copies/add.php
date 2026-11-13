<?php
/**
 * Librarian: Add Physical Copies
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Add Physical Copies - OpenShelf';

$preselectedBookId = (int)($_GET['book_id'] ?? 0);
$books = db_fetch_all("SELECT id, title, isbn FROM books WHERE status = 'active' ORDER BY title ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    $copyNumber = trim($_POST['copy_number'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $shelfLocation = trim($_POST['shelf_location'] ?? '');
    $condition = trim($_POST['condition_notes'] ?? 'New Acquisition');

    if ($bookId <= 0 || empty($copyNumber) || empty($barcode) || empty($shelfLocation)) {
        set_flash('danger', 'Please provide book selection, copy number, unique barcode, and shelf location.');
    } else {
        // Check uniqueness of barcode and copy_number
        $existing = db_fetch_one("SELECT id FROM book_copies WHERE barcode = ? OR copy_number = ?", [$barcode, $copyNumber], "ss");
        if ($existing) {
            set_flash('danger', 'A copy with that barcode or copy number already exists. Barcodes must be globally unique.');
        } else {
            $insert = db_execute(
                "INSERT INTO book_copies (book_id, copy_number, barcode, shelf_location, status, condition_notes) VALUES (?, ?, ?, ?, 'Available', ?)",
                [$bookId, $copyNumber, $barcode, $shelfLocation, $condition],
                "issss"
            );

            if ($insert) {
                // Ensure is_physical is flagged on book
                db_execute("UPDATE books SET is_physical = 1 WHERE id = ?", [$bookId], "i");

                log_audit('COPY_ADDED', "Added copy {$copyNumber} (Barcode: {$barcode}) for book #{$bookId}");
                set_flash('success', "Physical copy '{$copyNumber}' with barcode '{$barcode}' registered successfully!");
                header("Location: " . BASE_URL . "/librarian/copies/index.php");
                exit;
            } else {
                set_flash('danger', 'Failed to register copy.');
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-barcode text-primary me-2"></i> Register Physical Book Copy</h3>
        <p class="text-muted text-sm mb-0">Assign a physical copy number, barcode identification, and shelf placement location</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/copies/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Copies
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 680px;">
      <form action="<?= BASE_URL ?>/librarian/copies/add.php" method="POST">
        <div class="mb-3">
          <label for="book_id" class="form-label text-sm fw-semibold">Target Book Title <span class="text-danger">*</span></label>
          <select class="form-select" id="book_id" name="book_id" required>
            <option value="">Select a book title...</option>
            <?php foreach ($books as $b): ?>
              <option value="<?= $b['id'] ?>" <?= $preselectedBookId === (int)$b['id'] ? 'selected' : '' ?>>
                <?= clean_output($b['title']) ?> (ISBN: <?= clean_output($b['isbn']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="copy_number" class="form-label text-sm fw-semibold">Copy Identification Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="copy_number" name="copy_number" placeholder="e.g. CP-101-04" required>
          </div>
          <div class="col-md-6">
            <label for="barcodeInput" class="form-label text-sm fw-semibold">Physical Barcode Label <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="text" class="form-control" id="barcodeInput" name="barcode" placeholder="e.g. BAR-908123" required>
              <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()">Generate</button>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label for="shelf_location" class="form-label text-sm fw-semibold">Campus Shelf / Stacks Location <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="shelf_location" name="shelf_location" placeholder="e.g. Floor 2, Section B, Rack 14" required>
        </div>

        <div class="mb-4">
          <label for="condition_notes" class="form-label text-sm fw-semibold">Physical Condition Notes</label>
          <input type="text" class="form-control" id="condition_notes" name="condition_notes" value="Pristine condition, fresh binding" placeholder="e.g. Pristine, slight cover wear, repaired spine">
        </div>

        <button type="submit" class="btn btn-primary btn-rounded px-4 py-2 fw-bold">
          <i class="fas fa-check-circle me-1"></i> Register Copy to Inventory
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
