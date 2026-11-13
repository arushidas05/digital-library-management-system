<?php
/**
 * Member Personal Reading Notes Page
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'My Reading Notes - OpenShelf';
$user = current_user();
$userId = $user['id'];

// Handle Delete Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $noteId = (int)($_POST['note_id'] ?? 0);
    db_execute("DELETE FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId], "ii");
    set_flash('info', 'Reading note removed.');
    header("Location: " . BASE_URL . "/student/notes.php");
    exit;
}

// Fetch all notes
$notes = db_fetch_all(
    "SELECT n.*, b.title as book_title, b.cover_image
     FROM notes n
     JOIN books b ON n.book_id = b.id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-note-sticky text-warning me-2"></i> Reading Notes & Annotations</h3>
        <p class="text-muted text-sm mb-0">Personal notes, thoughts, and study highlights captured during digital reading</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/student/catalogue.php?format=digital" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-book-open me-1"></i> Open Online Reader
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <?php if (empty($notes)): ?>
      <div class="card p-5 text-center text-muted border shadow-sm">
        <i class="far fa-note-sticky fs-1 text-light mb-3"></i>
        <h5 class="fw-bold text-dark">No Reading Notes Yet</h5>
        <p class="text-sm">While reading in the digital reader, open the Notes panel to jot down chapter insights and quotes.</p>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($notes as $note): ?>
          <div class="col-md-6">
            <div class="card p-3 border shadow-sm h-100 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <div>
                  <h6 class="fw-bold mb-0 text-dark"><?= clean_output($note['book_title']) ?></h6>
                  <span class="badge bg-light text-primary border text-xs">Chapter <?= $note['chapter_number'] ?> &bull; Page <?= $note['page_number'] ?></span>
                </div>
                <span class="text-xs text-muted"><?= time_ago($note['created_at']) ?></span>
              </div>

              <div class="p-3 bg-light rounded-3 my-2 text-sm text-dark flex-grow-1" style="white-space: pre-wrap; font-family: 'Inter', sans-serif;">
                <?= clean_output($note['content']) ?>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $note['book_id'] ?>&chapter=<?= $note['chapter_number'] ?>" class="text-xs text-primary text-decoration-none fw-semibold">
                  <i class="fas fa-arrow-up-right-from-square me-1"></i> Jump to Chapter in Reader
                </a>

                <form action="<?= BASE_URL ?>/student/notes.php" method="POST" onsubmit="return confirm('Delete this note?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm border-0" title="Delete Note">
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
