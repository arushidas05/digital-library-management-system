<?php
/**
 * Book Details Page
 * Shows logical metadata, physical copy availability, digital reading link, reservation option, and reviews.
 */
require_once __DIR__ . '/../config.php';

$bookId = (int)($_GET['id'] ?? 0);
if ($bookId <= 0) {
    set_flash('danger', 'Invalid book requested.');
    header("Location: " . BASE_URL . "/student/catalogue.php");
    exit;
}

// Fetch logical book
$book = db_fetch_one(
    "SELECT b.*, p.name as publisher_name, p.address as publisher_address,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list,
            (SELECT AVG(rating) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as total_reviews
     FROM books b
     LEFT JOIN publishers p ON b.publisher_id = p.id
     WHERE b.id = ? AND b.status = 'active'",
    [$bookId],
    "i"
);

if (!$book) {
    set_flash('danger', 'The requested book was not found or is archived.');
    header("Location: " . BASE_URL . "/student/catalogue.php");
    exit;
}

$pageTitle = clean_output($book['title']) . ' - OpenShelf';
$currentUser = current_user();
$userId = $currentUser ? $currentUser['id'] : 0;

// Fetch book categories
$categories = db_fetch_all(
    "SELECT c.id, c.name, c.icon FROM book_categories bc JOIN categories c ON bc.category_id = c.id WHERE bc.book_id = ?",
    [$bookId],
    "i"
);

// Fetch physical copies
$copies = db_fetch_all(
    "SELECT * FROM book_copies WHERE book_id = ? ORDER BY copy_number ASC",
    [$bookId],
    "i"
);

$availableCopiesCount = 0;
foreach ($copies as $cp) {
    if ($cp['status'] === 'Available') {
        $availableCopiesCount++;
    }
}

// Fetch Digital Book info if digital
$digitalBook = null;
$readingProgress = null;
if ($book['is_digital']) {
    $digitalBook = db_fetch_one("SELECT * FROM digital_books WHERE book_id = ?", [$bookId], "i");
    if ($userId > 0) {
        $readingProgress = db_fetch_one("SELECT * FROM reading_progress WHERE user_id = ? AND book_id = ?", [$userId, $bookId], "ii");
    }
}

// Check Wishlist status
$inWishlist = false;
if ($userId > 0) {
    $wishlistCheck = db_fetch_one("SELECT id FROM wishlists WHERE user_id = ? AND book_id = ?", [$userId, $bookId], "ii");
    $inWishlist = (bool)$wishlistCheck;
}

// Check Active Reservation status
$userReservation = null;
if ($userId > 0) {
    $userReservation = db_fetch_one(
        "SELECT * FROM reservations WHERE user_id = ? AND book_id = ? AND status IN ('Pending', 'Ready')",
        [$userId, $bookId],
        "ii"
    );
}

// Handle Reservation Form Action (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve') {
    if (!$currentUser) {
        set_flash('warning', 'Please sign in to place a book reservation.');
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }

    if ($availableCopiesCount > 0) {
        set_flash('info', 'There are physical copies currently available on shelf. You can borrow directly from the circulation desk!');
    } elseif ($userReservation) {
        set_flash('warning', 'You already have an active reservation for this book (Queue #' . $userReservation['queue_position'] . ').');
    } else {
        // Calculate next queue position
        $queueRow = db_fetch_one("SELECT MAX(queue_position) as max_pos FROM reservations WHERE book_id = ? AND status = 'Pending'", [$bookId], "i");
        $nextPos = ((int)($queueRow['max_pos'] ?? 0)) + 1;

        db_execute(
            "INSERT INTO reservations (user_id, book_id, queue_position, status) VALUES (?, ?, ?, 'Pending')",
            [$userId, $bookId, $nextPos],
            "iii"
        );

        send_notification(
            $userId,
            'Reservation Placed: ' . $book['title'],
            "You have been placed in the reservation waitlist at queue position #{$nextPos}. We will notify you once a copy is returned.",
            'reservation',
            'reservations.php'
        );

        log_audit('RESERVATION_PLACED', "Member {$currentUser['username']} reserved book: {$book['title']} (Queue #{$nextPos})");

        set_flash('success', "Reservation placed successfully! You are #{$nextPos} in the queue.");
        header("Location: " . BASE_URL . "/student/book_details.php?id=" . $bookId);
        exit;
    }
}

// Handle Review Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review') {
    if (!$currentUser) {
        set_flash('warning', 'Please sign in to submit a review.');
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }

    $rating = (int)($_POST['rating'] ?? 5);
    $reviewText = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) $rating = 5;

    if (empty($reviewText)) {
        set_flash('danger', 'Please enter your review text.');
    } else {
        // Check if user already reviewed
        $existingReview = db_fetch_one("SELECT id FROM reviews WHERE user_id = ? AND book_id = ?", [$userId, $bookId], "ii");
        if ($existingReview) {
            db_execute("UPDATE reviews SET rating = ?, review_text = ?, status = 'Approved' WHERE id = ?", [$rating, $reviewText, $existingReview['id']], "isi");
            set_flash('success', 'Your review has been updated!');
        } else {
            db_execute("INSERT INTO reviews (user_id, book_id, rating, review_text, status) VALUES (?, ?, ?, ?, 'Approved')", [$userId, $bookId, $rating, $reviewText], "iiis");
            set_flash('success', 'Thank you! Your review has been posted.');
        }
        log_audit('REVIEW_SUBMITTED', "User {$currentUser['username']} reviewed book #{$bookId} ({$rating} stars)");
        header("Location: " . BASE_URL . "/student/book_details.php?id=" . $bookId);
        exit;
    }
}

// Fetch Reviews
$reviews = db_fetch_all(
    "SELECT r.*, u.username, p.full_name, p.avatar
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE r.book_id = ? AND r.status = 'Approved'
     ORDER BY r.created_at DESC",
    [$bookId],
    "i"
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/student/catalogue.php">Catalogue</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= clean_output($book['title']) ?></li>
    </ol>
  </nav>

  <?php render_flash(); ?>

  <div class="row g-4 mb-5">
    <!-- Book Cover & Actions Left -->
    <div class="col-lg-4 col-md-5 text-center">
      <div class="card p-3 border shadow-sm">
        <img src="<?= get_book_cover($book['cover_image'], $book['title']) ?>" alt="<?= clean_output($book['title']) ?>" class="book-detail-cover mx-auto mb-3">

        <div class="d-flex flex-column gap-2 mt-2">
          <?php if ($book['is_digital']): ?>
            <?php if ($readingProgress): ?>
              <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=<?= $readingProgress['current_chapter'] ?>" class="btn btn-warning btn-rounded fw-bold py-2 shadow-sm text-dark">
                <i class="fas fa-book-open me-1"></i> Continue Reading (<?= (int)$readingProgress['progress_percentage'] ?>%)
              </a>
            <?php else: ?>
              <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>" class="btn btn-primary btn-rounded fw-bold py-2 shadow-sm">
                <i class="fas fa-glasses me-1"></i> Read Online E-Book
              </a>
            <?php endif; ?>
          <?php endif; ?>

          <?php if ($currentUser): ?>
            <button type="button" class="btn btn-outline-danger btn-rounded btn-wishlist-toggle" data-book-id="<?= $book['id'] ?>">
              <i class="<?= $inWishlist ? 'fas text-danger' : 'far' ?> fa-heart me-1"></i>
              <?= $inWishlist ? 'Saved in Wishlist' : 'Add to Wishlist' ?>
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Metadata Details -->
      <div class="card p-3 mt-3 border text-start shadow-sm text-sm">
        <div class="fw-bold text-muted text-xs text-uppercase mb-2">Book Information</div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">ISBN-13:</span>
          <span class="fw-semibold"><code><?= clean_output($book['isbn']) ?></code></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Publisher:</span>
          <span class="fw-semibold"><?= clean_output($book['publisher_name'] ?: 'Not Specified') ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Year:</span>
          <span class="fw-semibold"><?= clean_output($book['publication_year'] ?: '—') ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Edition:</span>
          <span class="fw-semibold"><?= clean_output($book['edition'] ?: 'Standard') ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
          <span class="text-muted">Language:</span>
          <span class="fw-semibold"><?= clean_output($book['language'] ?: 'English') ?></span>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Pages:</span>
          <span class="fw-semibold"><?= clean_output($book['total_pages'] ?: 'N/A') ?></span>
        </div>
      </div>
    </div>

    <!-- Book Details & Actions Right -->
    <div class="col-lg-8 col-md-7">
      <div class="card p-4 border shadow-sm">
        <!-- Categories -->
        <div class="d-flex flex-wrap gap-2 mb-2">
          <?php foreach ($categories as $cat): ?>
            <span class="badge bg-light text-primary border">
              <i class="<?= clean_output($cat['icon'] ?: 'fas fa-tag') ?> me-1"></i> <?= clean_output($cat['name']) ?>
            </span>
          <?php endforeach; ?>
        </div>

        <h2 class="fw-bold mb-2"><?= clean_output($book['title']) ?></h2>
        
        <p class="text-muted mb-3 fs-6">
          By <strong class="text-dark"><?= clean_output($book['authors_list'] ?: 'Unknown Author') ?></strong>
        </p>

        <!-- Rating Overview -->
        <div class="d-flex align-items-center gap-2 mb-4">
          <div class="star-rating fs-5">
            <?php
              $score = round((float)$book['avg_rating']);
              for ($i = 1; $i <= 5; $i++) {
                  echo $i <= $score ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star"></i>';
              }
            ?>
          </div>
          <span class="fw-bold fs-6"><?= number_format((float)$book['avg_rating'], 1) ?></span>
          <span class="text-muted text-sm">(<?= $book['total_reviews'] ?> member reviews)</span>
        </div>

        <hr class="my-3">

        <!-- Synopsis -->
        <h5 class="fw-bold mb-2">About this Book</h5>
        <p class="text-muted" style="line-height: 1.75;">
          <?= nl2br(clean_output($book['description'] ?: 'No synopsis currently available for this title.')) ?>
        </p>

        <hr class="my-4">

        <!-- Physical Copy Availability Section (FR-04, FR-06, FR-08, M03, M05) -->
        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h5 class="fw-bold mb-0"><i class="fas fa-boxes-stacked text-primary me-2"></i> Physical Copies & Shelf Availability</h5>
              <small class="text-muted">Total physical inventory registered in campus stacks</small>
            </div>
            <div>
              <?php if ($availableCopiesCount > 0): ?>
                <span class="badge badge-available fs-6 px-3 py-2">
                  <i class="fas fa-check-circle me-1"></i> <?= $availableCopiesCount ?> of <?= count($copies) ?> Copies Available
                </span>
              <?php else: ?>
                <span class="badge badge-overdue fs-6 px-3 py-2">
                  <i class="fas fa-triangle-exclamation me-1"></i> 0 of <?= count($copies) ?> Copies Available
                </span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (empty($copies)): ?>
            <div class="alert alert-light border text-muted">
              <i class="fas fa-info-circle me-2"></i> No physical copies currently configured for this title. Available as digital e-book.
            </div>
          <?php else: ?>
            <div class="table-responsive border rounded-3 mb-3">
              <table class="table table-sm table-hover mb-0">
                <thead class="bg-light">
                  <tr>
                    <th>Copy #</th>
                    <th>Barcode</th>
                    <th>Location / Shelf</th>
                    <th>Status</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($copies as $cp): ?>
                    <tr>
                      <td class="fw-semibold"><?= clean_output($cp['copy_number']) ?></td>
                      <td><code><?= clean_output($cp['barcode']) ?></code></td>
                      <td><i class="fas fa-location-dot text-danger me-1"></i> <?= clean_output($cp['shelf_location']) ?></td>
                      <td>
                        <?php if ($cp['status'] === 'Available'): ?>
                          <span class="badge badge-available">Available</span>
                        <?php elseif ($cp['status'] === 'Borrowed'): ?>
                          <span class="badge badge-borrowed">Borrowed</span>
                        <?php elseif ($cp['status'] === 'Reserved'): ?>
                          <span class="badge badge-reserved">Reserved</span>
                        <?php else: ?>
                          <span class="badge bg-secondary"><?= clean_output($cp['status']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-xs text-muted"><?= clean_output($cp['condition_notes'] ?: 'Good') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Reservation Callout if 0 copies are available -->
            <?php if ($availableCopiesCount === 0): ?>
              <div class="p-3 bg-light border border-warning rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-clock text-warning me-2"></i> All Physical Copies are Currently Borrowed</h6>
                  <p class="text-xs text-muted mb-0">You can place a reservation to reserve the next available copy returned by a student.</p>
                </div>

                <div>
                  <?php if ($userReservation): ?>
                    <button class="btn btn-outline-primary btn-sm" disabled>
                      <i class="fas fa-check me-1"></i> You are #<?= $userReservation['queue_position'] ?> in queue
                    </button>
                  <?php else: ?>
                    <form action="<?= BASE_URL ?>/student/book_details.php?id=<?= $bookId ?>" method="POST" class="d-inline">
                      <input type="hidden" name="action" value="reserve">
                      <button type="submit" class="btn btn-warning btn-sm btn-rounded fw-bold text-dark shadow-sm">
                        <i class="fas fa-bookmark me-1"></i> Place Reservation Hold
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php else: ?>
              <div class="p-3 bg-light border rounded-3 d-flex align-items-center gap-2 text-sm text-muted">
                <i class="fas fa-circle-check text-success fs-5"></i>
                <span>Copies are available on shelf! Visit the circulation desk with your Student ID and ask the librarian for barcode <code><?= clean_output($copies[0]['barcode']) ?></code>.</span>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <!-- Reviews & Ratings Section (FR-13, M09) -->
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card p-4 border shadow-sm">
        <h4 class="fw-bold mb-4"><i class="fas fa-comments text-primary me-2"></i> Member Reviews & Discussion</h4>

        <!-- Submit Review Box -->
        <?php if ($currentUser): ?>
          <div class="p-3 bg-light border rounded-3 mb-4">
            <h6 class="fw-bold mb-2">Write a Review for this Book</h6>
            <form action="<?= BASE_URL ?>/student/book_details.php?id=<?= $bookId ?>" method="POST">
              <input type="hidden" name="action" value="review">

              <div class="mb-3">
                <label class="form-label text-xs fw-bold text-uppercase text-muted">Your Star Rating</label>
                <div class="star-rating-input d-block">
                  <input type="radio" id="star5" name="rating" value="5" checked><label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                  <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                  <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                  <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                  <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                </div>
              </div>

              <div class="mb-3">
                <label for="review_text" class="form-label text-xs fw-bold text-uppercase text-muted">Review Content</label>
                <textarea class="form-control" id="review_text" name="review_text" rows="3" placeholder="Share your thoughts, insights, or recommendations about this book..." required></textarea>
              </div>

              <button type="submit" class="btn btn-primary btn-sm btn-rounded px-3">
                <i class="fas fa-paper-plane me-1"></i> Post Review
              </button>
            </form>
          </div>
        <?php else: ?>
          <div class="alert alert-light border text-sm text-center">
            <a href="<?= BASE_URL ?>/login.php" class="text-primary fw-bold">Sign in</a> to leave your rating and review for this book.
          </div>
        <?php endif; ?>

        <!-- Existing Reviews List -->
        <?php if (empty($reviews)): ?>
          <p class="text-muted text-center py-4">No reviews yet for this title. Be the first to share your thoughts!</p>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($reviews as $rev): ?>
              <div class="p-3 border rounded-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?= get_user_avatar($rev['avatar'], $rev['full_name'] ?: $rev['username']) ?>" class="avatar-img" style="width: 32px; height: 32px;" alt="Avatar">
                    <div>
                      <div class="fw-bold text-sm text-dark"><?= clean_output($rev['full_name'] ?: $rev['username']) ?></div>
                      <div class="text-xs text-muted"><?= time_ago($rev['created_at']) ?></div>
                    </div>
                  </div>
                  <div class="star-rating">
                    <?php
                      for ($i = 1; $i <= 5; $i++) {
                          echo $i <= $rev['rating'] ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star"></i>';
                      }
                    ?>
                  </div>
                </div>
                <p class="text-muted text-sm mb-0"><?= nl2br(clean_output($rev['review_text'])) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
