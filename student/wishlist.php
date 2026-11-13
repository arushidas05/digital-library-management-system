<?php
/**
 * Student Wishlist Page
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'My Wishlist - OpenShelf';
$user = current_user();
$userId = $user['id'];

// Handle Remove Form Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    db_execute("DELETE FROM wishlists WHERE user_id = ? AND book_id = ?", [$userId, $bookId], "ii");
    set_flash('info', 'Book removed from your wishlist.');
    header("Location: " . BASE_URL . "/student/wishlist.php");
    exit;
}

// Fetch Wishlist Items
$wishlist = db_fetch_all(
    "SELECT w.*, b.title, b.cover_image, b.is_digital, b.is_physical,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list,
            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') as available_copies,
            (SELECT AVG(rating) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as avg_rating
     FROM wishlists w
     JOIN books b ON w.book_id = b.id
     WHERE w.user_id = ?
     ORDER BY w.added_at DESC",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-heart text-danger me-2"></i> My Wishlist</h3>
        <p class="text-muted text-sm mb-0">Books you have bookmarked to read or borrow later</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-search me-1"></i> Explore More Books
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <?php if (empty($wishlist)): ?>
      <div class="card p-5 text-center text-muted border shadow-sm">
        <i class="far fa-heart fs-1 mb-3 text-light"></i>
        <h5 class="fw-bold text-dark">Your Wishlist is Empty</h5>
        <p class="text-sm">Save your favorite titles to read later by clicking the heart button on any book card.</p>
        <div>
          <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-primary btn-sm btn-rounded">Browse Books</a>
        </div>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($wishlist as $item): ?>
          <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="book-card shadow-sm h-100">
              <div class="book-cover-wrap">
                <img src="<?= get_book_cover($item['cover_image'], $item['title']) ?>" alt="Cover" class="book-cover-img">
                <div class="book-format-badge">
                  <?php if ($item['is_digital']): ?>
                    <span class="badge bg-primary"><i class="fas fa-tablet-screen-button"></i> E-Book</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="book-info">
                <h6 class="book-title mb-1">
                  <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $item['book_id'] ?>" class="text-dark text-decoration-none hover-primary">
                    <?= clean_output($item['title']) ?>
                  </a>
                </h6>
                <div class="book-author"><?= clean_output($item['authors_list']) ?></div>

                <div class="book-meta">
                  <span class="text-xs text-muted">Added: <?= format_date($item['added_at']) ?></span>
                  <div class="star-rating">
                    <i class="fas fa-star text-warning"></i>
                    <span class="text-xs text-dark fw-bold"><?= number_format((float)$item['avg_rating'], 1) ?></span>
                  </div>
                </div>

                <div class="mt-3 pt-2 d-flex gap-2">
                  <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $item['book_id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1 btn-rounded">Details</a>
                  <?php if ($item['is_digital']): ?>
                    <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $item['book_id'] ?>" class="btn btn-primary btn-sm px-3 btn-rounded" title="Read Online">
                      <i class="fas fa-glasses"></i>
                    </a>
                  <?php endif; ?>
                  <form action="<?= BASE_URL ?>/student/wishlist.php" method="POST" class="d-inline">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="book_id" value="<?= $item['book_id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm btn-rounded" title="Remove from Wishlist">
                      <i class="fas fa-trash-can"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
