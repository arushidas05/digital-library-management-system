<?php
/**
 * Librarian: Edit Book Record
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$bookId = (int)($_GET['id'] ?? 0);
if ($bookId <= 0) {
    header("Location: " . BASE_URL . "/librarian/books/index.php");
    exit;
}

$book = db_fetch_one("SELECT * FROM books WHERE id = ?", [$bookId], "i");
if (!$book) {
    set_flash('danger', 'Book record not found.');
    header("Location: " . BASE_URL . "/librarian/books/index.php");
    exit;
}

$pageTitle = 'Edit Book: ' . clean_output($book['title']) . ' - OpenShelf';

$publishers = db_fetch_all("SELECT * FROM publishers ORDER BY name ASC");
$authors = db_fetch_all("SELECT * FROM authors ORDER BY name ASC");
$categories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");

// Fetch currently mapped authors and categories
$currentAuthors = array_column(db_fetch_all("SELECT author_id FROM book_authors WHERE book_id = ?", [$bookId], "i"), 'author_id');
$currentCategories = array_column(db_fetch_all("SELECT category_id FROM book_categories WHERE book_id = ?", [$bookId], "i"), 'category_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publisherId = (int)($_POST['publisher_id'] ?? 0) ?: null;
    $pubYear = (int)($_POST['publication_year'] ?? date('Y'));
    $edition = trim($_POST['edition'] ?? '');
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
        // Update book record
        db_execute(
            "UPDATE books SET title = ?, isbn = ?, publisher_id = ?, publication_year = ?, edition = ?, language = ?, total_pages = ?, description = ?, is_physical = ?, is_digital = ? WHERE id = ?",
            [$title, $isbn, $publisherId, $pubYear, $edition, $language, $totalPages, $description, $isPhysical, $isDigital, $bookId],
            "ssiissisiii"
        );

        // Update authors mapping
        db_execute("DELETE FROM book_authors WHERE book_id = ?", [$bookId], "i");
        foreach ($selectedAuthors as $aid) {
            $aid = (int)$aid;
            if ($aid > 0) {
                db_execute("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)", [$bookId, $aid], "ii");
            }
        }

        // Update categories mapping
        db_execute("DELETE FROM book_categories WHERE book_id = ?", [$bookId], "i");
        foreach ($selectedCategories as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) {
                db_execute("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)", [$bookId, $cid], "ii");
            }
        }

        log_audit('BOOK_UPDATED', "Updated book #{$bookId}: {$title}");
        set_flash('success', 'Book metadata updated successfully!');
        header("Location: " . BASE_URL . "/librarian/books/details.php?id=" . $bookId);
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-pen text-primary me-2"></i> Edit Book Record</h3>
        <p class="text-muted text-sm mb-0">Modify bibliographic details, authors, categories, and circulation settings</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $book['id'] ?>" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Cancel & View Book
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <form action="<?= BASE_URL ?>/librarian/books/edit.php?id=<?= $book['id'] ?>" method="POST">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card p-4 border shadow-sm mb-4">
            <h5 class="fw-bold mb-3 text-dark">Bibliographic Information</h5>

            <div class="mb-3">
              <label for="title" class="form-label text-sm fw-semibold">Book Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="title" name="title" value="<?= clean_output($book['title']) ?>" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="isbn" class="form-label text-sm fw-semibold">ISBN-13 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="isbn" name="isbn" value="<?= clean_output($book['isbn']) ?>" required>
              </div>
              <div class="col-md-6">
                <label for="publisher_id" class="form-label text-sm fw-semibold">Publisher</label>
                <select class="form-select" id="publisher_id" name="publisher_id">
                  <option value="">Select Publisher...</option>
                  <?php foreach ($publishers as $pub): ?>
                    <option value="<?= $pub['id'] ?>" <?= (int)$book['publisher_id'] === (int)$pub['id'] ? 'selected' : '' ?>>
                      <?= clean_output($pub['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label for="publication_year" class="form-label text-sm fw-semibold">Publication Year</label>
                <input type="number" class="form-control" id="publication_year" name="publication_year" value="<?= clean_output($book['publication_year']) ?>">
              </div>
              <div class="col-md-4">
                <label for="edition" class="form-label text-sm fw-semibold">Edition</label>
                <input type="text" class="form-control" id="edition" name="edition" value="<?= clean_output($book['edition']) ?>">
              </div>
              <div class="col-md-4">
                <label for="total_pages" class="form-label text-sm fw-semibold">Total Pages</label>
                <input type="number" class="form-control" id="total_pages" name="total_pages" value="<?= clean_output($book['total_pages']) ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label text-sm fw-semibold">Synopsis / Book Description</label>
              <textarea class="form-control" id="description" name="description" rows="5"><?= clean_output($book['description']) ?></textarea>
            </div>

            <div class="d-flex gap-4 p-3 bg-light rounded-3 border">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_physical" name="is_physical" value="1" <?= $book['is_physical'] ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold text-sm" for="is_physical">Physical Circulation Enabled</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_digital" name="is_digital" value="1" <?= $book['is_digital'] ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold text-sm" for="is_digital">Digital Online Reader Enabled</label>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <!-- Authors -->
          <div class="card p-3 border shadow-sm mb-4">
            <h6 class="fw-bold mb-2 text-dark"><i class="fas fa-feather-pointed text-primary me-1"></i> Authors</h6>
            <div style="max-height: 220px; overflow-y: auto;" class="p-2 border rounded bg-light">
              <?php foreach ($authors as $a): ?>
                <div class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="authors[]" value="<?= $a['id'] ?>" id="ea_<?= $a['id'] ?>" <?= in_array($a['id'], $currentAuthors) ? 'checked' : '' ?>>
                  <label class="form-check-label text-sm" for="ea_<?= $a['id'] ?>">
                    <?= clean_output($a['name']) ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Categories -->
          <div class="card p-3 border shadow-sm mb-4">
            <h6 class="fw-bold mb-2 text-dark"><i class="fas fa-tags text-primary me-1"></i> Categories</h6>
            <div style="max-height: 220px; overflow-y: auto;" class="p-2 border rounded bg-light">
              <?php foreach ($categories as $cat): ?>
                <div class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" id="ec_<?= $cat['id'] ?>" <?= in_array($cat['id'], $currentCategories) ? 'checked' : '' ?>>
                  <label class="form-check-label text-sm" for="ec_<?= $cat['id'] ?>">
                    <?= clean_output($cat['name']) ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-3 btn-rounded fw-bold shadow">
            <i class="fas fa-save me-1"></i> Update Book Record
          </button>
        </div>
      </div>
    </form>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
