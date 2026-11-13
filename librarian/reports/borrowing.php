<?php
/**
 * Librarian: Borrowing Trends Report
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Borrowing Trends Report - OpenShelf';

// Borrowing status breakdown
$statusBreakdown = db_fetch_all("SELECT status, COUNT(*) as cnt FROM borrowings GROUP BY status");

// Monthly breakdown
$monthlyTrends = db_fetch_all(
    "SELECT DATE_FORMAT(issue_date, '%M %Y') as month_label, COUNT(*) as total_loans,
            SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned_count,
            SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_count
     FROM borrowings
     GROUP BY DATE_FORMAT(issue_date, '%Y-%m'), DATE_FORMAT(issue_date, '%M %Y')
     ORDER BY issue_date DESC LIMIT 6"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-chart-line text-primary me-2"></i> Borrowing Circulation Trends</h3>
        <p class="text-muted text-sm mb-0">Monthly circulation breakdown and loan status distribution</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/reports/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Reports Overview
        </a>
      </div>
    </div>

    <!-- Monthly Circulation Summary Table -->
    <div class="card shadow-sm border mb-4">
      <div class="card-header bg-white fw-bold">
        <i class="fas fa-calendar-days text-primary me-2"></i> Monthly Circulation Performance
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 text-sm">
            <thead>
              <tr>
                <th>Month</th>
                <th>Total Checkouts</th>
                <th>Returned Safely</th>
                <th>Overdue Loans</th>
                <th>Timely Return Rate</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($monthlyTrends as $mt): ?>
                <?php
                  $rate = $mt['total_loans'] > 0 ? round(($mt['returned_count'] / $mt['total_loans']) * 100) : 0;
                ?>
                <tr>
                  <td class="fw-bold"><?= clean_output($mt['month_label']) ?></td>
                  <td><?= $mt['total_loans'] ?> checkouts</td>
                  <td class="text-success"><?= $mt['returned_count'] ?> returned</td>
                  <td class="text-danger"><?= $mt['overdue_count'] ?> overdue</td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $rate ?>%;"></div>
                      </div>
                      <span class="text-xs fw-bold"><?= $rate ?>%</span>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Status Distribution -->
    <div class="card p-4 border shadow-sm">
      <h5 class="fw-bold mb-3">Overall Loan Status Distribution</h5>
      <div class="row g-3">
        <?php foreach ($statusBreakdown as $sb): ?>
          <div class="col-md-3">
            <div class="p-3 border rounded-3 bg-light">
              <span class="badge copy-badge-pill status-<?= strtolower($sb['status']) ?> mb-2"><?= clean_output($sb['status']) ?></span>
              <div class="fs-4 fw-bold text-dark"><?= $sb['cnt'] ?></div>
              <small class="text-muted">Total checkouts in this state</small>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
