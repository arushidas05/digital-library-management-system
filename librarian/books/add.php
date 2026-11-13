<?php
/**
 * Librarian: Add New Book
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Add New Book - OpenShelf';

// Fetch Authors, Publishers, Categories for selectors
$publishers = db_fetch_all("SELECT * FROM publishers ORDER BY name ASC");
$authors = db_fetch_all("SELECT * FROM authors ORDER BY name ASC");
$categories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publisherId = (int)($_POST['publisher_id'] ?? 0) ?: null;
    $pubYear = (int)($_POST['publication_year'] ?? date('Y'));
    $edition = trim($_POST['edition'] ?? '1st Edition');
    $language = trim($_POST['language'] ?? 'English');
    $totalPages = (int)($_POST['total_pages'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $isPhysical = isset($_POST['is_physical']) ? 1 : 0;
    $isDigital = isset($_POST['is_digital']) ? 1 : 0;

    $selectedAuthors = $_POST['authors'] ?? [];
    $selectedCategories = $_POST['categories'] ?? [];

    if (empty($title) || empty($isbn)) {
        set_flash('danger', 'Title and ISBN are required.');
    } else {
        // Check ISBN unique
        $existing = db_fetch_one("SELECT id FROM books WHERE isbn = ?", [$isbn], "s");
        if ($existing) {
            set_flash('danger', 'A book with this ISBN already exists.');
        } else {
            // Insert logical book
            $insert = db_execute(
                "INSERT INTO books (title, isbn, publisher_id, publication_year, edition, language, total_pages, description, is_physical, is_digital, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                [$title, $isbn, $publisherId, $pubYear, $edition, $language, $totalPages, $description, $isPhysical, $isDigital],
                "ssiissisii"
            );

            if ($insert) {
                $bookId = db_last_id();

                // Map authors
                foreach ($selectedAuthors as $aid) {
                    $aid = (int)$aid;
                    if ($aid > 0) {
                        db_execute("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)", [$bookId, $aid], "ii");
                    }
                }

                // Map categories
                foreach ($selectedCategories as $cid) {
                    $cid = (int)$cid;
                    if ($cid > 0) {
                        db_execute("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)", [$bookId, $cid], "ii");
                    }
                }

                // If physical and initial copy details provided
                if ($isPhysical && !empty($_POST['initial_barcode'])) {
                    $copyNumber = trim($_POST['initial_copy_number'] ?? 'CP-' . $bookId . '-01');
                    $barcode = trim($_POST['initial_barcode']);
                    $shelfLocation = trim($_POST['initial_shelf'] ?? 'Floor 1, Shelf A-01');

                    db_execute(
                        "INSERT INTO book_copies (book_id, copy_number, barcode, shelf_location, status) VALUES (?, ?, ?, ?, 'Available')",
                        [$bookId, $copyNumber, $barcode, $shelfLocation],
                        "isss"
                    );
                }

                // If digital enabled, create digital book row and sample chapter
                if ($isDigital) {
                    db_execute(
                        "INSERT INTO digital_books (book_id, total_chapters, sample_content) VALUES (?, 1, ?)",
                        [$bookId, "Digital edition initialized."],
                        "is"
                    );
                    $digitalBookId = db_last_id();

                    $chapterTitle = trim($_POST['initial_chapter_title'] ?? 'Chapter 1: Introduction');
                    $chapterContent = trim($_POST['initial_chapter_content'] ?? 'Welcome to the digital edition of ' . $title . '. Content will be populated shortly.');
                    db_execute(
                        "INSERT INTO digital_book_chapters (digital_book_id, chapter_number, title, content) VALUES (?, 1, ?, ?)",
                        [$digitalBookId, $chapterTitle, $chapterContent],
                        "iss"
                    );
                }

                log_audit('BOOK_ADDED', "Added book: {$title} (ISBN: {$isbn})");
                set_flash('success', "Book '{$title}' has been successfully added to the catalogue!");
                header("Location: " . BASE_URL . "/librarian/books/details.php?id=" . $bookId);
                exit;
            } else {
                set_flash('danger', 'Failed to insert book record.');
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-folder-plus text-primary me-2"></i> Add Book to Catalogue</h3>
        <p class="text-muted text-sm mb-0">Record bibliographic data, select authors, assign categories, and generate physical copies</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/books/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Books
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <form action="<?= BASE_URL ?>/librarian/books/add.php" method="POST">
      <div class="row g-4">
        <!-- Main Bibliographic Info Left -->
        <div class="col-lg-8">
          <div class="card p-4 border shadow-sm mb-4">
            <h5 class="fw-bold mb-3 text-dark">Bibliographic Information</h5>

            <div class="mb-3">
              <label for="title" class="form-label text-sm fw-semibold">Book Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Design Patterns: Elements of Reusable Object-Oriented Software" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="isbn" class="form-label text-sm fw-semibold">ISBN / ISBN-13 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="isbn" name="isbn" placeholder="e.g. 978-0201633610" required>
              </div>
              <div class="col-md-6">
                <label for="publisher_id" class="form-label text-sm fw-semibold">Publisher</label>
                <select class="form-select" id="publisher_id" name="publisher_id">
                  <option value="">Select Publisher...</option>
                  <?php foreach ($publishers as $pub): ?>
                    <option value="<?= $pub['id'] ?>"><?= clean_output($pub['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label for="publication_year" class="form-label text-sm fw-semibold">Publication Year</label>
                <input type="number" class="form-control" id="publication_year" name="publication_year" value="<?= date('Y') ?>">
              </div>
              <div class="col-md-4">
                <label for="edition" class="form-label text-sm fw-semibold">Edition</label>
                <input type="text" class="form-control" id="edition" name="edition" placeholder="e.g. 2nd Edition">
              </div>
              <div class="col-md-4">
                <label for="total_pages" class="form-label text-sm fw-semibold">Total Pages</label>
                <input type="number" class="form-control" id="total_pages" name="total_pages" placeholder="e.g. 380">
              </div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label text-sm fw-semibold">Synopsis / Book Description</label>
              <textarea class="form-control" id="description" name="description" rows="4" placeholder="Brief summary, key learning objectives, or back-cover overview..."></textarea>
            </div>
          </div>

          <!-- Physical Copy Section -->
          <div class="card p-4 border shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold mb-0 text-dark">Initial Physical Copy</h5>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_physical" name="is_physical" value="1" checked onchange="document.getElementById('physicalSection').style.display = this.checked ? 'block' : 'none';">
                <label class="form-check-label fw-semibold text-sm" for="is_physical">Physical Copies Available</label>
              </div>
            </div>

            <div id="physicalSection">
              <p class="text-muted text-xs mb-3">Create an initial physical copy immediately on addition. Additional copies can be added later.</p>
              <div class="row g-3">
                <div class="col-md-4">
                  <label for="initial_copy_number" class="form-label text-xs fw-bold text-muted">Copy Number</label>
                  <input type="text" class="form-control form-control-sm" id="initial_copy_number" name="initial_copy_number" placeholder="e.g. CP-101">
                </div>
                <div class="col-md-4">
                  <label for="barcodeInput" class="form-label text-xs fw-bold text-muted">Barcode</label>
                  <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="barcodeInput" name="initial_barcode" placeholder="e.g. BAR-889922">
                    <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()">Auto</button>
                  </div>
                </div>
                <div class="col-md-4">
                  <label for="initial_shelf" class="form-label text-xs fw-bold text-muted">Shelf / Rack Location</label>
                  <input type="text" class="form-control form-control-sm" id="initial_shelf" name="initial_shelf" placeholder="e.g. Floor 2, Shelf CS-03">
                </div>
              </div>
            </div>
          </div>

          <!-- Digital Reading Section -->
          <div class="card p-4 border shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold mb-0 text-dark">Digital E-Book Edition</h5>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_digital" name="is_digital" value="1" checked onchange="document.getElementById('digitalSection').style.display = this.checked ? 'block' : 'none';">
                <label class="form-check-label fw-semibold text-sm" for="is_digital">Enable Online Reader</label>
              </div>
            </div>

            <div id="digitalSection">
              <p class="text-muted text-xs mb-3">Enter sample introductory content for Chapter 1. Additional chapters can be added in the book management view.</p>
              <div class="mb-3">
                <label for="initial_chapter_title" class="form-label text-xs fw-bold text-muted">Chapter 1 Title</label>
                <input type="text" class="form-control form-control-sm" id="initial_chapter_title" name="initial_chapter_title" value="Chapter 1: Introduction">
              </div>
              <div class="mb-3">
                <label for="initial_chapter_content" class="form-label text-xs fw-bold text-muted">Chapter 1 Content</label>
                <textarea class="form-control form-control-sm" id="initial_chapter_content" name="initial_chapter_content" rows="4" placeholder="Paste chapter content here..."></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Authors & Categories Sidebar Right -->
        <div class="col-lg-4">
          <!-- Authors Selection -->
          <div class="card p-3 border shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-feather-pointed text-primary me-1"></i> Authors</h6>
              <a href="<?= BASE_URL ?>/admin/authors/add.php" class="text-xs text-primary text-decoration-none">+ New Author</a>
            </div>
            <div style="max-height: 220px; overflow-y: auto;" class="p-2 border rounded bg-light">
              <?php foreach ($authors as $a): ?>
                <div class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="authors[]" value="<?= $a['id'] ?>" id="auth_<?= $a['id'] ?>">
                  <label class="form-check-label text-sm" for="auth_<?= $a['id'] ?>">
                    <?= clean_output($a['name']) ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Categories Selection -->
          <div class="card p-3 border shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-tags text-primary me-1"></i> Categories</h6>
              <a href="<?= BASE_URL ?>/admin/categories/add.php" class="text-xs text-primary text-decoration-none">+ New Category</a>
            </div>
            <div style="max-height: 220px; overflow-y: auto;" class="p-2 border rounded bg-light">
              <?php foreach ($categories as $cat): ?>
                <div class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" id="cat_<?= $cat['id'] ?>">
                  <label class="form-check-label text-sm" for="cat_<?= $cat['id'] ?>">
                    <i class="<?= clean_output($cat['icon'] ?: 'fas fa-book') ?> text-muted me-1"></i> <?= clean_output($cat['name']) ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn btn-primary w-100 py-3 btn-rounded fw-bold shadow">
            <i class="fas fa-check-circle me-1"></i> Save & Add to Catalogue
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
