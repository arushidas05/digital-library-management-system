<?php
/**
 * Member Submitted Reviews Page
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'My Reviews - OpenShelf';
$user = current_user();
$userId = $user['id'];

// Handle Delete Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $revId = (int)($_POST['review_id'] ?? 0);
    db_execute("DELETE FROM reviews WHERE id = ? AND user_id = ?", [$revId, $userId], "ii");
    set_flash('info', 'Your review has been removed.');
    header("Location: " . BASE_URL . "/student/reviews.php");
    exit;
}

// Fetch user's reviews
$reviews = db_fetch_all(
    "SELECT r.*, b.title as book_title, b.cover_image, b.id as book_id
     FROM reviews r
     JOIN books b ON r.book_id = b.id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-star text-warning me-2"></i> My Reviews & Ratings</h3>
        <p class="text-muted text-sm mb-0">Book ratings, feedback, and critiques you have published to the community</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-search me-1"></i> Review More Books
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <?php if (empty($reviews)): ?>
      <div class="card p-5 text-center text-muted border shadow-sm">
        <i class="far fa-comments fs-1 text-light mb-3"></i>
        <h5 class="fw-bold text-dark">No Reviews Submitted Yet</h5>
        <p class="text-sm">Read books in our catalogue and share your ratings and thoughts with fellow students!</p>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($reviews as $rev): ?>
          <div class="col-md-6">
            <div class="card p-3 border shadow-sm h-100 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="<?= get_book_cover($rev['cover_image'], $rev['book_title']) ?>" alt="Cover" style="width: 36px; height: 50px; object-fit: cover; border-radius: 4px;">
                  <div>
                    <h6 class="fw-bold mb-0">
                      <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $rev['book_id'] ?>" class="text-dark text-decoration-none hover-primary">
                        <?= clean_output($rev['book_title']) ?>
                      </a>
                    </h6>
                    <small class="text-xs text-muted"><?= format_date($rev['created_at']) ?></small>
                  </div>
                </div>

                <div class="star-rating">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= $rev['rating'] ? 'fas fa-star text-warning' : 'far fa-star text-muted' ?>"></i>
                  <?php endfor; ?>
                </div>
              </div>

              <div class="p-3 bg-light rounded-3 my-2 text-sm text-dark flex-grow-1">
                <?= nl2br(clean_output($rev['review_text'])) ?>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> <?= clean_output($rev['status']) ?></span>

                <form action="<?= BASE_URL ?>/student/reviews.php" method="POST" onsubmit="return confirm('Delete this review?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm border-0" title="Delete Review">
                    <i class="fas fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
