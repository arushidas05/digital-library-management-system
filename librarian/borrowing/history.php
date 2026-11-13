<?php
/**
 * Librarian: Full Circulation History Archive
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Circulation History - OpenShelf';

$search = trim($_GET['search'] ?? '');
$where = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "(b.title LIKE ? OR bc.barcode LIKE ? OR u.username LIKE ? OR p.full_name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}

$whereSql = implode(" AND ", $where);

$history = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, b.title, u.username, p.full_name, p.student_id,
            f.fine_amount, f.paid_amount, f.status as fine_status, f.payment_method
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     JOIN users u ON br.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     LEFT JOIN fines f ON br.id = f.borrowing_id
     WHERE {$whereSql}
     ORDER BY br.created_at DESC",
    $params,
    $types
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-history text-primary me-2"></i> Circulation Transactions Archive</h3>
        <p class="text-muted text-sm mb-0">Complete historical audit of checkouts, returns, overdue periods, and fee settlements</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/borrowing/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Active Desk
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <form action="<?= BASE_URL ?>/librarian/borrowing/history.php" method="GET" class="d-flex gap-2" style="max-width: 480px;">
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" name="search" value="<?= clean_output($search) ?>" placeholder="Search student, book, or barcode...">
          <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Search</button>
        </div>
        <?php if (!empty($search)): ?>
          <a href="<?= BASE_URL ?>/librarian/borrowing/history.php" class="btn btn-sm btn-outline-secondary">Reset</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <?php if (empty($history)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-history fs-1 text-light mb-2"></i>
            <p class="mb-0">No circulation history records found.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Borrower</th>
                  <th>Book Title</th>
                  <th>Barcode</th>
                  <th>Issued Date</th>
                  <th>Due Date</th>
                  <th>Return Date</th>
                  <th>Status</th>
                  <th>Fine Record</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($history as $h): ?>
                  <tr>
                    <td>
                      <div class="fw-bold text-dark"><?= clean_output($h['full_name'] ?: $h['username']) ?></div>
                      <div class="text-xs text-muted">ID: <?= clean_output($h['student_id'] ?: 'N/A') ?></div>
                    </td>
                    <td class="fw-semibold"><?= clean_output($h['title']) ?></td>
                    <td><code><?= clean_output($h['barcode']) ?></code></td>
                    <td><?= format_date($h['issue_date']) ?></td>
                    <td><?= format_date($h['due_date']) ?></td>
                    <td><?= format_date($h['return_date']) ?></td>
                    <td>
                      <span class="badge copy-badge-pill status-<?= strtolower($h['status']) ?>">
                        <?= clean_output($h['status']) ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($h['fine_amount'] > 0): ?>
                        <div class="text-xs">
                          <span class="fw-bold text-danger"><?= format_currency($h['fine_amount']) ?></span>
                          <?php if ($h['fine_status'] === 'Paid'): ?>
                            <span class="badge bg-success ms-1">Paid (<?= clean_output($h['payment_method']) ?>)</span>
                          <?php elseif ($h['fine_status'] === 'Waived'): ?>
                            <span class="badge bg-secondary ms-1">Waived</span>
                          <?php else: ?>
                            <span class="badge bg-danger ms-1">Unpaid</span>
                          <?php endif; ?>
                        </div>
                      <?php else: ?>
                        <span class="text-muted text-xs">No Fine</span>
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
