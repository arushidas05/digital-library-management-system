<?php
/**
 * Librarian Operations Dashboard
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Librarian Desk - OpenShelf';
$user = current_user();

// Run overdue calculations routine
process_all_overdue_loans();

// Librarian KPIs
$totalTitles = db_count('books', "status = 'active'");
$totalCopies = db_count('book_copies');
$activeLoans = db_count('borrowings', "status = 'Issued'");
$overdueLoans = db_count('borrowings', "status = 'Overdue'");
$pendingReservations = db_count('reservations', "status IN ('Pending', 'Ready')");

$finesRow = db_fetch_one("SELECT SUM(fine_amount - paid_amount) as total_unpaid FROM fines WHERE status = 'Unpaid'");
$totalUnpaidFines = (float)($finesRow['total_unpaid'] ?? 0);

// Urgent Overdue Loans
$urgentOverdues = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, b.title, u.username, p.full_name, p.phone,
            f.fine_amount
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     JOIN users u ON br.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     LEFT JOIN fines f ON br.id = f.borrowing_id
     WHERE br.status = 'Overdue'
     ORDER BY br.due_date ASC LIMIT 5"
);

// Recent Borrowings / Returns
$recentBorrowings = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, b.title, b.cover_image, u.username, p.full_name
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     JOIN users u ON br.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     ORDER BY br.created_at DESC LIMIT 6"
);

// Pending Reservations
$reservationsQueue = db_fetch_all(
    "SELECT r.*, b.title, u.username, p.full_name
     FROM reservations r
     JOIN books b ON r.book_id = b.id
     JOIN users u ON r.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE r.status IN ('Pending', 'Ready')
     ORDER BY r.status DESC, r.queue_position ASC LIMIT 5"
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-book-bookmark text-primary me-2"></i> Librarian Circulation Desk</h3>
        <p class="text-muted text-sm mb-0">Circulation control, physical inventory, member loan authorizations, and returns</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/librarian/borrowing/issue.php" class="btn btn-success btn-sm btn-rounded fw-semibold">
          <i class="fas fa-plus-circle me-1"></i> Issue Book
        </a>
        <a href="<?= BASE_URL ?>/librarian/borrowing/return.php" class="btn btn-primary btn-sm btn-rounded fw-semibold">
          <i class="fas fa-arrow-rotate-left me-1"></i> Return Book
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Stat KPI Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card p-3">
          <div class="stat-icon primary" style="width:42px; height:42px; font-size:1.2rem;">
            <i class="fas fa-book"></i>
          </div>
          <div>
            <div class="stat-value fs-5"><?= $totalTitles ?></div>
            <div class="stat-label text-xs">Book Titles</div>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card p-3">
          <div class="stat-icon info" style="width:42px; height:42px; font-size:1.2rem;">
            <i class="fas fa-barcode"></i>
          </div>
          <div>
            <div class="stat-value fs-5"><?= $totalCopies ?></div>
            <div class="stat-label text-xs">Total Copies</div>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card p-3">
          <div class="stat-icon success" style="width:42px; height:42px; font-size:1.2rem;">
            <i class="fas fa-hand-holding-hand"></i>
          </div>
          <div>
            <div class="stat-value fs-5"><?= $activeLoans ?></div>
            <div class="stat-label text-xs">Active Loans</div>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card p-3">
          <div class="stat-icon <?= $overdueLoans > 0 ? 'danger' : 'success' ?>" style="width:42px; height:42px; font-size:1.2rem;">
            <i class="fas fa-triangle-exclamation"></i>
          </div>
          <div>
            <div class="stat-value fs-5"><?= $overdueLoans ?></div>
            <div class="stat-label text-xs">Overdue Items</div>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card p-3">
          <div class="stat-icon warning" style="width:42px; height:42px; font-size:1.2rem;">
            <i class="fas fa-clock-rotate-left"></i>
          </div>
          <div>
            <div class="stat-value fs-5"><?= $pendingReservations ?></div>
            <div class="stat-label text-xs">Holds Waiting</div>
          </div>
        </div>
      </div>
      <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card p-3">
          <div class="stat-icon danger" style="width:42px; height:42px; font-size:1.2rem;">
            <i class="fas fa-receipt"></i>
          </div>
          <div>
            <div class="stat-value fs-5"><?= format_currency($totalUnpaidFines) ?></div>
            <div class="stat-label text-xs">Unpaid Fines</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions Toolbar -->
    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span class="fw-bold text-sm text-muted text-uppercase" style="letter-spacing: 0.05em;">
          <i class="fas fa-bolt text-warning me-1"></i> Quick Desk Actions
        </span>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= BASE_URL ?>/librarian/borrowing/issue.php" class="btn btn-sm btn-outline-success">
            <i class="fas fa-user-check me-1"></i> Issue Book
          </a>
          <a href="<?= BASE_URL ?>/librarian/borrowing/return.php" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-inbox me-1"></i> Receive Return
          </a>
          <a href="<?= BASE_URL ?>/librarian/books/add.php" class="btn btn-sm btn-outline-dark">
            <i class="fas fa-plus me-1"></i> Catalog New Title
          </a>
          <a href="<?= BASE_URL ?>/librarian/copies/add.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-barcode me-1"></i> Add Physical Copies
          </a>
          <a href="<?= BASE_URL ?>/librarian/fines/index.php" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-receipt me-1"></i> Settle Fines
          </a>
          <a href="<?= BASE_URL ?>/librarian/reports/index.php" class="btn btn-sm btn-outline-info">
            <i class="fas fa-chart-column me-1"></i> View Reports
          </a>
        </div>
      </div>
    </div>

    <!-- Tables Grid -->
    <div class="row g-4 mb-4">
      <!-- Recent Circulation Activity -->
      <div class="col-lg-8">
        <div class="card shadow-sm border h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-hand-holding-hand text-primary me-2"></i> Recent Circulation Transactions</span>
            <a href="<?= BASE_URL ?>/librarian/borrowing/index.php" class="text-xs text-primary text-decoration-none">View All Loans</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-sm">
                <thead>
                  <tr>
                    <th>Borrower</th>
                    <th>Book</th>
                    <th>Barcode</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentBorrowings as $br): ?>
                    <tr>
                      <td class="fw-semibold">
                        <?= clean_output($br['full_name'] ?: $br['username']) ?>
                        <div class="text-xs text-muted">@<?= clean_output($br['username']) ?></div>
                      </td>
                      <td>
                        <a href="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $br['id'] ?>" class="text-dark fw-semibold text-decoration-none hover-primary">
                          <?= clean_output($br['title']) ?>
                        </a>
                      </td>
                      <td><code><?= clean_output($br['barcode']) ?></code></td>
                      <td><?= format_date($br['due_date']) ?></td>
                      <td>
                        <?php if ($br['status'] === 'Issued'): ?>
                          <span class="badge badge-borrowed">Issued</span>
                        <?php elseif ($br['status'] === 'Overdue'): ?>
                          <span class="badge badge-overdue">Overdue</span>
                        <?php elseif ($br['status'] === 'Returned'): ?>
                          <span class="badge badge-available">Returned</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($br['status'] !== 'Returned'): ?>
                          <a href="<?= BASE_URL ?>/librarian/borrowing/return.php?barcode=<?= urlencode($br['barcode']) ?>" class="btn btn-sm btn-outline-primary btn-rounded px-2 py-1 text-xs">
                            <i class="fas fa-arrow-rotate-left me-1"></i> Return
                          </a>
                        <?php else: ?>
                          <span class="text-muted text-xs"><i class="fas fa-check text-success"></i> Done</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Reservations Queue -->
      <div class="col-lg-4">
        <div class="card shadow-sm border h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-clock-rotate-left text-warning me-2"></i> Holds Waiting</span>
            <a href="<?= BASE_URL ?>/librarian/reservations/index.php" class="text-xs text-primary text-decoration-none">Manage Queue</a>
          </div>
          <div class="card-body p-3">
            <?php if (empty($reservationsQueue)): ?>
              <p class="text-muted text-xs text-center py-4 mb-0">No active book reservations in queue.</p>
            <?php else: ?>
              <?php foreach ($reservationsQueue as $rq): ?>
                <div class="p-2 border rounded-3 mb-2 bg-light">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="fw-bold text-sm text-dark"><?= clean_output($rq['title']) ?></span>
                    <?php if ($rq['status'] === 'Ready'): ?>
                      <span class="badge bg-success">Ready</span>
                    <?php else: ?>
                      <span class="badge bg-primary">#<?= $rq['queue_position'] ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="text-xs text-muted mb-1">
                    Member: <strong><?= clean_output($rq['full_name'] ?: $rq['username']) ?></strong>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted text-xs"><?= time_ago($rq['reservation_date']) ?></small>
                    <?php if ($rq['status'] === 'Ready'): ?>
                      <a href="<?= BASE_URL ?>/librarian/reservations/approve.php?id=<?= $rq['id'] ?>" class="btn btn-success btn-sm btn-rounded py-0 px-2 text-xs">
                        <i class="fas fa-hand-holding me-1"></i> Fulfill
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Overdue Items Alert List -->
    <?php if (!empty($urgentOverdues)): ?>
      <div class="card shadow-sm border border-danger">
        <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
          <span><i class="fas fa-triangle-exclamation me-2"></i> Critical Overdue Items Requiring Follow-Up</span>
          <a href="<?= BASE_URL ?>/librarian/reports/overdue.php" class="btn btn-light btn-sm btn-rounded py-1 px-3 text-xs fw-bold">View Overdue Report</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Borrower</th>
                  <th>Contact Phone</th>
                  <th>Book Title</th>
                  <th>Barcode</th>
                  <th>Due Date</th>
                  <th>Accrued Fine</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($urgentOverdues as $ov): ?>
                  <?php
                    $due = new DateTime($ov['due_date']);
                    $today = new DateTime(date('Y-m-d'));
                    $diff = $today->diff($due);
                  ?>
                  <tr>
                    <td class="fw-bold"><?= clean_output($ov['full_name'] ?: $ov['username']) ?></td>
                    <td><?= clean_output($ov['phone'] ?: 'N/A') ?></td>
                    <td><?= clean_output($ov['title']) ?></td>
                    <td><code><?= clean_output($ov['barcode']) ?></code></td>
                    <td class="text-danger fw-bold"><?= format_date($ov['due_date']) ?> (<?= $diff->days ?> days late)</td>
                    <td class="text-danger fw-bold"><?= format_currency($ov['fine_amount']) ?></td>
                    <td>
                      <a href="<?= BASE_URL ?>/librarian/borrowing/return.php?barcode=<?= urlencode($ov['barcode']) ?>" class="btn btn-danger btn-sm btn-rounded px-3 py-1 text-xs">
                        Process Return
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
