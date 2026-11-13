<?php
/**
 * Student Dashboard
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'Member Dashboard - OpenShelf';
$user = current_user();
$userId = $user['id'];

// Process overdue loans routine
process_all_overdue_loans();

// Student Metrics
$activeLoansCount = db_count('borrowings', "user_id = ? AND status = 'Issued'", [$userId], "i");
$overdueLoansCount = db_count('borrowings', "user_id = ? AND status = 'Overdue'", [$userId], "i");
$readingCount = db_count('reading_progress', "user_id = ? AND progress_percentage < 100", [$userId], "i");
$reservationsCount = db_count('reservations', "user_id = ? AND status IN ('Pending', 'Ready')", [$userId], "i");
$wishlistCount = db_count('wishlists', "user_id = ?", [$userId], "i");

// Calculate total outstanding fines
$fineRow = db_fetch_one("SELECT SUM(fine_amount - paid_amount) as total_unpaid FROM fines WHERE user_id = ? AND status = 'Unpaid'", [$userId], "i");
$totalFinesUnpaid = (float)($fineRow['total_unpaid'] ?? 0);

// Fetch Continue Reading Book (last active reading progress)
$continueBook = db_fetch_one(
    "SELECT rp.*, b.title, b.cover_image, b.total_pages,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list
     FROM reading_progress rp
     JOIN books b ON rp.book_id = b.id
     WHERE rp.user_id = ?
     ORDER BY rp.last_read_at DESC LIMIT 1",
    [$userId],
    "i"
);

// Fetch Currently Borrowed Physical Books
$borrowedBooks = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, bc.shelf_location, b.id as book_id, b.title, b.cover_image
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN books b ON bc.book_id = b.id
     WHERE br.user_id = ? AND br.status IN ('Issued', 'Overdue')
     ORDER BY br.due_date ASC",
    [$userId],
    "i"
);

// Fetch Active Reservations
$activeReservations = db_fetch_all(
    "SELECT r.*, b.title, b.cover_image
     FROM reservations r
     JOIN books b ON r.book_id = b.id
     WHERE r.user_id = ? AND r.status IN ('Pending', 'Ready')
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
        <h3 class="fw-bold mb-1">Welcome, <?= clean_output($user['full_name'] ?: $user['username']) ?>!</h3>
        <p class="text-muted text-sm mb-0">Member ID: <span class="badge bg-light text-dark border"><?= clean_output($user['student_id'] ?: 'BCA-MEMBER') ?></span> &bull; OpenShelf Student Portal</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-primary btn-rounded btn-sm">
          <i class="fas fa-search me-1"></i> Browse Catalogue
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <?php if ($totalFinesUnpaid > 0): ?>
      <div class="alert alert-danger d-flex align-items-center justify-content-between shadow-sm mb-4">
        <div class="d-flex align-items-center">
          <i class="fas fa-triangle-exclamation fs-4 me-3"></i>
          <div>
            <strong>You have outstanding library fines: <?= format_currency($totalFinesUnpaid) ?></strong>
            <div class="text-xs">Please visit the circulation desk to return overdue items and clear your balance.</div>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/student/borrowed-books.php" class="btn btn-danger btn-sm btn-rounded">View Fines</a>
      </div>
    <?php endif; ?>

    <!-- Stat Cards Grid -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon primary">
            <i class="fas fa-book"></i>
          </div>
          <div>
            <div class="stat-value"><?= $activeLoansCount ?></div>
            <div class="stat-label">Borrowed Books</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon <?= $overdueLoansCount > 0 ? 'danger' : 'success' ?>">
            <i class="fas fa-clock"></i>
          </div>
          <div>
            <div class="stat-value"><?= $overdueLoansCount ?></div>
            <div class="stat-label">Overdue Items</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon info">
            <i class="fas fa-book-open-reader"></i>
          </div>
          <div>
            <div class="stat-value"><?= $readingCount ?></div>
            <div class="stat-label">Reading Online</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon warning">
            <i class="fas fa-clock-rotate-left"></i>
          </div>
          <div>
            <div class="stat-value"><?= $reservationsCount ?></div>
            <div class="stat-label">Active Holds</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Continue Reading Banner -->
    <?php if ($continueBook): ?>
      <div class="continue-reading-banner">
        <div class="row align-items-center">
          <div class="col-md-8">
            <span class="badge bg-warning text-dark fw-bold mb-2">
              <i class="fas fa-play me-1"></i> Continue Reading
            </span>
            <h4 class="fw-bold mb-1"><?= clean_output($continueBook['title']) ?></h4>
            <p class="text-white-50 text-sm mb-3">By <?= clean_output($continueBook['authors_list'] ?: 'Unknown Author') ?> &bull; Chapter <?= $continueBook['current_chapter'] ?></p>

            <div class="d-flex align-items-center gap-3 mb-3" style="max-width: 400px;">
              <div class="progress flex-grow-1" style="height: 8px; background: rgba(255,255,255,0.2);">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $continueBook['progress_percentage'] ?>%;" aria-valuenow="<?= $continueBook['progress_percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <span class="fw-bold text-sm"><?= (int)$continueBook['progress_percentage'] ?>% completed</span>
            </div>

            <div class="d-flex gap-2">
              <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $continueBook['book_id'] ?>&chapter=<?= $continueBook['current_chapter'] ?>" class="btn btn-warning btn-rounded px-4 py-2 fw-bold text-dark">
                <i class="fas fa-book-open me-2"></i> Resume Reading
              </a>
              <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $continueBook['book_id'] ?>" class="btn btn-outline-light btn-rounded px-3 py-2 text-sm">
                Book Info
              </a>
            </div>
          </div>
          <div class="col-md-4 text-center text-md-end d-none d-md-block">
            <img src="<?= get_book_cover($continueBook['cover_image'], $continueBook['title']) ?>" alt="Cover" class="rounded shadow-lg" style="height: 160px; object-fit: cover;">
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Two-column tables: Active Borrowings & Reservations -->
    <div class="row g-4">
      <!-- Borrowed Books -->
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-book-bookmark text-primary me-2"></i> Currently Borrowed Physical Copies</span>
            <a href="<?= BASE_URL ?>/student/borrowed-books.php" class="text-sm text-primary text-decoration-none">View All History</a>
          </div>
          <div class="card-body p-0">
            <?php if (empty($borrowedBooks)): ?>
              <div class="text-center py-5 text-muted">
                <i class="fas fa-book-open fs-2 mb-2 text-light"></i>
                <p class="mb-0">You currently have no borrowed physical books.</p>
                <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-outline-primary btn-sm mt-3 btn-rounded">Find Books to Borrow</a>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th>Book</th>
                      <th>Barcode</th>
                      <th>Due Date</th>
                      <th>Status</th>
                      <th>Shelf</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($borrowedBooks as $b): ?>
                      <?php
                        $due = new DateTime($b['due_date']);
                        $today = new DateTime(date('Y-m-d'));
                        $diff = $today->diff($due);
                        $isOverdue = ($today > $due);
                      ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <img src="<?= get_book_cover($b['cover_image'], $b['title']) ?>" style="width: 36px; height: 48px; object-fit: cover; border-radius: 4px;" alt="Cover">
                            <div>
                              <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['book_id'] ?>" class="text-dark fw-semibold text-decoration-none hover-primary text-sm d-block">
                                <?= clean_output($b['title']) ?>
                              </a>
                              <span class="text-xs text-muted">Issued: <?= format_date($b['issue_date']) ?></span>
                            </div>
                          </div>
                        </td>
                        <td><code><?= clean_output($b['barcode']) ?></code></td>
                        <td>
                          <span class="fw-semibold text-sm <?= $isOverdue ? 'text-danger' : 'text-dark' ?>">
                            <?= format_date($b['due_date']) ?>
                          </span>
                          <div class="text-xs text-muted">
                            <?= $isOverdue ? "Overdue by {$diff->days} days" : "{$diff->days} days left" ?>
                          </div>
                        </td>
                        <td>
                          <?php if ($isOverdue): ?>
                            <span class="badge badge-overdue">Overdue</span>
                          <?php else: ?>
                            <span class="badge badge-borrowed">Active Loan</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-xs text-muted"><?= clean_output($b['shelf_location']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Active Reservations & Quick Tools -->
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-clock-rotate-left text-warning me-2"></i> Book Reservations</span>
            <a href="<?= BASE_URL ?>/student/reservations.php" class="text-xs text-primary text-decoration-none">Manage</a>
          </div>
          <div class="card-body p-3">
            <?php if (empty($activeReservations)): ?>
              <div class="text-center py-4 text-muted text-sm">
                <i class="far fa-calendar-check fs-3 mb-2 text-light"></i>
                <p class="mb-0">No active book reservations.</p>
              </div>
            <?php else: ?>
              <?php foreach ($activeReservations as $res): ?>
                <div class="p-2 border rounded-3 mb-2 bg-light">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="fw-semibold text-sm"><?= clean_output($res['title']) ?></span>
                    <?php if ($res['status'] === 'Ready'): ?>
                      <span class="badge bg-success">Ready for Pickup</span>
                    <?php else: ?>
                      <span class="badge bg-primary">Queue #<?= $res['queue_position'] ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="text-xs text-muted">
                    Reserved: <?= format_date($res['reservation_date']) ?>
                    <?php if ($res['expiry_date']): ?>
                      &bull; <span class="text-danger fw-semibold">Pickup by <?= format_date($res['expiry_date']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
          <div class="card-header fw-bold">
            <i class="fas fa-bolt text-warning me-2"></i> Quick Actions
          </div>
          <div class="list-group list-group-flush text-sm">
            <a href="<?= BASE_URL ?>/student/catalogue.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
              <span><i class="fas fa-compass me-2 text-primary"></i> Explore Books Catalogue</span>
              <i class="fas fa-chevron-right text-muted text-xs"></i>
            </a>
            <a href="<?= BASE_URL ?>/student/my-library.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
              <span><i class="fas fa-book-reader me-2 text-success"></i> My Reading Library</span>
              <i class="fas fa-chevron-right text-muted text-xs"></i>
            </a>
            <a href="<?= BASE_URL ?>/student/bookmarks.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
              <span><i class="fas fa-bookmark me-2 text-info"></i> Saved Bookmarks & Notes</span>
              <i class="fas fa-chevron-right text-muted text-xs"></i>
            </a>
            <a href="<?= BASE_URL ?>/student/reading-statistics.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
              <span><i class="fas fa-chart-line me-2 text-warning"></i> Reading Analytics</span>
              <i class="fas fa-chevron-right text-muted text-xs"></i>
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
