<?php
/**
 * Admin: Publishers Management
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$pageTitle = 'Manage Publishers - OpenShelf';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');

    if (!empty($name)) {
        db_execute(
            "INSERT INTO publishers (name, address, phone, email, website) VALUES (?, ?, ?, ?, ?)",
            [$name, $address, $phone, $email, $website],
            "sssss"
        );
        log_audit('PUBLISHER_ADDED', "Added publisher: {$name}");
        set_flash('success', "Publisher '{$name}' created!");
        header("Location: " . BASE_URL . "/admin/publishers/index.php");
        exit;
    }
}

if (isset($_GET['delete'])) {
    $pubId = (int)$_GET['delete'];
    db_execute("DELETE FROM publishers WHERE id = ?", [$pubId], "i");
    log_audit('PUBLISHER_DELETED', "Deleted publisher #{$pubId}");
    set_flash('info', 'Publisher record removed.');
    header("Location: " . BASE_URL . "/admin/publishers/index.php");
    exit;
}

$publishers = db_fetch_all(
    "SELECT p.*, (SELECT COUNT(*) FROM books b WHERE b.publisher_id = p.id) as book_count
     FROM publishers p
     ORDER BY p.name ASC"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-building text-primary me-2"></i> Publishers Directory</h3>
        <p class="text-muted text-sm mb-0">Manage publishing houses, contact information, and imprint details</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="row g-4">
      <!-- Add Publisher Form Left -->
      <div class="col-lg-4">
        <div class="card p-4 border shadow-sm">
          <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i> Add Publisher</h5>
          <form action="<?= BASE_URL ?>/admin/publishers/index.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="mb-3">
              <label for="name" class="form-label text-sm fw-semibold">Publisher Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name" placeholder="e.g. MIT Press" required>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label text-sm fw-semibold">Contact Email</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="contact@mitpress.edu">
            </div>

            <div class="mb-3">
              <label for="phone" class="form-label text-sm fw-semibold">Phone</label>
              <input type="text" class="form-control" id="phone" name="phone" placeholder="+1 617-253-5646">
            </div>

            <div class="mb-3">
              <label for="website" class="form-label text-sm fw-semibold">Website URL</label>
              <input type="url" class="form-control" id="website" name="website" placeholder="https://mitpress.mit.edu">
            </div>

            <div class="mb-4">
              <label for="address" class="form-label text-sm fw-semibold">Headquarters Address</label>
              <textarea class="form-control" id="address" name="address" rows="2" placeholder="Cambridge, Massachusetts"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-rounded w-100 fw-bold">
              <i class="fas fa-check me-1"></i> Save Publisher
            </button>
          </form>
        </div>
      </div>

      <!-- Publishers Table Right -->
      <div class="col-lg-8">
        <div class="card border shadow-sm">
          <div class="card-header bg-white fw-bold">
            All Publishers (<?= count($publishers) ?>)
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-sm">
                <thead>
                  <tr>
                    <th>Publisher Name</th>
                    <th>Catalogue Books</th>
                    <th>Contact Info</th>
                    <th>Website</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($publishers as $p): ?>
                    <tr>
                      <td class="fw-bold text-dark"><?= clean_output($p['name']) ?></td>
                      <td>
                        <span class="badge bg-light text-primary border"><?= $p['book_count'] ?> books</span>
                      </td>
                      <td class="text-xs text-muted">
                        <div><?= clean_output($p['email'] ?: '—') ?></div>
                        <small><?= clean_output($p['phone'] ?: '') ?></small>
                      </td>
                      <td class="text-xs">
                        <?php if (!empty($p['website'])): ?>
                          <a href="<?= clean_output($p['website']) ?>" target="_blank" class="text-primary text-decoration-none">
                            Visit Site <i class="fas fa-arrow-up-right-from-square text-xs ms-1"></i>
                          </a>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <a href="<?= BASE_URL ?>/admin/publishers/index.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Publisher" onclick="return confirm('Delete this publisher?');">
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
