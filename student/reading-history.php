<?php
/**
 * Member Reading History
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'Reading History - OpenShelf';
$user = current_user();
$userId = $user['id'];

$history = db_fetch_all(
    "SELECT rp.*, b.title, b.cover_image, b.total_pages,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list
     FROM reading_progress rp
     JOIN books b ON rp.book_id = b.id
     WHERE rp.user_id = ?
     ORDER BY rp.last_read_at DESC",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-clock text-primary me-2"></i> Reading History</h3>
        <p class="text-muted text-sm mb-0">Track your digital reading journey, chapters explored, and time spent reading</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/reading-statistics.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-chart-line me-1"></i> View Reading Analytics
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <?php if (empty($history)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-book-open-reader fs-1 text-light mb-2"></i>
            <p class="mb-0">You haven't opened any digital books yet.</p>
            <a href="<?= BASE_URL ?>/student/catalogue.php?format=digital" class="btn btn-primary btn-sm btn-rounded mt-3">Start Reading Today</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Book Title</th>
                  <th>Last Read Position</th>
                  <th>Progress</th>
                  <th>Last Reading Activity</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($history as $h): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img src="<?= get_book_cover($h['cover_image'], $h['title']) ?>" alt="Cover" style="width: 38px; height: 50px; object-fit: cover; border-radius: 4px;">
                        <div>
                          <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $h['book_id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                            <?= clean_output($h['title']) ?>
                          </a>
                          <small class="text-muted d-block"><?= clean_output($h['authors_list']) ?></small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-primary border">Chapter <?= $h['current_chapter'] ?></span>
                      <small class="text-muted ms-1">Page <?= $h['current_page'] ?></small>
                    </td>
                    <td style="min-width: 140px;">
                      <div class="d-flex justify-content-between text-xs text-muted mb-1">
                        <span><?= (int)$h['progress_percentage'] ?>% completed</span>
                      </div>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar <?= $h['progress_percentage'] >= 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $h['progress_percentage'] ?>%;"></div>
                      </div>
                    </td>
                    <td>
                      <span class="text-sm text-dark d-block"><?= format_date($h['last_read_at'], 'M d, Y') ?></span>
                      <small class="text-muted text-xs"><?= time_ago($h['last_read_at']) ?></small>
                    </td>
                    <td>
                      <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $h['book_id'] ?>&chapter=<?= $h['current_chapter'] ?>" class="btn btn-primary btn-sm btn-rounded">
                        <i class="fas fa-book-open me-1"></i> Continue
                      </a>
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
