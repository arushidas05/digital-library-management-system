<?php
/**
 * Admin: System Settings Configuration
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$pageTitle = 'System Settings - OpenShelf';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libraryName = trim($_POST['library_name'] ?? 'OpenShelf Digital & Physical Library');
    $finePerDay = (float)($_POST['fine_per_day'] ?? 5.00);
    $currencySymbol = trim($_POST['currency_symbol'] ?? '$');
    $maxBorrowDays = (int)($_POST['max_borrow_days'] ?? 14);
    $maxBooksPerMember = (int)($_POST['max_books_per_member'] ?? 3);
    $reservationExpiryDays = (int)($_POST['reservation_expiry_days'] ?? 3);
    $enableOnlineReading = isset($_POST['enable_online_reading']) ? '1' : '0';

    update_setting('library_name', $libraryName, 'Library platform title');
    update_setting('fine_per_day', (string)$finePerDay, 'Daily overdue fine amount');
    update_setting('currency_symbol', $currencySymbol, 'Currency symbol for fines');
    update_setting('max_borrow_days', (string)$maxBorrowDays, 'Standard loan duration in days');
    update_setting('max_books_per_member', (string)$maxBooksPerMember, 'Maximum concurrent physical borrowings per student');
    update_setting('reservation_expiry_days', (string)$reservationExpiryDays, 'Hold pickup expiration window in days');
    update_setting('enable_online_reading', $enableOnlineReading, 'Global toggle for digital e-book reader');

    log_audit('SETTINGS_UPDATED', 'Admin modified system global settings');
    set_flash('success', 'System configuration saved successfully!');
    header("Location: " . BASE_URL . "/admin/settings/index.php");
    exit;
}

// Fetch current values
$currentSettings = [
    'library_name' => get_setting('library_name', 'OpenShelf Digital & Physical Library'),
    'fine_per_day' => get_setting('fine_per_day', '5.00'),
    'currency_symbol' => get_setting('currency_symbol', '$'),
    'max_borrow_days' => get_setting('max_borrow_days', '14'),
    'max_books_per_member' => get_setting('max_books_per_member', '3'),
    'reservation_expiry_days' => get_setting('reservation_expiry_days', '3'),
    'enable_online_reading' => get_setting('enable_online_reading', '1'),
];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-sliders text-primary me-2"></i> System Configuration & Policies</h3>
        <p class="text-muted text-sm mb-0">Fine policies, circulation windows, borrow limits, and platform rules</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 760px;">
      <form action="<?= BASE_URL ?>/admin/settings/index.php" method="POST">
        <h5 class="fw-bold mb-3 text-dark">Platform Identity</h5>
        <div class="mb-4">
          <label for="library_name" class="form-label text-sm fw-semibold">Library Title</label>
          <input type="text" class="form-control" id="library_name" name="library_name" value="<?= clean_output($currentSettings['library_name']) ?>" required>
        </div>

        <hr class="my-4">

        <h5 class="fw-bold mb-3 text-dark">Circulation & Borrowing Rules</h5>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="max_borrow_days" class="form-label text-sm fw-semibold">Standard Loan Period (Days)</label>
            <input type="number" class="form-control" id="max_borrow_days" name="max_borrow_days" value="<?= clean_output($currentSettings['max_borrow_days']) ?>" min="1" max="90" required>
            <div class="text-xs text-muted mt-1">Default number of days allocated before item is flagged overdue.</div>
          </div>
          <div class="col-md-6">
            <label for="max_books_per_member" class="form-label text-sm fw-semibold">Max Books per Member</label>
            <input type="number" class="form-control" id="max_books_per_member" name="max_books_per_member" value="<?= clean_output($currentSettings['max_books_per_member']) ?>" min="1" max="20" required>
            <div class="text-xs text-muted mt-1">Maximum concurrent physical borrowings a student can hold.</div>
          </div>
        </div>

        <hr class="my-4">

        <h5 class="fw-bold mb-3 text-dark">Overdue Fines & Penalties</h5>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="fine_per_day" class="form-label text-sm fw-semibold">Daily Fine Rate (Per Overdue Day)</label>
            <input type="number" step="0.25" class="form-control" id="fine_per_day" name="fine_per_day" value="<?= clean_output($currentSettings['fine_per_day']) ?>" required>
          </div>
          <div class="col-md-6">
            <label for="currency_symbol" class="form-label text-sm fw-semibold">Currency Symbol</label>
            <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?= clean_output($currentSettings['currency_symbol']) ?>" required>
          </div>
        </div>

        <hr class="my-4">

        <h5 class="fw-bold mb-3 text-dark">Reservations & Digital Access</h5>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="reservation_expiry_days" class="form-label text-sm fw-semibold">Hold Collection Window (Days)</label>
            <input type="number" class="form-control" id="reservation_expiry_days" name="reservation_expiry_days" value="<?= clean_output($currentSettings['reservation_expiry_days']) ?>" min="1" max="14" required>
            <div class="text-xs text-muted mt-1">Days reserved item is held for pickup once returned before passing to next person.</div>
          </div>
          <div class="col-md-6 d-flex align-items-center pt-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="enable_online_reading" name="enable_online_reading" value="1" <?= $currentSettings['enable_online_reading'] === '1' ? 'checked' : '' ?>>
              <label class="form-check-label text-sm fw-semibold" for="enable_online_reading">Enable In-Browser Digital Reading Globally</label>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-rounded px-4 py-2 fw-bold shadow">
          <i class="fas fa-save me-1"></i> Save Platform Policies
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
