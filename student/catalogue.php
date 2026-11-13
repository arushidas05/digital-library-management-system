<?php
/**
 * Book Catalogue Page (Search, Filter, Sort)
 */
require_once __DIR__ . '/../config.php';

$pageTitle = 'Book Catalogue - OpenShelf Library';

// Query parameters
$search = trim($_GET['search'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$format = trim($_GET['format'] ?? 'all'); // 'all', 'digital', 'physical'
$availability = trim($_GET['avail'] ?? 'all'); // 'all', 'available'
$sortBy = trim($_GET['sort'] ?? 'title'); // 'title', 'rating', 'newest'

// Build query
$whereClauses = ["b.status = 'active'"];
$params = [];
$types = "";

if (!empty($search)) {
    $whereClauses[] = "(b.title LIKE ? OR b.isbn LIKE ? OR a.name LIKE ? OR p.name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if ($categoryId > 0) {
    $whereClauses[] = "EXISTS (SELECT 1 FROM book_categories bc WHERE bc.book_id = b.id AND bc.category_id = ?)";
    $params[] = $categoryId;
    $types .= "i";
}

if ($format === 'digital') {
    $whereClauses[] = "b.is_digital = 1";
} elseif ($format === 'physical') {
    $whereClauses[] = "b.is_physical = 1";
}

if ($availability === 'available') {
    $whereClauses[] = "EXISTS (SELECT 1 FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available')";
}

$whereSql = implode(" AND ", $whereClauses);

// Sorting
$orderSql = "b.title ASC";
if ($sortBy === 'rating') {
    $orderSql = "avg_rating DESC, b.title ASC";
} elseif ($sortBy === 'newest') {
    $orderSql = "b.created_at DESC";
}

$sql = "SELECT DISTINCT b.*, p.name as publisher_name,
               (SELECT GROUP_CONCAT(a2.name SEPARATOR ', ') FROM book_authors ba2 JOIN authors a2 ON ba2.author_id = a2.id WHERE ba2.book_id = b.id) as authors_list,
               (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') as available_copies,
               (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id) as total_copies,
               (SELECT AVG(rating) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as avg_rating,
               (SELECT COUNT(*) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as total_reviews
        FROM books b
        LEFT JOIN publishers p ON b.publisher_id = p.id
        LEFT JOIN book_authors ba ON b.id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.id
        WHERE {$whereSql}
        ORDER BY {$orderSql}";

$books = db_fetch_all($sql, $params, $types);

// Fetch categories for sidebar filter
$allCategories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
      <h2 class="fw-bold mb-1"><i class="fas fa-book-open text-primary me-2"></i> Library Book Catalogue</h2>
      <p class="text-muted text-sm mb-0">Showing <?= count($books) ?> books available in our collection</p>
    </div>
    <div>
      <?php if (is_student()): ?>
        <a href="<?= BASE_URL ?>/student/my-library.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-book-reader me-1"></i> My Reading Shelf
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php render_flash(); ?>

  <!-- Search & Filter Controls -->
  <div class="card p-3 mb-4 shadow-sm border">
    <form action="<?= BASE_URL ?>/student/catalogue.php" method="GET" class="row g-3 align-items-end">
      <!-- Search input -->
      <div class="col-md-4">
        <label for="search" class="form-label text-xs fw-bold text-uppercase text-muted">Keywords</label>
        <div class="input-group">
          <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
          <input type="text" class="form-control" id="search" name="search" value="<?= clean_output($search) ?>" placeholder="Title, author, or ISBN...">
        </div>
      </div>

      <!-- Category Filter -->
      <div class="col-md-3">
        <label for="category" class="form-label text-xs fw-bold text-uppercase text-muted">Category / Genre</label>
        <select class="form-select" id="category" name="category">
          <option value="0">All Categories</option>
          <?php foreach ($allCategories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
              <?= clean_output($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Format Filter -->
      <div class="col-md-2">
        <label for="format" class="form-label text-xs fw-bold text-uppercase text-muted">Reading Format</label>
        <select class="form-select" id="format" name="format">
          <option value="all" <?= $format === 'all' ? 'selected' : '' ?>>All Formats</option>
          <option value="digital" <?= $format === 'digital' ? 'selected' : '' ?>>Digital E-Book</option>
          <option value="physical" <?= $format === 'physical' ? 'selected' : '' ?>>Physical Copy</option>
        </select>
      </div>

      <!-- Availability Filter -->
      <div class="col-md-2">
        <label for="avail" class="form-label text-xs fw-bold text-uppercase text-muted">Availability</label>
        <select class="form-select" id="avail" name="avail">
          <option value="all" <?= $availability === 'all' ? 'selected' : '' ?>>Any Status</option>
          <option value="available" <?= $availability === 'available' ? 'selected' : '' ?>>Available Now</option>
        </select>
      </div>

      <!-- Actions -->
      <div class="col-md-1 d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100" title="Apply Filters">
          <i class="fas fa-filter"></i>
        </button>
        <?php if (!empty($search) || $categoryId > 0 || $format !== 'all' || $availability !== 'all'): ?>
          <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-outline-secondary" title="Reset Filters">
            <i class="fas fa-undo"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Catalogue Results Grid -->
  <?php if (empty($books)): ?>
    <div class="card p-5 text-center text-muted border">
      <i class="fas fa-search fs-1 mb-3 text-light"></i>
      <h5 class="fw-bold text-dark">No Books Found</h5>
      <p class="mb-3 text-sm">We couldn't find any books matching your search filters. Try broadening your keywords or resetting filters.</p>
      <div>
        <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-primary btn-rounded btn-sm">Clear Filters</a>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($books as $b): ?>
        <div class="col-xl-3 col-lg-4 col-md-6 catalogue-book-col"
             data-title="<?= clean_output($b['title']) ?>"
             data-author="<?= clean_output($b['authors_list']) ?>"
             data-isbn="<?= clean_output($b['isbn']) ?>">
          <div class="book-card shadow-sm h-100">
            <div class="book-cover-wrap">
              <img src="<?= get_book_cover($b['cover_image'], $b['title']) ?>" alt="<?= clean_output($b['title']) ?>" class="book-cover-img" loading="lazy">
              
              <div class="book-format-badge">
                <?php if ($b['is_digital']): ?>
                  <span class="badge bg-primary shadow-sm" title="E-Book Available">
                    <i class="fas fa-tablet-screen-button"></i> E-Book
                  </span>
                <?php endif; ?>
                <?php if ($b['is_physical']): ?>
                  <span class="badge bg-secondary shadow-sm" title="Physical Library Copy">
                    <i class="fas fa-book"></i> Physical
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="book-info">
              <h5 class="book-title mb-1">
                <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['id'] ?>" class="text-decoration-none text-dark hover-primary" title="<?= clean_output($b['title']) ?>">
                  <?= clean_output($b['title']) ?>
                </a>
              </h5>
              
              <div class="book-author">
                <i class="fas fa-pen-nib text-muted me-1"></i> <?= clean_output($b['authors_list'] ?: 'Unknown Author') ?>
              </div>

              <div class="book-meta">
                <div>
                  <?php if ($b['is_physical']): ?>
                    <?php if ($b['available_copies'] > 0): ?>
                      <span class="badge badge-available">
                        <i class="fas fa-circle-check me-1"></i> <?= $b['available_copies'] ?> / <?= $b['total_copies'] ?> Available
                      </span>
                    <?php else: ?>
                      <span class="badge badge-overdue">
                        <i class="fas fa-clock me-1"></i> All Copies Borrowed
                      </span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="badge bg-light text-muted border">Digital Online Only</span>
                  <?php endif; ?>
                </div>

                <div class="star-rating" title="Rating: <?= number_format((float)$b['avg_rating'], 1) ?> out of 5">
                  <i class="fas fa-star text-warning"></i>
                  <span class="text-xs fw-bold text-dark"><?= number_format((float)$b['avg_rating'], 1) ?></span>
                  <span class="text-xs text-muted">(<?= $b['total_reviews'] ?>)</span>
                </div>
              </div>

              <div class="mt-3 pt-2 d-flex gap-2">
                <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1 btn-rounded">
                  <i class="fas fa-circle-info me-1"></i> Details
                </a>
                <?php if ($b['is_digital']): ?>
                  <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $b['id'] ?>" class="btn btn-primary btn-sm px-3 btn-rounded" title="Read Digital Book Online">
                    <i class="fas fa-glasses me-1"></i> Read
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
