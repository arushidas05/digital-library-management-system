<?php
/**
 * Librarian: Logical Book Details & Copies Inventory
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$bookId = (int)($_GET['id'] ?? 0);
if ($bookId <= 0) {
    header("Location: " . BASE_URL . "/librarian/books/index.php");
    exit;
}

// Fetch logical book
$book = db_fetch_one(
    "SELECT b.*, p.name as publisher_name,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list,
            (SELECT AVG(rating) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.book_id = b.id AND r.status = 'Approved') as total_reviews
     FROM books b
     LEFT JOIN publishers p ON b.publisher_id = p.id
     WHERE b.id = ?",
    [$bookId],
    "i"
);

if (!$book) {
    set_flash('danger', 'Book record not found.');
    header("Location: " . BASE_URL . "/librarian/books/index.php");
    exit;
}

$pageTitle = 'Book Details: ' . clean_output($book['title']) . ' - OpenShelf';

// Handle Add Chapter POST (if digital book)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_chapter') {
    $digitalBook = db_fetch_one("SELECT id FROM digital_books WHERE book_id = ?", [$bookId], "i");
    if (!$digitalBook) {
        db_execute("INSERT INTO digital_books (book_id, total_chapters) VALUES (?, 1)", [$bookId], "i");
        $digId = db_last_id();
    } else {
        $digId = $digitalBook['id'];
    }

    $chNum = (int)($_POST['chapter_number'] ?? 1);
    $chTitle = trim($_POST['chapter_title'] ?? '');
    $chContent = trim($_POST['chapter_content'] ?? '');

    if (!empty($chTitle) && !empty($chContent)) {
        db_execute(
            "INSERT INTO digital_book_chapters (digital_book_id, chapter_number, title, content) VALUES (?, ?, ?, ?)",
            [$digId, $chNum, $chTitle, $chContent],
            "iiss"
        );
        // Update total chapters count
        $cnt = db_count('digital_book_chapters', 'digital_book_id = ?', [$digId], "i");
        db_execute("UPDATE digital_books SET total_chapters = ? WHERE id = ?", [$cnt, $digId], "ii");

        log_audit('CHAPTER_ADDED', "Added chapter {$chNum} to book #{$bookId}");
        set_flash('success', "Chapter '{$chTitle}' added successfully!");
        header("Location: " . BASE_URL . "/librarian/books/details.php?id=" . $bookId);
        exit;
    }
}

// Fetch physical copies
$copies = db_fetch_all("SELECT * FROM book_copies WHERE book_id = ? ORDER BY copy_number ASC", [$bookId], "i");

// Fetch digital chapters
$digitalBook = db_fetch_one("SELECT * FROM digital_books WHERE book_id = ?", [$bookId], "i");
$chapters = [];
if ($digitalBook) {
    $chapters = db_fetch_all("SELECT * FROM digital_book_chapters WHERE digital_book_id = ? ORDER BY chapter_number ASC", [$digitalBook['id']], "i");
}

// Fetch circulation history
$history = db_fetch_all(
    "SELECT br.*, bc.copy_number, bc.barcode, u.username, p.full_name
     FROM borrowings br
     JOIN book_copies bc ON br.book_copy_id = bc.id
     JOIN users u ON br.user_id = u.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE bc.book_id = ?
     ORDER BY br.created_at DESC LIMIT 10",
    [$bookId],
    "i"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-book-open text-primary me-2"></i> Book Record: <?= clean_output($book['title']) ?></h3>
        <p class="text-muted text-sm mb-0">Catalogue details, physical copies inventory, circulation history, and digital editions</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/librarian/books/edit.php?id=<?= $book['id'] ?>" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-pen me-1"></i> Edit Metadata
        </a>
        <a href="<?= BASE_URL ?>/librarian/copies/add.php?book_id=<?= $book['id'] ?>" class="btn btn-success btn-sm btn-rounded">
          <i class="fas fa-barcode me-1"></i> Add Physical Copy
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Logical Metadata Card -->
    <div class="card p-4 border shadow-sm mb-4">
      <div class="row g-4">
        <div class="col-md-2 text-center">
          <img src="<?= get_book_cover($book['cover_image'], $book['title']) ?>" alt="Cover" class="img-fluid rounded shadow-sm" style="max-height: 180px;">
        </div>
        <div class="col-md-10">
          <h4 class="fw-bold mb-1"><?= clean_output($book['title']) ?></h4>
          <p class="text-muted text-sm mb-2">By <strong><?= clean_output($book['authors_list'] ?: 'Unknown') ?></strong></p>

          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge bg-light text-dark border"><i class="fas fa-barcode me-1"></i> ISBN: <?= clean_output($book['isbn']) ?></span>
            <span class="badge bg-light text-dark border"><i class="fas fa-building me-1"></i> Publisher: <?= clean_output($book['publisher_name'] ?: 'None') ?></span>
            <span class="badge bg-light text-dark border"><i class="fas fa-calendar me-1"></i> Year: <?= clean_output($book['publication_year']) ?></span>
            <span class="badge bg-light text-dark border"><i class="fas fa-file-lines me-1"></i> Pages: <?= clean_output($book['total_pages']) ?></span>
            <?php if ($book['is_digital']): ?>
              <span class="badge bg-primary"><i class="fas fa-tablet-screen-button me-1"></i> Digital Enabled</span>
            <?php endif; ?>
            <?php if ($book['is_physical']): ?>
              <span class="badge bg-secondary"><i class="fas fa-book me-1"></i> Physical Enabled</span>
            <?php endif; ?>
          </div>

          <p class="text-muted text-sm mb-0"><?= nl2br(clean_output($book['description'])) ?></p>
        </div>
      </div>
    </div>

    <!-- Physical Copies Inventory Table (FR-04, M03) -->
    <div class="card shadow-sm border mb-4">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-barcode text-primary me-2"></i> Physical Copies Inventory (<?= count($copies) ?> Copies)</span>
        <a href="<?= BASE_URL ?>/librarian/copies/add.php?book_id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-success btn-rounded">
          <i class="fas fa-plus me-1"></i> New Copy
        </a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($copies)): ?>
          <div class="text-center py-4 text-muted text-sm">
            No physical copies currently registered for this book.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Copy #</th>
                  <th>Barcode</th>
                  <th>Shelf / Location</th>
                  <th>Status</th>
                  <th>Condition Notes</th>
                  <th class="text-end">Manage</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($copies as $cp): ?>
                  <tr>
                    <td class="fw-bold"><?= clean_output($cp['copy_number']) ?></td>
                    <td><code><?= clean_output($cp['barcode']) ?></code></td>
                    <td><i class="fas fa-location-dot text-danger me-1"></i> <?= clean_output($cp['shelf_location']) ?></td>
                    <td>
                      <span class="badge copy-badge-pill status-<?= strtolower($cp['status']) ?>">
                        <?= clean_output($cp['status']) ?>
                      </span>
                    </td>
                    <td class="text-muted text-xs"><?= clean_output($cp['condition_notes'] ?: 'Good') ?></td>
                    <td class="text-end">
                      <div class="btn-group">
                        <a href="<?= BASE_URL ?>/librarian/copies/edit.php?id=<?= $cp['id'] ?>" class="btn btn-sm btn-outline-primary table-action-btn" title="Edit Copy Details">
                          <i class="fas fa-pen"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/librarian/copies/status.php?id=<?= $cp['id'] ?>" class="btn btn-sm btn-outline-warning table-action-btn" title="Update Status">
                          <i class="fas fa-rotate"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Digital Book Chapters (if digital) -->
    <?php if ($book['is_digital']): ?>
      <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <span class="fw-bold"><i class="fas fa-glasses text-info me-2"></i> Digital Book Chapters (<?= count($chapters) ?>)</span>
          <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>" target="_blank" class="btn btn-sm btn-primary btn-rounded">
            <i class="fas fa-play me-1"></i> Preview in Online Reader
          </a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($chapters)): ?>
            <div class="text-center py-4 text-muted text-sm">
              No digital chapters added yet. Use form below to add Chapter 1.
            </div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($chapters as $ch): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <span class="fw-bold text-sm">Chapter <?= $ch['chapter_number'] ?>: <?= clean_output($ch['title']) ?></span>
                    <small class="text-muted d-block text-xs"><?= mb_strimwidth(strip_tags($ch['content']), 0, 100, '...') ?></small>
                  </div>
                  <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=<?= $ch['chapter_number'] ?>" class="btn btn-sm btn-outline-secondary btn-rounded">
                    Read
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Inline Add Chapter Form -->
          <div class="p-3 bg-light border-top">
            <h6 class="fw-bold text-sm mb-2"><i class="fas fa-plus-circle me-1 text-primary"></i> Add Next Chapter</h6>
            <form action="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $book['id'] ?>" method="POST">
              <input type="hidden" name="action" value="add_chapter">
              <div class="row g-2 mb-2">
                <div class="col-md-3">
                  <input type="number" class="form-control form-control-sm" name="chapter_number" value="<?= count($chapters) + 1 ?>" required placeholder="Chapter Number">
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control form-control-sm" name="chapter_title" placeholder="Chapter Title (e.g. Chapter <?= count($chapters) + 1 ?>: The Encounter)" required>
                </div>
              </div>
              <div class="mb-2">
                <textarea class="form-control form-control-sm" name="chapter_content" rows="3" placeholder="Enter chapter text paragraphs here..." required></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm btn-rounded px-3">
                <i class="fas fa-upload me-1"></i> Save Chapter
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Past Circulation Transactions for this Title -->
    <div class="card shadow-sm border">
      <div class="card-header bg-white fw-bold">
        <i class="fas fa-history text-secondary me-2"></i> Circulation History for this Book
      </div>
      <div class="card-body p-0">
        <?php if (empty($history)): ?>
          <div class="text-center py-4 text-muted text-sm">
            No circulation transactions recorded yet for this book.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Borrower</th>
                  <th>Barcode</th>
                  <th>Issue Date</th>
                  <th>Due Date</th>
                  <th>Return Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($history as $h): ?>
                  <tr>
                    <td class="fw-semibold"><?= clean_output($h['full_name'] ?: $h['username']) ?></td>
                    <td><code><?= clean_output($h['barcode']) ?></code></td>
                    <td><?= format_date($h['issue_date']) ?></td>
                    <td><?= format_date($h['due_date']) ?></td>
                    <td><?= format_date($h['return_date']) ?></td>
                    <td>
                      <span class="badge copy-badge-pill status-<?= strtolower($h['status']) ?>">
                        <?= clean_output($h['status']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
