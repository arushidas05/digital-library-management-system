<?php
/**
 * Librarian: Fines & Settlement Ledger
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Fines Ledger - OpenShelf';

process_all_overdue_loans();

$statusFilter = trim($_GET['status'] ?? 'Unpaid'); // 'Unpaid', 'Paid', 'Waived', 'all'
$where = ["1=1"];
$params = [];
$types = "";

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $where[] = "f.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$whereSql = implode(" AND ", $where);

$fines = db_fetch_all(
    "SELECT f.*, br.due_date, br.return_date, bc.barcode, bc.copy_number, b.title,
            u.username, p.full_name, p.student_id, p.phone
     FROM fines f
     JOIN borrowings br ON f.borrowing_id = br.id
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     JOIN users u ON f.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE {$whereSql}
     ORDER BY f.fine_date DESC, f.id DESC",
    $params,
    $types
);

// Totals
$totalsRow = db_fetch_one("SELECT SUM(fine_amount) as total_fines, SUM(paid_amount) as total_collected FROM fines");
$totalFinesAccrued = (float)($totalsRow['total_fines'] ?? 0);
$totalCollected = (float)($totalsRow['total_collected'] ?? 0);
$totalOutstanding = max(0, $totalFinesAccrued - $totalCollected);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-file-invoice-dollar text-primary me-2"></i> Overdue Fines & Fee Settlements</h3>
        <p class="text-muted text-sm mb-0">Record penalty payments, issue cash receipts, and manage student fee liabilities</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Summary KPIs -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon danger">
            <i class="fas fa-clock"></i>
          </div>
          <div>
            <div class="stat-value text-danger"><?= format_currency($totalOutstanding) ?></div>
            <div class="stat-label">Total Outstanding Dues</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon success">
            <i class="fas fa-hand-holding-dollar"></i>
          </div>
          <div>
            <div class="stat-value text-success"><?= format_currency($totalCollected) ?></div>
            <div class="stat-label">Total Fees Collected</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon primary">
            <i class="fas fa-receipt"></i>
          </div>
          <div>
            <div class="stat-value"><?= format_currency($totalFinesAccrued) ?></div>
            <div class="stat-label">Gross Penalties Accrued</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Pills -->
    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/librarian/fines/index.php?status=Unpaid" class="btn btn-sm <?= $statusFilter === 'Unpaid' ? 'btn-danger' : 'btn-outline-danger' ?> filter-badge-btn">
          Unpaid Fines
        </a>
        <a href="<?= BASE_URL ?>/librarian/fines/index.php?status=Paid" class="btn btn-sm <?= $statusFilter === 'Paid' ? 'btn-success' : 'btn-outline-success' ?> filter-badge-btn">
          Settled / Paid
        </a>
        <a href="<?= BASE_URL ?>/librarian/fines/index.php?status=Waived" class="btn btn-sm <?= $statusFilter === 'Waived' ? 'btn-secondary' : 'btn-outline-secondary' ?> filter-badge-btn">
          Waived Records
        </a>
        <a href="<?= BASE_URL ?>/librarian/fines/index.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-dark' ?> filter-badge-btn">
          All Ledger Records
        </a>
      </div>
    </div>

    <!-- Fines Table -->
    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <?php if (empty($fines)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-receipt fs-1 text-light mb-2"></i>
            <p class="mb-0">No fine records found for this filter.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Borrower / Member</th>
                  <th>Overdue Book</th>
                  <th>Barcode</th>
                  <th>Fine Date</th>
                  <th>Amount Due</th>
                  <th>Status</th>
                  <th>Receipt / Settlement</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($fines as $f): ?>
                  <tr>
                    <td>
                      <div class="fw-bold text-dark"><?= clean_output($f['full_name'] ?: $f['username']) ?></div>
                      <div class="text-xs text-muted">ID: <?= clean_output($f['student_id'] ?: 'N/A') ?></div>
                    </td>
                    <td class="fw-semibold"><?= clean_output($f['title']) ?></td>
                    <td><code><?= clean_output($f['barcode']) ?></code></td>
                    <td><?= format_date($f['fine_date']) ?></td>
                    <td>
                      <span class="fw-bold fs-6 text-dark"><?= format_currency($f['fine_amount']) ?></span>
                      <?php if ($f['paid_amount'] > 0 && $f['paid_amount'] < $f['fine_amount']): ?>
                        <div class="text-xs text-muted">Paid: <?= format_currency($f['paid_amount']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($f['status'] === 'Paid'): ?>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Paid</span>
                      <?php elseif ($f['status'] === 'Waived'): ?>
                        <span class="badge bg-secondary">Waived</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Unpaid</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($f['status'] === 'Paid'): ?>
                        <div class="text-xs fw-semibold text-dark"><?= clean_output($f['receipt_no']) ?></div>
                        <div class="text-xs text-muted"><?= clean_output($f['payment_method']) ?> &bull; <?= format_date($f['paid_date']) ?></div>
                      <?php else: ?>
                        <span class="text-muted text-xs">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <?php if ($f['status'] === 'Unpaid'): ?>
                        <a href="<?= BASE_URL ?>/librarian/fines/settle.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-success btn-rounded px-3">
                          <i class="fas fa-hand-holding-dollar me-1"></i> Collect Fee
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
