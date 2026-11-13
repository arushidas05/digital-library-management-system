<?php
/**
 * Personal Library Hub
 * Online Book Reading & Library Management System
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'My Library - OpenShelf';
$user = current_user();
$userId = $user['id'];

$activeTab = trim($_GET['tab'] ?? 'reading');

// 1. Currently Reading Books (<100% progress)
$readingBooks = db_fetch_all(
    "SELECT rp.*, b.title, b.cover_image, b.total_pages,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list
     FROM reading_progress rp
     JOIN books b ON rp.book_id = b.id
     WHERE rp.user_id = ? AND rp.progress_percentage < 100
     ORDER BY rp.last_read_at DESC",
    [$userId],
    "i"
);

// 2. Completed Books (>=100% progress)
$completedBooks = db_fetch_all(
    "SELECT rp.*, b.title, b.cover_image,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list
     FROM reading_progress rp
     JOIN books b ON rp.book_id = b.id
     WHERE rp.user_id = ? AND rp.progress_percentage >= 100
     ORDER BY rp.last_read_at DESC",
    [$userId],
    "i"
);

// 3. Currently Borrowed Physical Books
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

// 4. Wishlist Books
$wishlistBooks = db_fetch_all(
    "SELECT w.*, b.title, b.cover_image, b.is_digital, b.is_physical,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list,
            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') as available_copies
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
        <h3 class="fw-bold mb-1"><i class="fas fa-book-reader text-primary me-2"></i> My Personal Library</h3>
        <p class="text-muted text-sm mb-0">Track your in-progress books, physical loans, saved wishlist, and reading milestones</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-plus me-1"></i> Discover More Books
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 border shadow-sm">
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'reading' ? 'active' : '' ?>" href="<?= BASE_URL ?>/student/my-library.php?tab=reading">
          <i class="fas fa-book-open me-1"></i> Currently Reading (<?= count($readingBooks) ?>)
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'completed' ? 'active' : '' ?>" href="<?= BASE_URL ?>/student/my-library.php?tab=completed">
          <i class="fas fa-circle-check me-1"></i> Completed (<?= count($completedBooks) ?>)
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'borrowed' ? 'active' : '' ?>" href="<?= BASE_URL ?>/student/my-library.php?tab=borrowed">
          <i class="fas fa-hand-holding-box me-1"></i> Borrowed Copies (<?= count($borrowedBooks) ?>)
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'wishlist' ? 'active' : '' ?>" href="<?= BASE_URL ?>/student/my-library.php?tab=wishlist">
          <i class="fas fa-heart me-1"></i> Wishlist (<?= count($wishlistBooks) ?>)
        </a>
      </li>
    </ul>

    <!-- Tab 1: Currently Reading -->
    <?php if ($activeTab === 'reading'): ?>
      <?php if (empty($readingBooks)): ?>
        <div class="card p-5 text-center text-muted">
          <i class="fas fa-book-open-reader fs-1 mb-3 text-light"></i>
          <h5>No Books Currently In Progress</h5>
          <p class="text-sm">Explore our digital collection and begin reading instantly right in your browser!</p>
          <div>
            <a href="<?= BASE_URL ?>/student/catalogue.php?format=digital" class="btn btn-primary btn-sm btn-rounded">Browse Digital Books</a>
          </div>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($readingBooks as $b): ?>
            <div class="col-md-6 col-lg-4">
              <div class="card p-3 border shadow-sm h-100 d-flex flex-column">
                <div class="d-flex gap-3 mb-3">
                  <img src="<?= get_book_cover($b['cover_image'], $b['title']) ?>" alt="Cover" style="width: 70px; height: 100px; object-fit: cover; border-radius: 6px;">
                  <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">
                      <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['book_id'] ?>" class="text-dark text-decoration-none hover-primary">
                        <?= clean_output($b['title']) ?>
                      </a>
                    </h6>
                    <small class="text-muted d-block mb-2"><?= clean_output($b['authors_list'] ?: 'Unknown Author') ?></small>
                    <span class="badge bg-light text-primary border">Chapter <?= $b['current_chapter'] ?> &bull; Page <?= $b['current_page'] ?></span>
                  </div>
                </div>

                <div class="mt-auto">
                  <div class="d-flex justify-content-between text-xs text-muted mb-1">
                    <span>Progress</span>
                    <span class="fw-bold text-dark"><?= (int)$b['progress_percentage'] ?>%</span>
                  </div>
                  <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $b['progress_percentage'] ?>%;"></div>
                  </div>

                  <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $b['book_id'] ?>&chapter=<?= $b['current_chapter'] ?>" class="btn btn-primary btn-sm w-100 btn-rounded">
                      <i class="fas fa-glasses me-1"></i> Continue Reading
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <!-- Tab 2: Completed Books -->
    <?php elseif ($activeTab === 'completed'): ?>
      <?php if (empty($completedBooks)): ?>
        <div class="card p-5 text-center text-muted">
          <i class="fas fa-trophy fs-1 mb-3 text-light"></i>
          <h5>No Completed Books Yet</h5>
          <p class="text-sm">Keep reading! Finished books will automatically appear here in your achievements hall.</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($completedBooks as $b): ?>
            <div class="col-md-6 col-lg-4">
              <div class="card p-3 border shadow-sm">
                <div class="d-flex gap-3">
                  <img src="<?= get_book_cover($b['cover_image'], $b['title']) ?>" alt="Cover" style="width: 70px; height: 100px; object-fit: cover; border-radius: 6px;">
                  <div>
                    <span class="badge bg-success mb-1"><i class="fas fa-check-circle me-1"></i> Completed 100%</span>
                    <h6 class="fw-bold mb-1">
                      <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['book_id'] ?>" class="text-dark text-decoration-none hover-primary">
                        <?= clean_output($b['title']) ?>
                      </a>
                    </h6>
                    <small class="text-muted d-block"><?= clean_output($b['authors_list']) ?></small>
                    <small class="text-xs text-muted mt-2 d-block">Finished: <?= time_ago($b['last_read_at']) ?></small>
                  </div>
                </div>
                <div class="mt-3 pt-2 border-top d-flex gap-2">
                  <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $b['book_id'] ?>&chapter=1" class="btn btn-outline-secondary btn-sm w-100 btn-rounded">
                    <i class="fas fa-rotate-left me-1"></i> Re-read Book
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <!-- Tab 3: Borrowed Physical Books -->
    <?php elseif ($activeTab === 'borrowed'): ?>
      <?php if (empty($borrowedBooks)): ?>
        <div class="card p-5 text-center text-muted">
          <i class="fas fa-book-bookmark fs-1 mb-3 text-light"></i>
          <h5>No Active Physical Loans</h5>
          <p class="text-sm">You haven't borrowed any physical copies from the library stacks.</p>
        </div>
      <?php else: ?>
        <div class="card border shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Book</th>
                  <th>Barcode</th>
                  <th>Issue Date</th>
                  <th>Due Date</th>
                  <th>Shelf Location</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($borrowedBooks as $b): ?>
                  <?php
                    $due = new DateTime($b['due_date']);
                    $today = new DateTime(date('Y-m-d'));
                    $isOverdue = ($today > $due);
                  ?>
                  <tr>
                    <td>
                      <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['book_id'] ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                        <?= clean_output($b['title']) ?>
                      </a>
                    </td>
                    <td><code><?= clean_output($b['barcode']) ?></code></td>
                    <td><?= format_date($b['issue_date']) ?></td>
                    <td class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>"><?= format_date($b['due_date']) ?></td>
                    <td><?= clean_output($b['shelf_location']) ?></td>
                    <td>
                      <?php if ($isOverdue): ?>
                        <span class="badge badge-overdue">Overdue</span>
                      <?php else: ?>
                        <span class="badge badge-borrowed">Active Loan</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    <!-- Tab 4: Wishlist -->
    <?php elseif ($activeTab === 'wishlist'): ?>
      <?php if (empty($wishlistBooks)): ?>
        <div class="card p-5 text-center text-muted">
          <i class="far fa-heart fs-1 mb-3 text-light"></i>
          <h5>Your Wishlist is Empty</h5>
          <p class="text-sm">Save books you want to read later by clicking the heart button on any book card or details page.</p>
          <div>
            <a href="<?= BASE_URL ?>/student/catalogue.php" class="btn btn-primary btn-sm btn-rounded">Find Books</a>
          </div>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($wishlistBooks as $b): ?>
            <div class="col-md-6 col-lg-3">
              <div class="book-card shadow-sm h-100">
                <div class="book-cover-wrap">
                  <img src="<?= get_book_cover($b['cover_image'], $b['title']) ?>" alt="Cover" class="book-cover-img">
                </div>
                <div class="book-info">
                  <h6 class="book-title">
                    <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['book_id'] ?>" class="text-dark text-decoration-none hover-primary">
                      <?= clean_output($b['title']) ?>
                    </a>
                  </h6>
                  <div class="book-author"><?= clean_output($b['authors_list']) ?></div>
                  <div class="mt-auto d-flex gap-2">
                    <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $b['book_id'] ?>" class="btn btn-outline-primary btn-sm w-100 btn-rounded">View</a>
                    <?php if ($b['is_digital']): ?>
                      <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $b['book_id'] ?>" class="btn btn-primary btn-sm px-3 btn-rounded">Read</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
