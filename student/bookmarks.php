<?php
/**
 * Member Saved Bookmarks Page
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'My Bookmarks - OpenShelf';
$user = current_user();
$userId = $user['id'];

// Handle Delete Bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $bmId = (int)($_POST['bookmark_id'] ?? 0);
    db_execute("DELETE FROM bookmarks WHERE id = ? AND user_id = ?", [$bmId, $userId], "ii");
    set_flash('info', 'Bookmark removed.');
    header("Location: " . BASE_URL . "/student/bookmarks.php");
    exit;
}

// Fetch all bookmarks
$bookmarks = db_fetch_all(
    "SELECT bm.*, b.title, b.cover_image
     FROM bookmarks bm
     JOIN books b ON bm.book_id = b.id
     WHERE bm.user_id = ?
     ORDER BY bm.created_at DESC",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-bookmark text-primary me-2"></i> Saved Bookmarks</h3>
        <p class="text-muted text-sm mb-0">Jump directly to your marked chapters and book pages</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/my-library.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-book-open me-1"></i> Continue Reading
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <?php if (empty($bookmarks)): ?>
      <div class="card p-5 text-center text-muted border shadow-sm">
        <i class="far fa-bookmark fs-1 text-light mb-3"></i>
        <h5 class="fw-bold text-dark">No Bookmarks Saved Yet</h5>
        <p class="text-sm">While reading any digital book in the online reader, click the bookmark icon to mark key passages.</p>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($bookmarks as $bm): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card p-3 border shadow-sm h-100 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-light text-primary border">
                  Chapter <?= $bm['chapter_number'] ?> &bull; Page <?= $bm['page_number'] ?>
                </span>
                <span class="text-xs text-muted"><?= time_ago($bm['created_at']) ?></span>
              </div>

              <h6 class="fw-bold mb-1 text-dark"><?= clean_output($bm['title']) ?></h6>
              <div class="text-xs text-muted mb-2">Book: <strong><?= clean_output($bm['title']) ?></strong></div>

              <?php if (!empty($bm['note'])): ?>
                <p class="p-2 bg-light rounded text-xs text-muted fst-italic mb-3">
                  "<?= clean_output($bm['note']) ?>"
                </p>
              <?php endif; ?>

              <div class="mt-auto pt-2 border-top d-flex gap-2">
                <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $bm['book_id'] ?>&chapter=<?= $bm['chapter_number'] ?>" class="btn btn-primary btn-sm flex-grow-1 btn-rounded">
                  <i class="fas fa-glasses me-1"></i> Open Page in Reader
                </a>
                <form action="<?= BASE_URL ?>/student/bookmarks.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this bookmark?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="bookmark_id" value="<?= $bm['id'] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm btn-rounded" title="Delete Bookmark">
                    <i class="fas fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
