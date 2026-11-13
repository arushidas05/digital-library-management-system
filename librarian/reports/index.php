<?php
/**
 * Librarian & Admin: Reports & Analytics Hub
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Library Reports & Analytics - OpenShelf';

// Analytical Aggregates
$totalTitles = db_count('books', "status = 'active'");
$totalCopies = db_count('book_copies');
$totalLoans = db_count('borrowings');
$activeLoans = db_count('borrowings', "status IN ('Issued', 'Overdue')");
$totalMembers = db_count('users', "role_id = 3");
$overdueCount = db_count('borrowings', "status = 'Overdue'");

$finesRow = db_fetch_one("SELECT SUM(fine_amount) as total_fines, SUM(paid_amount) as total_paid FROM fines");
$totalFinesAccrued = (float)($finesRow['total_fines'] ?? 0);
$totalFinesCollected = (float)($finesRow['total_paid'] ?? 0);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-chart-pie text-primary me-2"></i> Operational Reports & Analytics</h3>
        <p class="text-muted text-sm mb-0">Circulation performance, collection usage, borrower metrics, and financial audit logs</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Master KPI Row -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon primary"><i class="fas fa-book"></i></div>
          <div>
            <div class="stat-value"><?= $totalTitles ?></div>
            <div class="stat-label">Catalogue Titles</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon info"><i class="fas fa-barcode"></i></div>
          <div>
            <div class="stat-value"><?= $totalCopies ?></div>
            <div class="stat-label">Physical Copies</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon success"><i class="fas fa-hand-holding-hand"></i></div>
          <div>
            <div class="stat-value"><?= $totalLoans ?></div>
            <div class="stat-label">Total Checkouts</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon warning"><i class="fas fa-users"></i></div>
          <div>
            <div class="stat-value"><?= $totalMembers ?></div>
            <div class="stat-label">Registered Members</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reports Directory Cards -->
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card p-4 border shadow-sm h-100 card-hover">
          <div class="stat-icon primary mb-3">
            <i class="fas fa-chart-line"></i>
          </div>
          <h5 class="fw-bold mb-2">Borrowing Trends Report</h5>
          <p class="text-muted text-sm mb-4">
            Analysis of checkout trends, return timeliness, and monthly circulation volume across all departments.
          </p>
          <a href="<?= BASE_URL ?>/librarian/reports/borrowing.php" class="btn btn-outline-primary btn-sm btn-rounded mt-auto">
            Open Report <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-4 border shadow-sm h-100 card-hover">
          <div class="stat-icon success mb-3">
            <i class="fas fa-fire"></i>
          </div>
          <h5 class="fw-bold mb-2">Most Popular Books</h5>
          <p class="text-muted text-sm mb-4">
            Ranking of most borrowed physical titles, highest online read counts, and reader satisfaction scores.
          </p>
          <a href="<?= BASE_URL ?>/librarian/reports/popular.php" class="btn btn-outline-success btn-sm btn-rounded mt-auto">
            Open Ranking <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-4 border shadow-sm h-100 card-hover">
          <div class="stat-icon danger mb-3">
            <i class="fas fa-triangle-exclamation"></i>
          </div>
          <h5 class="fw-bold mb-2">Overdue & Fine Report</h5>
          <p class="text-muted text-sm mb-4">
            Active overdue loans, delinquent member list, accumulated fine calculations, and recovery statistics.
          </p>
          <a href="<?= BASE_URL ?>/librarian/reports/overdue.php" class="btn btn-outline-danger btn-sm btn-rounded mt-auto">
            Open Overdue List <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
