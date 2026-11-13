<?php
/**
 * Librarian: Reservations Queue Management
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Reservations Queue - OpenShelf';

$statusFilter = trim($_GET['status'] ?? 'active'); // 'active', 'ready', 'all'
$where = ["1=1"];
$params = [];
$types = "";

if ($statusFilter === 'active') {
    $where[] = "r.status IN ('Pending', 'Ready')";
} elseif ($statusFilter === 'ready') {
    $where[] = "r.status = 'Ready'";
} elseif ($statusFilter === 'completed') {
    $where[] = "r.status = 'Completed'";
}

$whereSql = implode(" AND ", $where);

$reservations = db_fetch_all(
    "SELECT r.*, b.title, b.isbn, b.cover_image,
            u.username, u.email, p.full_name, p.student_id, p.phone,
            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = r.book_id AND bc.status = 'Available') as available_copies
     FROM reservations r
     JOIN books b ON r.book_id = b.id
     JOIN users u ON r.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE {$whereSql}
     ORDER BY r.status DESC, r.queue_position ASC, r.reservation_date ASC",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-clock-rotate-left text-primary me-2"></i> Book Reservations Queue</h3>
        <p class="text-muted text-sm mb-0">Automated queue prioritization and hold pickup management for high-demand titles</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Filter Pills -->
    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/librarian/reservations/index.php?status=active" class="btn btn-sm <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-outline-secondary' ?> filter-badge-btn">
          Active Queue (Pending & Ready)
        </a>
        <a href="<?= BASE_URL ?>/librarian/reservations/index.php?status=ready" class="btn btn-sm <?= $statusFilter === 'ready' ? 'btn-success' : 'btn-outline-success' ?> filter-badge-btn">
          Ready for Pickup
        </a>
        <a href="<?= BASE_URL ?>/librarian/reservations/index.php?status=completed" class="btn btn-sm <?= $statusFilter === 'completed' ? 'btn-secondary' : 'btn-outline-secondary' ?> filter-badge-btn">
          Completed Archive
        </a>
        <a href="<?= BASE_URL ?>/librarian/reservations/index.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-dark' ?> filter-badge-btn">
          All Records
        </a>
      </div>
    </div>

    <!-- Reservations Table -->
    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <?php if (empty($reservations)): ?>
          <div class="text-center py-5 text-muted">
            <i class="far fa-calendar-check fs-1 text-light mb-2"></i>
            <p class="mb-0">No reservations found for this filter.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Queue #</th>
                  <th>Member / Student</th>
                  <th>Book Title</th>
                  <th>Reserved On</th>
                  <th>Status</th>
                  <th>Hold Deadline</th>
                  <th class="text-end">Fulfill / Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reservations as $res): ?>
                  <tr>
                    <td>
                      <span class="badge bg-light text-primary border fs-6">
                        #<?= $res['queue_position'] ?>
                      </span>
                    </td>
                    <td>
                      <div class="fw-bold text-dark"><?= clean_output($res['full_name'] ?: $res['username']) ?></div>
                      <div class="text-xs text-muted">ID: <?= clean_output($res['student_id'] ?: 'N/A') ?> &bull; <?= clean_output($res['phone'] ?: $res['email']) ?></div>
                    </td>
                    <td class="fw-semibold">
                      <a href="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $res['book_id'] ?>" class="text-dark text-decoration-none hover-primary">
                        <?= clean_output($res['title']) ?>
                      </a>
                      <div class="text-xs text-muted">Available in stacks: <?= $res['available_copies'] ?> copies</div>
                    </td>
                    <td><?= format_date($res['reservation_date'], 'M d, Y h:i A') ?></td>
                    <td>
                      <?php if ($res['status'] === 'Ready'): ?>
                        <span class="badge bg-success fs-6"><i class="fas fa-check me-1"></i> Ready for Pickup</span>
                      <?php elseif ($res['status'] === 'Pending'): ?>
                        <span class="badge bg-primary">In Queue (#<?= $res['queue_position'] ?>)</span>
                      <?php elseif ($res['status'] === 'Completed'): ?>
                        <span class="badge bg-secondary">Collected</span>
                      <?php elseif ($res['status'] === 'Cancelled'): ?>
                        <span class="badge bg-danger">Cancelled</span>
                      <?php else: ?>
                        <span class="badge bg-light text-muted border"><?= clean_output($res['status']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($res['expiry_date']): ?>
                        <span class="text-danger fw-bold"><?= format_date($res['expiry_date'], 'M d, Y') ?></span>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <?php if ($res['status'] === 'Ready' || ($res['status'] === 'Pending' && $res['available_copies'] > 0)): ?>
                        <a href="<?= BASE_URL ?>/librarian/reservations/approve.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-success btn-rounded px-3">
                          <i class="fas fa-hand-holding me-1"></i> Issue Copy
                        </a>
                      <?php endif; ?>
                      <?php if (in_array($res['status'], ['Pending', 'Ready'])): ?>
                        <a href="<?= BASE_URL ?>/librarian/reservations/cancel.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-outline-danger btn-rounded" onclick="return confirm('Cancel this reservation?');">
                          Cancel
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
