<?php
/**
 * Librarian: Circulation Desk & Active Loans
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Circulation Desk - OpenShelf';

process_all_overdue_loans();

$statusFilter = trim($_GET['status'] ?? 'active'); // 'active', 'overdue', 'returned', 'all'
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];
$types = "";

if ($statusFilter === 'active') {
    $where[] = "br.status IN ('Issued', 'Overdue')";
} elseif ($statusFilter === 'overdue') {
    $where[] = "br.status = 'Overdue'";
} elseif ($statusFilter === 'returned') {
    $where[] = "br.status = 'Returned'";
}

if (!empty($search)) {
    $where[] = "(b.title LIKE ? OR bc.barcode LIKE ? OR u.username LIKE ? OR p.full_name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}

$whereSql = implode(" AND ", $where);

$loans = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, bc.shelf_location, b.title, b.cover_image,
            u.username, p.full_name, p.student_id, p.phone,
            f.fine_amount, f.status as fine_status
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
        <h3 class="fw-bold mb-1"><i class="fas fa-hand-holding-hand text-primary me-2"></i> Circulation Desk & Loans</h3>
        <p class="text-muted text-sm mb-0">Manage physical copy checkouts, return check-ins, due date tracking, and overdue status</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/librarian/borrowing/issue.php" class="btn btn-success btn-sm btn-rounded fw-semibold">
          <i class="fas fa-plus-circle me-1"></i> Issue Physical Book
        </a>
        <a href="<?= BASE_URL ?>/librarian/borrowing/return.php" class="btn btn-primary btn-sm btn-rounded fw-semibold">
          <i class="fas fa-arrow-rotate-left me-1"></i> Return Book
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Filter Pills & Search -->
    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex flex-wrap gap-1">
          <a href="<?= BASE_URL ?>/librarian/borrowing/index.php?status=active" class="btn btn-sm <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-outline-secondary' ?> filter-badge-btn">
            Active Loans
          </a>
          <a href="<?= BASE_URL ?>/librarian/borrowing/index.php?status=overdue" class="btn btn-sm <?= $statusFilter === 'overdue' ? 'btn-danger' : 'btn-outline-danger' ?> filter-badge-btn">
            Overdue Only
          </a>
          <a href="<?= BASE_URL ?>/librarian/borrowing/index.php?status=returned" class="btn btn-sm <?= $statusFilter === 'returned' ? 'btn-success' : 'btn-outline-success' ?> filter-badge-btn">
            Returned Archive
          </a>
          <a href="<?= BASE_URL ?>/librarian/borrowing/index.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-dark' ?> filter-badge-btn">
            All Records
          </a>
        </div>

        <form action="<?= BASE_URL ?>/librarian/borrowing/index.php" method="GET" class="d-flex gap-2" style="max-width: 320px; width: 100%;">
          <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
          <div class="input-group input-group-sm">
            <input type="text" class="form-control" name="search" value="<?= clean_output($search) ?>" placeholder="Search student, book, or barcode...">
            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
          </div>
        </form>
      </div>
    </div>

    <!-- Loans Table -->
    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <?php if (empty($loans)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-hand-holding-box fs-1 text-light mb-2"></i>
            <p class="mb-0">No circulation records found matching the filter.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Borrower / Student</th>
                  <th>Book Title</th>
                  <th>Barcode</th>
                  <th>Issue Date</th>
                  <th>Due Date</th>
                  <th>Status & Fine</th>
                  <th class="text-end">Desk Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($loans as $loan): ?>
                  <?php
                    $due = new DateTime($loan['due_date']);
                    $today = new DateTime(date('Y-m-d'));
                    $isOverdue = ($today > $due && $loan['status'] !== 'Returned');
                    $diff = $today->diff($due);
                  ?>
                  <tr>
                    <td>
                      <div class="fw-bold text-dark"><?= clean_output($loan['full_name'] ?: $loan['username']) ?></div>
                      <div class="text-xs text-muted">ID: <?= clean_output($loan['student_id'] ?: 'N/A') ?> &bull; @<?= clean_output($loan['username']) ?></div>
                    </td>
                    <td>
                      <a href="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $loan['id'] ?>" class="text-dark fw-semibold text-decoration-none hover-primary">
                        <?= clean_output($loan['title']) ?>
                      </a>
                    </td>
                    <td><code><?= clean_output($loan['barcode']) ?></code></td>
                    <td><?= format_date($loan['issue_date']) ?></td>
                    <td class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                      <?= format_date($loan['due_date']) ?>
                      <?php if ($isOverdue): ?>
                        <div class="text-xs text-danger"><?= $diff->days ?> days late</div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($loan['status'] === 'Returned'): ?>
                        <span class="badge badge-available">Returned</span>
                        <?php if ($loan['return_date']): ?>
                          <div class="text-xs text-muted"><?= format_date($loan['return_date']) ?></div>
                        <?php endif; ?>
                      <?php elseif ($isOverdue || $loan['status'] === 'Overdue'): ?>
                        <span class="badge badge-overdue d-block mb-1">Overdue</span>
                        <?php if ($loan['fine_amount'] > 0): ?>
                          <span class="text-danger fw-bold text-xs">Fine: <?= format_currency($loan['fine_amount']) ?></span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge badge-borrowed">Active Loan</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <?php if ($loan['status'] !== 'Returned'): ?>
                        <a href="<?= BASE_URL ?>/librarian/borrowing/return.php?barcode=<?= urlencode($loan['barcode']) ?>" class="btn btn-sm btn-primary btn-rounded px-3 py-1">
                          <i class="fas fa-arrow-rotate-left me-1"></i> Return
                        </a>
                      <?php else: ?>
                        <span class="text-muted text-xs"><i class="fas fa-check text-success me-1"></i> Completed</span>
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
