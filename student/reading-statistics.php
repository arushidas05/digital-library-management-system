<?php
/**
 * Member Reading Statistics & Analytics
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'Reading Analytics - OpenShelf';
$user = current_user();
$userId = $user['id'];

// Aggregate stats
$booksInProgress = db_count('reading_progress', "user_id = ? AND progress_percentage < 100", [$userId], "i");
$booksCompleted = db_count('reading_progress', "user_id = ? AND progress_percentage >= 100", [$userId], "i");
$bookmarksCount = db_count('bookmarks', "user_id = ?", [$userId], "i");
$notesCount = db_count('notes', "user_id = ?", [$userId], "i");
$reviewsCount = db_count('reviews', "user_id = ?", [$userId], "i");

$totalPagesRow = db_fetch_one(
    "SELECT SUM(rp.current_page) as total_pages_read 
     FROM reading_progress rp 
     WHERE rp.user_id = ?",
    [$userId],
    "i"
);
$totalPagesRead = (int)($totalPagesRow['total_pages_read'] ?? 0);

// Reading progress list
$progressList = db_fetch_all(
    "SELECT rp.*, b.title, b.cover_image, b.total_pages
     FROM reading_progress rp
     JOIN books b ON rp.book_id = b.id
     WHERE rp.user_id = ?
     ORDER BY rp.progress_percentage DESC",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-chart-pie text-primary me-2"></i> Personal Reading Analytics</h3>
        <p class="text-muted text-sm mb-0">Insights into your reading velocity, completion milestones, and study activity</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/my-library.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-book-open me-1"></i> My Library Shelf
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Stat KPI Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon success">
            <i class="fas fa-trophy"></i>
          </div>
          <div>
            <div class="stat-value"><?= $booksCompleted ?></div>
            <div class="stat-label">Books Completed</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon info">
            <i class="fas fa-book-reader"></i>
          </div>
          <div>
            <div class="stat-value"><?= $booksInProgress ?></div>
            <div class="stat-label">In Progress</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon primary">
            <i class="fas fa-bookmark"></i>
          </div>
          <div>
            <div class="stat-value"><?= $bookmarksCount ?></div>
            <div class="stat-label">Bookmarks Saved</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon warning">
            <i class="fas fa-pen-to-square"></i>
          </div>
          <div>
            <div class="stat-value"><?= $notesCount ?></div>
            <div class="stat-label">Notes Taken</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reading Progress Breakdown -->
    <div class="card shadow-sm border mb-4">
      <div class="card-header bg-white fw-bold">
        <i class="fas fa-bars-progress text-primary me-2"></i> Reading Completion Breakdown by Title
      </div>
      <div class="card-body p-4">
        <?php if (empty($progressList)): ?>
          <p class="text-muted text-center py-4 mb-0">No reading progress recorded yet. Open a digital book to start tracking.</p>
        <?php else: ?>
          <div class="d-flex flex-column gap-4">
            <?php foreach ($progressList as $item): ?>
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div class="fw-bold text-dark">
                    <?= clean_output($item['title']) ?>
                    <span class="text-muted text-xs ms-2">(Chapter <?= $item['current_chapter'] ?>, Page <?= $item['current_page'] ?>)</span>
                  </div>
                  <div class="fw-bold text-sm <?= $item['progress_percentage'] >= 100 ? 'text-success' : 'text-primary' ?>">
                    <?= (int)$item['progress_percentage'] ?>%
                  </div>
                </div>
                <div class="progress" style="height: 10px;">
                  <div class="progress-bar <?= $item['progress_percentage'] >= 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $item['progress_percentage'] ?>%;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
