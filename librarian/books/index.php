<?php
/**
 * Librarian: Books Catalogue Management
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role(['Librarian', 'Admin']);

$pageTitle = 'Manage Book Records - OpenShelf';

$search = trim($_GET['search'] ?? '');
$catFilter = (int)($_GET['category'] ?? 0);

$where = ["b.status = 'active'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "(b.title LIKE ? OR b.isbn LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if ($catFilter > 0) {
    $where[] = "EXISTS (SELECT 1 FROM book_categories bc WHERE bc.book_id = b.id AND bc.category_id = ?)";
    $params[] = $catFilter;
    $types .= "i";
}

$whereSql = implode(" AND ", $where);

$books = db_fetch_all(
    "SELECT b.*, p.name as publisher_name,
            (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') FROM book_authors ba JOIN authors a ON ba.author_id = a.id WHERE ba.book_id = b.id) as authors_list,
            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id) as total_copies,
            (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') as available_copies
     FROM books b
     LEFT JOIN publishers p ON b.publisher_id = p.id
     WHERE {$whereSql}
     ORDER BY b.created_at DESC",
    $params,
    $types
);

$allCategories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-book text-primary me-2"></i> Catalogue Master Records</h3>
        <p class="text-muted text-sm mb-0">Maintain bibliographic entries, ISBN indexing, authors, and format availability</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/librarian/books/add.php" class="btn btn-primary btn-rounded btn-sm fw-semibold">
          <i class="fas fa-plus me-1"></i> Add New Book
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Filter Bar -->
    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <form action="<?= BASE_URL ?>/librarian/books/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control" name="search" value="<?= clean_output($search) ?>" placeholder="Search by title or ISBN...">
          </div>
        </div>
        <div class="col-md-4">
          <select class="form-select form-select-sm" name="category">
            <option value="0">All Categories</option>
            <?php foreach ($allCategories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $catFilter === (int)$cat['id'] ? 'selected' : '' ?>>
                <?= clean_output($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
          <?php if (!empty($search) || $catFilter > 0): ?>
            <a href="<?= BASE_URL ?>/librarian/books/index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Books Master Table -->
    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <?php if (empty($books)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-book fs-1 text-light mb-2"></i>
            <p class="mb-0">No book records found matching your filters.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-sm">
              <thead>
                <tr>
                  <th>Title & Authors</th>
                  <th>ISBN</th>
                  <th>Publisher</th>
                  <th>Formats</th>
                  <th>Copies Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($books as $b): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img src="<?= get_book_cover($b['cover_image'], $b['title']) ?>" alt="Cover" style="width: 38px; height: 50px; object-fit: cover; border-radius: 4px;">
                        <div>
                          <a href="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $b['id'] ?>" class="text-dark fw-bold text-decoration-none hover-primary">
                            <?= clean_output($b['title']) ?>
                          </a>
                          <small class="text-muted d-block"><?= clean_output($b['authors_list'] ?: 'Unknown Author') ?></small>
                        </div>
                      </div>
                    </td>
                    <td><code><?= clean_output($b['isbn']) ?></code></td>
                    <td><?= clean_output($b['publisher_name'] ?: '—') ?></td>
                    <td>
                      <?php if ($b['is_digital']): ?>
                        <span class="badge bg-primary text-xs"><i class="fas fa-tablet-screen-button me-1"></i> Digital</span>
                      <?php endif; ?>
                      <?php if ($b['is_physical']): ?>
                        <span class="badge bg-secondary text-xs"><i class="fas fa-book me-1"></i> Physical</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($b['is_physical']): ?>
                        <span class="badge <?= $b['available_copies'] > 0 ? 'badge-available' : 'badge-overdue' ?>">
                          <?= $b['available_copies'] ?> / <?= $b['total_copies'] ?> Avail
                        </span>
                      <?php else: ?>
                        <span class="text-muted text-xs">E-Book Only</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <div class="btn-group">
                        <a href="<?= BASE_URL ?>/librarian/books/details.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-info table-action-btn" title="View Logical Details & Copies">
                          <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/librarian/copies/add.php?book_id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-success table-action-btn" title="Add Physical Copies">
                          <i class="fas fa-barcode"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/librarian/books/edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary table-action-btn" title="Edit Metadata">
                          <i class="fas fa-pen"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/librarian/books/delete.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger table-action-btn" title="Archive Book" onclick="return confirm('Archive this book title?');">
                          <i class="fas fa-trash-can"></i>
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

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
