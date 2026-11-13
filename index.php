<?php
/**
 * Public Home / Landing Page
 * Online Book Reading & Library Management System
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'OpenShelf - Digital Library & Reading Platform';

// Fetch Featured Books
$featuredBooks = db_fetch_all(
    "SELECT b.*, p.name as publisher_name,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list,
            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') as available_copies,
            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id) as total_copies,
            (SELECT AVG(rating) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as total_reviews
     FROM books b
     LEFT JOIN publishers p ON b.publisher_id = p.id
     WHERE b.status = 'active'
     ORDER BY b.id ASC LIMIT 6"
);

// Fetch Categories
$categories = db_fetch_all(
    "SELECT c.*, (SELECT COUNT(*) FROM book_categories bc WHERE bc.category_id = c.id) as book_count
     FROM categories c
     ORDER BY book_count DESC LIMIT 6"
);

// Stats
$totalBooksCount = db_count('books', "status = 'active'");
$totalCopiesCount = db_count('book_copies');
$totalMembersCount = db_count('users', "role_id = 3 AND status = 'active'");
$totalDigitalCount = db_count('books', "is_digital = 1 AND status = 'active'");

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="py-5" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%); color: #ffffff;">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill fw-bold text-uppercase" style="letter-spacing: 0.05em;">
          <i class="fas fa-sparkles me-1 text-warning"></i> Next-Gen Academic Library
        </span>
        <h1 class="display-4 fw-extrabold mb-3" style="line-height: 1.15;">
          Online Book Reading & <br><span style="color: #67e8f9;">Library Management</span> System
        </h1>
        <p class="lead text-white-50 mb-4" style="font-size: 1.15rem;">
          Bridging the physical and digital library. Discover physical copies across campus shelves, place queue reservations, read digital books instantly in your browser, and track your reading journey.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-warning btn-rounded px-4 py-3 fw-bold text-dark shadow-lg">
            <i class="fas fa-compass me-2"></i> Explore Catalogue
          </a>
          <a href="<?= BASE_URL ?>/student/catalogue.php?format=digital" class="btn btn-outline-light btn-rounded px-4 py-3 fw-semibold">
            <i class="fas fa-book-open-reader me-2"></i> Read Online
          </a>
          <?php if (!is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-link text-white text-decoration-none px-3 py-3 fw-medium">
              Member Sign In <i class="fas fa-arrow-right ms-1"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5 text-center">
        <div class="position-relative d-inline-block">
          <div class="p-4 bg-white rounded-5 shadow-2-strong text-dark text-start" style="max-width: 380px;">
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
              <div class="stat-icon primary">
                <i class="fas fa-book-bookmark"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold">Live Campus System</h6>
                <small class="text-muted">Circulation & Digital Access</small>
              </div>
            </div>
            <div class="row g-2 text-center mb-3">
              <div class="col-6">
                <div class="p-2 bg-light rounded-3">
                  <div class="fw-bold fs-5 text-primary"><?= $totalBooksCount ?></div>
                  <div class="text-xs text-muted">Book Titles</div>
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 bg-light rounded-3">
                  <div class="fw-bold fs-5 text-success"><?= $totalCopiesCount ?></div>
                  <div class="text-xs text-muted">Physical Copies</div>
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 bg-light rounded-3">
                  <div class="fw-bold fs-5 text-info"><?= $totalDigitalCount ?></div>
                  <div class="text-xs text-muted">Digital Titles</div>
                </div>
              </div>
              <div class="col-6">
                <div class="p-2 bg-light rounded-3">
                  <div class="fw-bold fs-5 text-warning"><?= $totalMembersCount ?></div>
                  <div class="text-xs text-muted">Active Members</div>
                </div>
              </div>
            </div>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary w-100 btn-rounded fw-semibold">
              <i class="fas fa-gauge me-1"></i> Launch Demo Console
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Flash Alerts -->
<div class="container mt-4">
  <?php render_flash(); ?>
</div>

<!-- Features Overview -->
<section class="py-5 bg-white">
  <div class="container py-3">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Everything You Need In One Unified Library</h2>
      <p class="text-muted">Designed for Students, Librarians, and Administrators</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="p-4 rounded-4 border bg-light h-100">
          <div class="stat-icon primary mb-3">
            <i class="fas fa-barcode"></i>
          </div>
          <h5 class="fw-bold mb-2">Physical Book Circulation</h5>
          <p class="text-muted text-sm mb-0">
            Real-time tracking of individual book copies, shelf locations, barcodes, automated due dates, returns, and overdue fine management.
          </p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-4 rounded-4 border bg-light h-100">
          <div class="stat-icon success mb-3">
            <i class="fas fa-glasses"></i>
          </div>
          <h5 class="fw-bold mb-2">In-Browser Digital Reader</h5>
          <p class="text-muted text-sm mb-0">
            Rich online reader with custom reading themes (Light, Dark, Sepia, Night), font sizing, bookmarks, personal notes, and continuous progress saving.
          </p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-4 rounded-4 border bg-light h-100">
          <div class="stat-icon info mb-3">
            <i class="fas fa-clock-rotate-left"></i>
          </div>
          <h5 class="fw-bold mb-2">Smart Queue Reservations</h5>
          <p class="text-muted text-sm mb-0">
            When all physical copies are borrowed, members join an automated waitlist. When returned, the next student is automatically notified!
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Books -->
<section class="py-5">
  <div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1">Featured & Popular Books</h3>
        <p class="text-muted text-sm mb-0">Curated titles available for physical borrowing and digital reading</p>
      </div>
      <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-outline-primary btn-rounded btn-sm">
        View All Books <i class="fas fa-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-4">
      <?php foreach ($featuredBooks as $b): ?>
        <div class="col-md-4 col-lg-2 col-sm-6">
          <div class="book-card shadow-sm h-100">
            <div class="book-cover-wrap">
              <img src="<?= get_book_cover($b['cover_image'], $b['title']) ?>" alt="<?= clean_output($b['title']) ?>" class="book-cover-img" loading="lazy">
              <div class="book-format-badge">
                <?php if ($b['is_digital']): ?>
                  <span class="badge bg-primary shadow-sm" title="Digital Reading Available">
                    <i class="fas fa-tablet-screen-button"></i> E-Book
                  </span>
                <?php endif; ?>
                <?php if ($b['is_physical']): ?>
                  <span class="badge bg-secondary shadow-sm" title="Physical Copy Available">
                    <i class="fas fa-book"></i> Physical
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="book-info">
              <h6 class="book-title" title="<?= clean_output($b['title']) ?>">
                <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['id'] ?>" class="text-decoration-none text-dark hover-primary">
                  <?= clean_output($b['title']) ?>
                </a>
              </h6>
              <div class="book-author"><?= clean_output($b['authors_list'] ?: 'Unknown Author') ?></div>

              <div class="book-meta">
                <div>
                  <?php if ($b['is_physical']): ?>
                    <?php if ($b['available_copies'] > 0): ?>
                      <span class="badge badge-available">
                        <?= $b['available_copies'] ?> / <?= $b['total_copies'] ?> avail
                      </span>
                    <?php else: ?>
                      <span class="badge badge-overdue">0 copies (Hold)</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="badge bg-light text-muted border">Digital Only</span>
                  <?php endif; ?>
                </div>

                <div class="star-rating">
                  <i class="fas fa-star text-warning"></i>
                  <span class="text-xs text-muted"><?= number_format((float)$b['avg_rating'], 1) ?></span>
                </div>
              </div>

              <div class="mt-3 pt-2 d-flex gap-1">
                <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary w-100 btn-rounded">
                  Details
                </a>
                <?php if ($b['is_digital']): ?>
                  <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $b['id'] ?>" class="btn btn-sm btn-primary px-3 btn-rounded" title="Read Online Now">
                    <i class="fas fa-glasses"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Categories Grid -->
<section class="py-5 bg-white">
  <div class="container py-3">
    <div class="text-center mb-5">
      <h3 class="fw-bold">Browse by Category</h3>
      <p class="text-muted">Find exactly what you want to read across academic and literature disciplines</p>
    </div>

    <div class="row g-4">
      <?php foreach ($categories as $cat): ?>
        <div class="col-md-4 col-sm-6">
          <a href="<?= BASE_URL ?>/student/catalogue.php?category=<?= $cat['id'] ?>" class="text-decoration-none">
            <div class="card p-3 border h-100 card-hover">
              <div class="d-flex align-items-center gap-3">
                <div class="stat-icon primary">
                  <i class="<?= clean_output($cat['icon'] ?: 'fas fa-book') ?>"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-1 text-dark"><?= clean_output($cat['name']) ?></h6>
                  <span class="text-muted text-xs"><?= $cat['book_count'] ?> titles in collection</span>
                </div>
                <div class="ms-auto text-muted">
                  <i class="fas fa-chevron-right"></i>
                </div>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
