<?php
/**
 * Online Digital Book Reader
 * Immersive, distraction-free reading platform with progress saving, bookmarks, notes, and custom themes.
 */
require_once __DIR__ . '/../includes/auth_check.php';

$bookId = (int)($_GET['book_id'] ?? 0);
if ($bookId <= 0) {
    set_flash('danger', 'Invalid book requested for digital reading.');
    header("Location: " . BASE_URL . "/student/catalogue.php");
    exit;
}

$currentUser = current_user();
$userId = $currentUser['id'];

// Fetch book info
$book = db_fetch_one("SELECT * FROM books WHERE id = ? AND is_digital = 1", [$bookId], "i");
if (!$book) {
    set_flash('warning', 'This title is not currently available for digital reading.');
    header("Location: " . BASE_URL . "/student/book_details.php?id=" . $bookId);
    exit;
}

// Fetch digital book entry
$digitalBook = db_fetch_one("SELECT * FROM digital_books WHERE book_id = ?", [$bookId], "i");
if (!$digitalBook) {
    set_flash('warning', 'Digital edition files are currently being prepared for this title.');
    header("Location: " . BASE_URL . "/student/book_details.php?id=" . $bookId);
    exit;
}

// Fetch all chapters
$chapters = db_fetch_all(
    "SELECT * FROM digital_book_chapters WHERE digital_book_id = ? ORDER BY chapter_number ASC",
    [$digitalBook['id']],
    "i"
);

if (empty($chapters)) {
    set_flash('warning', 'No chapters found for this digital book.');
    header("Location: " . BASE_URL . "/student/book_details.php?id=" . $bookId);
    exit;
}

// Fetch user's saved progress
$progress = db_fetch_one("SELECT * FROM reading_progress WHERE user_id = ? AND book_id = ?", [$userId, $bookId], "ii");

// Determine which chapter to display
$chapterNum = isset($_GET['chapter']) ? (int)$_GET['chapter'] : ($progress['current_chapter'] ?? 1);
$currentChapter = null;
foreach ($chapters as $ch) {
    if ((int)$ch['chapter_number'] === $chapterNum) {
        $currentChapter = $ch;
        break;
    }
}
if (!$currentChapter) {
    $currentChapter = $chapters[0];
    $chapterNum = (int)$currentChapter['chapter_number'];
}

$totalChapters = count($chapters);

// Fetch existing bookmarks & notes for drawer
$bookmarks = db_fetch_all("SELECT * FROM bookmarks WHERE user_id = ? AND book_id = ? ORDER BY chapter_number ASC, page_number ASC", [$userId, $bookId], "ii");
$notes = db_fetch_all("SELECT * FROM notes WHERE user_id = ? AND book_id = ? ORDER BY chapter_number ASC, created_at DESC", [$userId, $bookId], "ii");

$pageTitle = 'Reading: ' . clean_output($book['title']) . ' - OpenShelf';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= clean_output($pageTitle) ?></title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:ital,wght@0,300;0,400;0,700;1,300;1,400&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- MDBootstrap CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/mdb.min.css">

  <!-- Reader Styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/reader.css">

  <script>
    window.APP_BASE_URL = "<?= BASE_URL ?>";
  </script>
</head>
<body class="reader-body theme-light">

<!-- Top Navigation & Controls Bar -->
<header class="reader-topbar">
  <!-- Left: Exit & Book Details -->
  <div class="d-flex align-items-center gap-3">
    <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $book['id'] ?>" class="btn btn-outline-secondary btn-sm btn-rounded" title="Exit to Book Details">
      <i class="fas fa-arrow-left me-1"></i> Exit
    </a>
    <div>
      <h6 class="mb-0 fw-bold text-truncate" style="max-width: 260px;" title="<?= clean_output($book['title']) ?>">
        <?= clean_output($book['title']) ?>
      </h6>
      <small class="text-muted d-block text-xs">
        Chapter <?= $chapterNum ?> of <?= $totalChapters ?>
      </small>
    </div>
  </div>

  <!-- Center: Progress Track & Chapter Selector -->
  <div class="d-none d-md-flex align-items-center gap-3">
    <select class="form-select form-select-sm" style="width: auto; min-width: 180px;" onchange="window.location.href='<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=' + this.value">
      <?php foreach ($chapters as $ch): ?>
        <option value="<?= $ch['chapter_number'] ?>" <?= (int)$ch['chapter_number'] === $chapterNum ? 'selected' : '' ?>>
          Ch. <?= $ch['chapter_number'] ?>: <?= clean_output($ch['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <div class="d-flex align-items-center gap-2">
      <div class="reader-progress-track">
        <div class="reader-progress-fill" id="readerProgressFill"></div>
      </div>
      <span class="text-xs fw-semibold" id="readerProgressText">0%</span>
    </div>

    <!-- Sync status indicator -->
    <span id="saveStatusIndicator" class="save-status-pill text-xs">
      <i class="fas fa-check text-muted me-1"></i> Synced
    </span>
  </div>

  <!-- Right: Tool Drawers & Preferences Dropdown -->
  <div class="d-flex align-items-center gap-2">
    <!-- TOC Drawer Button -->
    <button type="button" class="btn btn-light btn-sm btn-floating" title="Table of Contents" onclick="ReaderEngine.toggleDrawer('drawerToc')">
      <i class="fas fa-list-ul"></i>
    </button>

    <!-- Bookmarks Drawer Button -->
    <button type="button" class="btn btn-light btn-sm btn-floating" title="Bookmarks" onclick="ReaderEngine.toggleDrawer('drawerBookmarks')">
      <i class="fas fa-bookmark text-primary"></i>
    </button>

    <!-- Notes Drawer Button -->
    <button type="button" class="btn btn-light btn-sm btn-floating" title="Reading Notes" onclick="ReaderEngine.toggleDrawer('drawerNotes')">
      <i class="fas fa-note-sticky text-warning"></i>
    </button>

    <!-- Preferences Dropdown -->
    <div class="dropdown">
      <button class="btn btn-light btn-sm btn-floating" type="button" id="prefsDropdown" data-mdb-dropdown-init aria-expanded="false" title="Reading Display Preferences">
        <i class="fas fa-font"></i>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg" aria-labelledby="prefsDropdown" style="width: 280px;">
        <h6 class="fw-bold mb-2 text-sm">Theme</h6>
        <div class="d-flex gap-2 mb-3">
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 p-1 bg-white text-dark" onclick="ReaderEngine.setTheme('theme-light')">Light</button>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 p-1" style="background:#f5eedc; color:#4a3b2c;" onclick="ReaderEngine.setTheme('theme-sepia')">Sepia</button>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 p-1 bg-dark text-white" onclick="ReaderEngine.setTheme('theme-dark')">Dark</button>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 p-1 text-white" style="background:#0b0f19;" onclick="ReaderEngine.setTheme('theme-night')">Night</button>
        </div>

        <h6 class="fw-bold mb-2 text-sm">Font Size</h6>
        <div class="d-flex gap-1 mb-3">
          <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" onclick="ReaderEngine.setFontSize('font-size-sm')">Small</button>
          <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" onclick="ReaderEngine.setFontSize('font-size-md')">Normal</button>
          <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" onclick="ReaderEngine.setFontSize('font-size-lg')">Large</button>
          <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" onclick="ReaderEngine.setFontSize('font-size-xl')">XL</button>
        </div>

        <h6 class="fw-bold mb-2 text-sm">Font Type</h6>
        <div class="d-flex gap-1 mb-3">
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 font-serif" onclick="ReaderEngine.setFontFamily('font-serif')">Serif</button>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 font-sans" onclick="ReaderEngine.setFontFamily('font-sans')">Sans</button>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1 font-mono" onclick="ReaderEngine.setFontFamily('font-mono')">Mono</button>
        </div>

        <h6 class="fw-bold mb-2 text-sm">Margins / Width</h6>
        <div class="d-flex gap-1">
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1" onclick="ReaderEngine.setWidth('width-compact')">Compact</button>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1" onclick="ReaderEngine.setWidth('width-normal')">Standard</button>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1" onclick="ReaderEngine.setWidth('width-wide')">Wide</button>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Main Reader Document Content -->
<main class="reader-container width-normal font-size-md font-serif" id="readerContent">
  <h1 class="reader-chapter-title"><?= clean_output($currentChapter['title']) ?></h1>

  <div class="reader-body-text">
    <?php
      $paragraphs = explode("\n\n", $currentChapter['content']);
      foreach ($paragraphs as $para):
        $para = trim($para);
        if (!empty($para)):
    ?>
      <p class="reader-paragraph"><?= nl2br(clean_output($para)) ?></p>
    <?php
        endif;
      endforeach;
    ?>
  </div>

  <div class="text-center my-5 py-4 border-top">
    <div class="text-muted text-sm mb-3">End of <?= clean_output($currentChapter['title']) ?></div>
    <div class="d-flex justify-content-center gap-3">
      <?php if ($chapterNum > 1): ?>
        <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=<?= $chapterNum - 1 ?>" class="btn btn-outline-secondary btn-rounded px-4">
          <i class="fas fa-chevron-left me-1"></i> Previous Chapter
        </a>
      <?php endif; ?>

      <?php if ($chapterNum < $totalChapters): ?>
        <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=<?= $chapterNum + 1 ?>" class="btn btn-primary btn-rounded px-4">
          Next Chapter <i class="fas fa-chevron-right ms-1"></i>
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/student/book_details.php?id=<?= $book['id'] ?>" class="btn btn-success btn-rounded px-4">
          <i class="fas fa-flag-checkered me-1"></i> Finish Reading Book
        </a>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Floating Bottom Bar -->
<div class="reader-bottom-nav">
  <?php if ($chapterNum > 1): ?>
    <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=<?= $chapterNum - 1 ?>" class="btn btn-sm btn-link text-dark p-1" title="Previous Chapter">
      <i class="fas fa-chevron-left"></i>
    </a>
  <?php endif; ?>

  <button type="button" class="btn btn-sm btn-outline-primary btn-rounded px-3" onclick="handleQuickBookmark()">
    <i class="fas fa-bookmark me-1"></i> Bookmark
  </button>

  <button type="button" class="btn btn-sm btn-outline-warning btn-rounded px-3 text-dark" onclick="ReaderEngine.toggleDrawer('drawerNotes')">
    <i class="fas fa-pen me-1"></i> Note
  </button>

  <?php if ($chapterNum < $totalChapters): ?>
    <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=<?= $chapterNum + 1 ?>" class="btn btn-sm btn-link text-dark p-1" title="Next Chapter">
      <i class="fas fa-chevron-right"></i>
    </a>
  <?php endif; ?>
</div>

<!-- Drawer 1: Table of Contents -->
<div class="reader-drawer" id="drawerToc">
  <div class="drawer-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-list-ul me-2"></i> Table of Contents</h6>
    <button type="button" class="btn-close" onclick="ReaderEngine.closeAllDrawers()"></button>
  </div>
  <div class="drawer-body p-0">
    <div class="list-group list-group-flush">
      <?php foreach ($chapters as $ch): ?>
        <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=<?= $ch['chapter_number'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= (int)$ch['chapter_number'] === $chapterNum ? 'active fw-bold' : '' ?>">
          <span>Ch. <?= $ch['chapter_number'] ?>: <?= clean_output($ch['title']) ?></span>
          <?php if ((int)$ch['chapter_number'] === $chapterNum): ?>
            <i class="fas fa-book-open"></i>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Drawer 2: Bookmarks -->
<div class="reader-drawer" id="drawerBookmarks">
  <div class="drawer-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-bookmark text-primary me-2"></i> Bookmarks for this Book</h6>
    <button type="button" class="btn-close" onclick="ReaderEngine.closeAllDrawers()"></button>
  </div>
  <div class="drawer-body">
    <div class="p-3 bg-light rounded-3 mb-3 border">
      <h6 class="text-xs fw-bold text-uppercase text-muted mb-2">Add New Bookmark</h6>
      <input type="text" id="bmTitleInput" class="form-control form-control-sm mb-2" placeholder="Bookmark Title (e.g. Chapter <?= $chapterNum ?> Quote)">
      <textarea id="bmNoteInput" class="form-control form-control-sm mb-2" rows="2" placeholder="Optional personal note..."></textarea>
      <button type="button" class="btn btn-primary btn-sm w-100 btn-rounded" onclick="submitNewBookmark()">
        <i class="fas fa-plus me-1"></i> Save Bookmark
      </button>
    </div>

    <div id="bookmarksListContainer">
      <?php if (empty($bookmarks)): ?>
        <p class="text-muted text-xs text-center py-3" id="noBookmarksMsg">No bookmarks saved yet for this book.</p>
      <?php else: ?>
        <?php foreach ($bookmarks as $bm): ?>
          <div class="p-2 border rounded-3 mb-2 bg-white bookmark-card" id="bmItem-<?= $bm['id'] ?>">
            <div class="d-flex justify-content-between align-items-start">
              <a href="<?= BASE_URL ?>/reader/reader.php?book_id=<?= $book['id'] ?>&chapter=<?= $bm['chapter_number'] ?>" class="fw-bold text-dark text-decoration-none text-sm hover-primary">
                <?= clean_output($bm['title']) ?>
              </a>
              <button class="btn btn-link text-danger p-0 ms-2" onclick="deleteBookmark(<?= $bm['id'] ?>)">
                <i class="fas fa-trash-can text-xs"></i>
              </button>
            </div>
            <div class="text-xs text-muted">
              Chapter <?= $bm['chapter_number'] ?> &bull; Page <?= $bm['page_number'] ?>
            </div>
            <?php if (!empty($bm['note'])): ?>
              <small class="text-muted d-block mt-1 fst-italic">"<?= clean_output($bm['note']) ?>"</small>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Drawer 3: Personal Reading Notes -->
<div class="reader-drawer" id="drawerNotes">
  <div class="drawer-header">
    <h6 class="fw-bold mb-0"><i class="fas fa-note-sticky text-warning me-2"></i> Reading Notes</h6>
    <button type="button" class="btn-close" onclick="ReaderEngine.closeAllDrawers()"></button>
  </div>
  <div class="drawer-body">
    <div class="p-3 bg-light rounded-3 mb-3 border">
      <h6 class="text-xs fw-bold text-uppercase text-muted mb-2">Add Note (Ch. <?= $chapterNum ?>)</h6>
      <textarea id="noteContentInput" class="form-control form-control-sm mb-2" rows="3" placeholder="Write your insights, chapter summary, or reflections..."></textarea>
      <button type="button" class="btn btn-warning btn-sm w-100 btn-rounded fw-bold text-dark" onclick="submitNewNote()">
        <i class="fas fa-paper-plane me-1"></i> Save Note
      </button>
    </div>

    <div id="notesListContainer">
      <?php if (empty($notes)): ?>
        <p class="text-muted text-xs text-center py-3" id="noNotesMsg">No notes saved yet for this book.</p>
      <?php else: ?>
        <?php foreach ($notes as $n): ?>
          <div class="p-2 border rounded-3 mb-2 bg-white note-card" id="noteItem-<?= $n['id'] ?>">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <span class="badge bg-light text-primary border text-xs">Chapter <?= $n['chapter_number'] ?> &bull; Page <?= $n['page_number'] ?></span>
              <button class="btn btn-link text-danger p-0 ms-2" onclick="deleteNote(<?= $n['id'] ?>)">
                <i class="fas fa-trash-can text-xs"></i>
              </button>
            </div>
            <div class="text-xs text-dark" style="white-space: pre-wrap;"><?= clean_output($n['content']) ?></div>
            <small class="text-muted text-xs d-block mt-1"><?= time_ago($n['created_at']) ?></small>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MDBootstrap & App JS -->
<script src="<?= BASE_URL ?>/assets/vendor/mdb.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/reader.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    ReaderEngine.init({
      bookId: <?= $book['id'] ?>,
      currentChapter: <?= $chapterNum ?>,
      totalChapters: <?= $totalChapters ?>
    });
  });

  function handleQuickBookmark() {
    ReaderEngine.addBookmark('Chapter <?= $chapterNum ?> Marker', 'Bookmarked while reading')
      .then(res => {
        if (res.success) {
          alert('Page bookmarked successfully!');
          location.reload();
        } else {
          alert('Could not save bookmark.');
        }
      });
  }

  function submitNewBookmark() {
    const title = document.getElementById('bmTitleInput').value.trim();
    const note = document.getElementById('bmNoteInput').value.trim();
    ReaderEngine.addBookmark(title, note)
      .then(res => {
        if (res.success) {
          document.getElementById('bmTitleInput').value = '';
          document.getElementById('bmNoteInput').value = '';
          location.reload();
        } else {
          alert('Failed to save bookmark.');
        }
      });
  }

  function deleteBookmark(id) {
    if (!confirm('Remove this bookmark?')) return;
    const payload = new URLSearchParams({ action: 'delete', book_id: <?= $book['id'] ?>, bookmark_id: id });
    fetch(window.APP_BASE_URL + '/reader/bookmark.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: payload.toString()
    }).then(res => res.json()).then(data => {
      if (data.success) {
        const el = document.getElementById('bmItem-' + id);
        if (el) el.remove();
      }
    });
  }

  function submitNewNote() {
    const content = document.getElementById('noteContentInput').value.trim();
    if (!content) {
      alert('Please enter note content.');
      return;
    }
    ReaderEngine.addNote(content)
      .then(res => {
        if (res.success) {
          document.getElementById('noteContentInput').value = '';
          location.reload();
        } else {
          alert('Failed to save note.');
        }
      });
  }

  function deleteNote(id) {
    if (!confirm('Remove this note?')) return;
    const payload = new URLSearchParams({ action: 'delete', book_id: <?= $book['id'] ?>, note_id: id });
    fetch(window.APP_BASE_URL + '/reader/note.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: payload.toString()
    }).then(res => res.json()).then(data => {
      if (data.success) {
        const el = document.getElementById('noteItem-' + id);
        if (el) el.remove();
      }
    });
  }
</script>
</body>
</html>
