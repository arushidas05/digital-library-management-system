<?php
/**
 * Librarian: Physical Copies Inventory Management
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Physical Copies Inventory - OpenShelf';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$where = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "(bc.barcode LIKE ? OR bc.copy_number LIKE ? OR b.title LIKE ? OR bc.shelf_location LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $where[] = "bc.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$whereSql = implode(" AND ", $where);

$copies = db_fetch_all(
    "SELECT bc.*, b.title, b.isbn, b.cover_image,
            (SELECT CONCAT(u.username, ' (Due: ', br.due_date, ')') 
             FROM borrowings br 
             JOIN users u ON br.user_id = u.id 
             WHERE br.book_copy_id = bc.id AND br.status IN ('Issued', 'Overdue') LIMIT 1) as current_loan_info
     FROM book_copies bc
     JOIN books b ON bc.book_id = b.id
     WHERE {$whereSql}
     ORDER BY bc.id DESC",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-barcode text-primary me-2"></i> Physical Copies Inventory</h3>
        <p class="text-muted text-sm mb-0">Track each physical item, barcode label, shelf placement, and condition status</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/copies/add.php" class="btn btn-primary btn-sm btn-rounded fw-semibold">
          <i class="fas fa-plus me-1"></i> Add Physical Copies
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Search & Filter Card -->
    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <form action="<?= BASE_URL ?>/librarian/copies/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control" name="search" value="<?= clean_output($search) ?>" placeholder="Search barcode, copy number, shelf, or title...">
          </div>
        </div>
        <div class="col-md-4">
          <select class="form-select form-select-sm" name="status">
            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Inventory Statuses</option>
            <option value="Available" <?= $statusFilter === 'Available' ? 'selected' : '' ?>>Available on Shelf</option>
            <option value="Borrowed" <?= $statusFilter === 'Borrowed' ? 'selected' : '' ?>>Currently Borrowed</option>
            <option value="Reserved" <?= $statusFilter === 'Reserved' ? 'selected' : '' ?>>Reserved / Hold</option>
            <option value="Maintenance" <?= $statusFilter === 'Maintenance' ? 'selected' : '' ?>>Under Maintenance / Repair</option>
            <option value="Damaged" <?= $statusFilter === 'Damaged' ? 'selected' : '' ?>>Damaged</option>
            <option value="Lost" <?= $statusFilter === 'Lost' ? 'selected' : '' ?>>Reported Lost</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
          <?php if (!empty($search) || $statusFilter !== 'all'): ?>
            <a href="<?= BASE_URL ?>/librarian/copies/index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Copies Inventory Table -->
    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <?php if (empty($copies)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-barcode fs-1 text-light mb-2"></i>
            <p class="mb-0">No physical copies found matching your criteria.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Barcode</th>
                  <th>Copy #</th>
                  <th>Book Title</th>
                  <th>Shelf Location</th>
                  <th>Status</th>
                  <th>Current Holder</th>
                  <th>Condition</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($copies as $cp): ?>
                  <tr>
                    <td><code><?= clean_output($cp['barcode']) ?></code></td>
                    <td class="fw-bold"><?= clean_output($cp['copy_number']) ?></td>
                    <td>
                      <a href="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $cp['book_id'] ?>" class="text-dark fw-semibold text-decoration-none hover-primary">
                        <?= clean_output($cp['title']) ?>
                      </a>
                    </td>
                    <td><i class="fas fa-location-dot text-danger me-1"></i> <?= clean_output($cp['shelf_location']) ?></td>
                    <td>
                      <span class="badge copy-badge-pill status-<?= strtolower($cp['status']) ?>">
                        <?= clean_output($cp['status']) ?>
                      </span>
                    </td>
                    <td class="text-xs">
                      <?= $cp['current_loan_info'] ? '<span class="text-danger fw-semibold">' . clean_output($cp['current_loan_info']) . '</span>' : '<span class="text-muted">On Shelf</span>' ?>
                    </td>
                    <td class="text-muted text-xs"><?= clean_output($cp['condition_notes'] ?: 'Good') ?></td>
                    <td class="text-end">
                      <div class="btn-group">
                        <a href="<?= BASE_URL ?>/librarian/copies/edit.php?id=<?= $cp['id'] ?>" class="btn btn-sm btn-outline-primary table-action-btn" title="Edit Copy Location">
                          <i class="fas fa-pen"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/librarian/copies/status.php?id=<?= $cp['id'] ?>" class="btn btn-sm btn-outline-warning table-action-btn" title="Update Copy Status">
                          <i class="fas fa-rotate"></i>
                        </a>
                      </div>
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
