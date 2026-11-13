<?php
/**
 * Admin: Authors Management
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$pageTitle = 'Manage Authors - OpenShelf';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['biography'] ?? '');

    if (!empty($name)) {
        db_execute("INSERT INTO authors (name, biography, photo) VALUES (?, ?, 'default-author.png')", [$name, $bio], "ss");
        log_audit('AUTHOR_ADDED', "Added author: {$name}");
        set_flash('success', "Author '{$name}' added!");
        header("Location: " . BASE_URL . "/admin/authors/index.php");
        exit;
    }
}

if (isset($_GET['delete'])) {
    $authorId = (int)$_GET['delete'];
    db_execute("DELETE FROM authors WHERE id = ?", [$authorId], "i");
    log_audit('AUTHOR_DELETED', "Deleted author #{$authorId}");
    set_flash('info', 'Author removed.');
    header("Location: " . BASE_URL . "/admin/authors/index.php");
    exit;
}

$authors = db_fetch_all(
    "SELECT a.*, (SELECT COUNT(*) FROM book_authors ba WHERE ba.author_id = a.id) as book_count
     FROM authors a
     ORDER BY a.name ASC"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-feather-pointed text-primary me-2"></i> Authors Management</h3>
        <p class="text-muted text-sm mb-0">Maintain literary author records, biographies, and bibliographic contributions</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="row g-4">
      <!-- Add Author Form Left -->
      <div class="col-lg-4">
        <div class="card p-4 border shadow-sm">
          <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i> Add New Author</h5>
          <form action="<?= BASE_URL ?>/admin/authors/index.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="mb-3">
              <label for="name" class="form-label text-sm fw-semibold">Author Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Donald Knuth" required>
            </div>

            <div class="mb-4">
              <label for="biography" class="form-label text-sm fw-semibold">Biography</label>
              <textarea class="form-control" id="biography" name="biography" rows="4" placeholder="Author background, awards, and historical contributions..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-rounded w-100 fw-bold">
              <i class="fas fa-check me-1"></i> Save Author
            </button>
          </form>
        </div>
      </div>

      <!-- Authors Table Right -->
      <div class="col-lg-8">
        <div class="card border shadow-sm">
          <div class="card-header bg-white fw-bold">
            All Authors (<?= count($authors) ?>)
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-sm">
                <thead>
                  <tr>
                    <th>Author Name</th>
                    <th>Catalogue Titles</th>
                    <th>Biography Snippet</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($authors as $a): ?>
                    <tr>
                      <td class="fw-bold text-dark"><?= clean_output($a['name']) ?></td>
                      <td>
                        <span class="badge bg-light text-primary border"><?= $a['book_count'] ?> books</span>
                      </td>
                      <td class="text-xs text-muted"><?= mb_strimwidth(clean_output($a['biography']), 0, 70, '...') ?></td>
                      <td class="text-end">
                        <a href="<?= BASE_URL ?>/admin/authors/index.php?delete=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Author" onclick="return confirm('Delete this author?');">
                          <i class="fas fa-trash-can"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
