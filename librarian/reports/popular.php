<?php
/**
 * Librarian: Most Popular Books Report
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Most Popular Books - OpenShelf';

// Ranking of books by physical borrowing count + digital reading sessions
$popularBooks = db_fetch_all(
    "SELECT b.*,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list,
            (SELECT COUNT(*) FROM borrowings br JOIN book_copies bc ON br.book_copy_id = bc.id WHERE bc.book_id = b.id) as borrow_count,
            (SELECT COUNT(*) FROM reading_progress rp WHERE rp.book_id = b.id) as digital_readers_count,
            (SELECT COUNT(*) FROM wishlists w WHERE w.book_id = b.id) as wishlist_count,
            (SELECT AVG(rating) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as avg_rating
     FROM books b
     WHERE b.status = 'active'
     ORDER BY borrow_count DESC, digital_readers_count DESC, avg_rating DESC"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-fire text-danger me-2"></i> Most Popular Titles Ranking</h3>
        <p class="text-muted text-sm mb-0">Highest demand books ranked by physical borrow frequency and online reading engagement</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/reports/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Reports Overview
        </a>
      </div>
    </div>

    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 text-sm">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Book Title & Author</th>
                <th>ISBN</th>
                <th>Physical Borrows</th>
                <th>Digital Readers</th>
                <th>Wishlist Holds</th>
                <th>Avg. Rating</th>
              </tr>
            </thead>
            <tbody>
              <?php $rank = 1; foreach ($popularBooks as $pb): ?>
                <tr>
                  <td>
                    <?php if ($rank === 1): ?>
                      <span class="badge bg-warning text-dark"><i class="fas fa-crown me-1"></i> #1</span>
                    <?php elseif ($rank === 2): ?>
                      <span class="badge bg-secondary">#2</span>
                    <?php elseif ($rank === 3): ?>
                      <span class="badge bg-danger bg-opacity-75">#3</span>
                    <?php else: ?>
                      <span class="text-muted fw-bold">#<?= $rank ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img src="<?= get_book_cover($pb['cover_image'], $pb['title']) ?>" alt="Cover" style="width: 36px; height: 48px; object-fit: cover; border-radius: 4px;">
                      <div>
                        <a href="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $pb['id'] ?>" class="text-dark fw-bold text-decoration-none hover-primary">
                          <?= clean_output($pb['title']) ?>
                        </a>
                        <small class="text-muted d-block"><?= clean_output($pb['authors_list']) ?></small>
                      </div>
                    </div>
                  </td>
                  <td><code><?= clean_output($pb['isbn']) ?></code></td>
                  <td>
                    <span class="fw-bold text-primary fs-6"><?= $pb['borrow_count'] ?></span> checkouts
                  </td>
                  <td>
                    <span class="fw-bold text-success fs-6"><?= $pb['digital_readers_count'] ?></span> readers
                  </td>
                  <td>
                    <span class="text-danger fw-semibold"><i class="fas fa-heart me-1"></i> <?= $pb['wishlist_count'] ?></span>
                  </td>
                  <td>
                    <i class="fas fa-star text-warning"></i>
                    <strong><?= number_format((float)$pb['avg_rating'], 1) ?></strong>
                  </td>
                </tr>
              <?php $rank++; endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
