<?php
/**
 * Student Reservations Queue Management
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'My Book Reservations - OpenShelf';
$user = current_user();
$userId = $user['id'];

// Handle Cancel Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $resId = (int)($_POST['reservation_id'] ?? 0);
    $res = db_fetch_one("SELECT * FROM reservations WHERE id = ? AND user_id = ?", [$resId, $userId], "ii");
    if ($res && in_array($res['status'], ['Pending', 'Ready'])) {
        db_execute("UPDATE reservations SET status = 'Cancelled' WHERE id = ?", [$resId], "i");
        log_audit('RESERVATION_CANCELLED', "Member {$user['username']} cancelled reservation #{$resId}");
        set_flash('info', 'Your book reservation has been cancelled.');
        header("Location: " . BASE_URL . "/student/reservations.php");
        exit;
    }
}

// Active / Pending Reservations
$activeReservations = db_fetch_all(
    "SELECT r.*, b.title, b.cover_image, b.id as book_id,
            (SELECT COUNT(*) FROM reservations r2 WHERE r2.book_id = r.book_id AND r2.status = 'Pending' AND r2.queue_position < r.queue_position) as people_ahead
     FROM reservations r
     JOIN books b ON r.book_id = b.id
     WHERE r.user_id = ? AND r.status IN ('Pending', 'Ready')
     ORDER BY r.reservation_date DESC",
    [$userId],
    "i"
);

// Past / Historical Reservations
$pastReservations = db_fetch_all(
    "SELECT r.*, b.title, b.cover_image, b.id as book_id
     FROM reservations r
     JOIN books b ON r.book_id = b.id
     WHERE r.user_id = ? AND r.status NOT IN ('Pending', 'Ready')
     ORDER BY r.reservation_date DESC",
    [$userId],
    "i"
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-clock-rotate-left text-primary me-2"></i> My Book Reservations</h3>
        <p class="text-muted text-sm mb-0">Track queue positions and collection holds for unavailable physical books</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-plus me-1"></i> Reserve Another Book
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Active Holds Grid -->
    <div class="card mb-4 shadow-sm border">
      <div class="card-header bg-white fw-bold">
        <i class="fas fa-bookmark text-primary me-2"></i> Current Active Holds (<?= count($activeReservations) ?>)
      </div>
      <div class="card-body p-0">
        <?php if (empty($activeReservations)): ?>
          <div class="text-center py-5 text-muted">
            <i class="far fa-calendar-check fs-2 text-primary mb-2"></i>
            <p class="mb-0">You have no active book holds or reservations.</p>
            <small class="text-muted">When all physical copies of a book are on loan, click "Reserve Book" on its page to get notified first when returned.</small>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Book Title</th>
                  <th>Reserved On</th>
                  <th>Queue Status</th>
                  <th>Pickup Deadline</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($activeReservations as $res): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img src="<?= get_book_cover($res['cover_image'], $res['title']) ?>" alt="Cover" style="width: 38px; height: 50px; object-fit: cover; border-radius: 4px;">
                        <div>
                          <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $res['book_id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                            <?= clean_output($res['title']) ?>
                          </a>
                        </div>
                      </div>
                    </td>
                    <td><?= format_date($res['reservation_date'], 'M d, Y h:i A') ?></td>
                    <td>
                      <?php if ($res['status'] === 'Ready'): ?>
                        <span class="badge bg-success fs-6"><i class="fas fa-bell me-1"></i> Ready for Pickup!</span>
                        <div class="text-xs text-success fw-semibold mt-1">Book is waiting at circulation desk</div>
                      <?php else: ?>
                        <span class="badge bg-primary fs-6">Position #<?= $res['queue_position'] ?></span>
                        <div class="text-xs text-muted mt-1"><?= $res['people_ahead'] ?> person(s) ahead of you</div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($res['expiry_date']): ?>
                        <span class="text-danger fw-bold"><i class="far fa-clock me-1"></i> <?= format_date($res['expiry_date'], 'M d, Y h:i A') ?></span>
                      <?php else: ?>
                        <span class="text-muted text-sm">Awaiting copy return</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form action="<?= BASE_URL ?>/student/reservations.php" method="POST" onsubmit="return confirm('Cancel this reservation?');">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm btn-rounded">
                          <i class="fas fa-times me-1"></i> Cancel Hold
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Past Holds Table -->
    <div class="card shadow-sm border">
      <div class="card-header bg-white fw-bold">
        <i class="fas fa-history text-muted me-2"></i> Reservation History
      </div>
      <div class="card-body p-0">
        <?php if (empty($pastReservations)): ?>
          <div class="text-center py-4 text-muted text-sm">
            No past reservations on record.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Book Title</th>
                  <th>Reserved On</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pastReservations as $pres): ?>
                  <tr>
                    <td class="fw-semibold">
                      <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $pres['book_id'] ?>" class="text-dark text-decoration-none hover-primary">
                        <?= clean_output($pres['title']) ?>
                      </a>
                    </td>
                    <td><?= format_date($pres['reservation_date']) ?></td>
                    <td>
                      <?php if ($pres['status'] === 'Completed'): ?>
                        <span class="badge bg-success">Collected / Issued</span>
                      <?php elseif ($pres['status'] === 'Cancelled'): ?>
                        <span class="badge bg-secondary">Cancelled</span>
                      <?php elseif ($pres['status'] === 'Expired'): ?>
                        <span class="badge bg-danger">Expired Uncollected</span>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
