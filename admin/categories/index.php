<?php
/**
 * Admin: Categories Management
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$pageTitle = 'Manage Categories - OpenShelf';

// Handle Add Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fas fa-book');

    if (!empty($name)) {
        db_execute("INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)", [$name, $desc, $icon], "sss");
        log_audit('CATEGORY_ADDED', "Added category: {$name}");
        set_flash('success', "Category '{$name}' created!");
        header("Location: " . BASE_URL . "/admin/categories/index.php");
        exit;
    }
}

// Handle Delete Action
if (isset($_GET['delete'])) {
    $catId = (int)$_GET['delete'];
    db_execute("DELETE FROM categories WHERE id = ?", [$catId], "i");
    log_audit('CATEGORY_DELETED', "Deleted category #{$catId}");
    set_flash('info', 'Category removed.');
    header("Location: " . BASE_URL . "/admin/categories/index.php");
    exit;
}

$categories = db_fetch_all(
    "SELECT c.*, (SELECT COUNT(*) FROM book_categories bc WHERE bc.category_id = c.id) as book_count
     FROM categories c
     ORDER BY c.name ASC"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-tags text-primary me-2"></i> Book Categories & Genres</h3>
        <p class="text-muted text-sm mb-0">Organize academic and literary disciplines into searchable genres</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="row g-4">
      <!-- Add Category Form Left -->
      <div class="col-lg-4">
        <div class="card p-4 border shadow-sm">
          <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i> Add New Category</h5>
          <form action="<?= BASE_URL ?>/admin/categories/index.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="mb-3">
              <label for="name" class="form-label text-sm fw-semibold">Category Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Artificial Intelligence & Robotics" required>
            </div>

            <div class="mb-3">
              <label for="icon" class="form-label text-sm fw-semibold">FontAwesome Icon Class</label>
              <input type="text" class="form-control" id="icon" name="icon" value="fas fa-book" placeholder="e.g. fas fa-brain, fas fa-flask">
            </div>

            <div class="mb-4">
              <label for="description" class="form-label text-sm fw-semibold">Description</label>
              <textarea class="form-control" id="description" name="description" rows="3" placeholder="Overview of books in this category"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-rounded w-100 fw-bold">
              <i class="fas fa-check me-1"></i> Create Category
            </button>
          </form>
        </div>
      </div>

      <!-- Categories List Right -->
      <div class="col-lg-8">
        <div class="card border shadow-sm">
          <div class="card-header bg-white fw-bold">
            All Categories (<?= count($categories) ?>)
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-sm">
                <thead>
                  <tr>
                    <th>Category</th>
                    <th>Icon</th>
                    <th>Titles Count</th>
                    <th>Description</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($categories as $cat): ?>
                    <tr>
                      <td class="fw-bold text-dark"><?= clean_output($cat['name']) ?></td>
                      <td><i class="<?= clean_output($cat['icon']) ?> fs-5 text-primary"></i></td>
                      <td>
                        <span class="badge bg-light text-primary border"><?= $cat['book_count'] ?> books</span>
                      </td>
                      <td class="text-xs text-muted"><?= clean_output($cat['description'] ?: '—') ?></td>
                      <td class="text-end">
                        <a href="<?= BASE_URL ?>/admin/categories/index.php?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Category" onclick="return confirm('Delete this category?');">
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
