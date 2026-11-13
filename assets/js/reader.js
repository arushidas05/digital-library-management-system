/**
 * Online Reader Engine
 * Handles themes, font sizing, scroll tracking, auto-saving reading progress, bookmarks, and notes.
 */

const ReaderEngine = (function () {
  let bookId = 0;
  let currentChapter = 1;
  let totalChapters = 1;
  let saveTimer = null;

  function init(config) {
    bookId = config.bookId;
    currentChapter = config.currentChapter || 1;
    totalChapters = config.totalChapters || 1;

    // Restore preferences
    applyStoredPreferences();

    // Setup scroll tracking
    window.addEventListener('scroll', handleScrollDebounced);

    // Initial progress computation
    updateProgressBar();

    // Auto-save progress every 20 seconds
    setInterval(saveProgressToServer, 20000);
  }

  function applyStoredPreferences() {
    const theme = localStorage.getItem('reader_theme') || 'theme-light';
    const fontSize = localStorage.getItem('reader_font_size') || 'font-size-md';
    const fontFamily = localStorage.getItem('reader_font_family') || 'font-serif';
    const width = localStorage.getItem('reader_width') || 'width-normal';

    setTheme(theme);
    setFontSize(fontSize);
    setFontFamily(fontFamily);
    setWidth(width);
  }

  function setTheme(theme) {
    document.body.classList.remove('theme-light', 'theme-sepia', 'theme-dark', 'theme-night');
    document.body.classList.add(theme);
    localStorage.setItem('reader_theme', theme);
  }

  function setFontSize(sizeClass) {
    const container = document.getElementById('readerContent');
    if (container) {
      container.classList.remove('font-size-sm', 'font-size-md', 'font-size-lg', 'font-size-xl');
      container.classList.add(sizeClass);
      localStorage.setItem('reader_font_size', sizeClass);
    }
  }

  function setFontFamily(fontClass) {
    const container = document.getElementById('readerContent');
    if (container) {
      container.classList.remove('font-sans', 'font-serif', 'font-mono');
      container.classList.add(fontClass);
      localStorage.setItem('reader_font_family', fontClass);
    }
  }

  function setWidth(widthClass) {
    const container = document.getElementById('readerContent');
    if (container) {
      container.classList.remove('width-compact', 'width-normal', 'width-wide');
      container.classList.add(widthClass);
      localStorage.setItem('reader_width', widthClass);
    }
  }

  function handleScrollDebounced() {
    updateProgressBar();
    clearTimeout(saveTimer);
    saveTimer = setTimeout(saveProgressToServer, 3000);
  }

  function calculateProgress() {
    const h = document.documentElement;
    const b = document.body;
    const st = 'scrollTop';
    const sh = 'scrollHeight';
    const scrollPercent = (h[st] || b[st]) / ((h[sh] || b[sh]) - h.clientHeight);
    const chapterScroll = isNaN(scrollPercent) ? 0 : Math.max(0, Math.min(1, scrollPercent));

    // Calculate total progress across all chapters
    const baseProgress = ((currentChapter - 1) / totalChapters) * 100;
    const currentProgress = (chapterScroll / totalChapters) * 100;
    const totalProgress = Math.min(100, Math.round(baseProgress + currentProgress));

    return {
      chapterScroll: Math.round(chapterScroll * 100),
      totalProgress: totalProgress
    };
  }

  function updateProgressBar() {
    const prog = calculateProgress();
    const fill = document.getElementById('readerProgressFill');
    const text = document.getElementById('readerProgressText');
    if (fill) fill.style.width = prog.totalProgress + '%';
    if (text) text.innerText = prog.totalProgress + '%';
  }

  function saveProgressToServer() {
    const prog = calculateProgress();
    const statusEl = document.getElementById('saveStatusIndicator');
    
    if (statusEl) {
      statusEl.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
      statusEl.style.display = 'inline-flex';
    }

    const payload = new URLSearchParams({
      book_id: bookId,
      current_chapter: currentChapter,
      progress_percentage: prog.totalProgress,
      current_page: Math.max(1, Math.round(prog.chapterScroll / 10))
    });

    fetch((window.APP_BASE_URL || '') + '/reader/save_progress.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: payload.toString()
    })
    .then(res => res.json())
    .then(data => {
      if (statusEl) {
        if (data.success) {
          statusEl.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Saved (' + prog.totalProgress + '%)';
          setTimeout(() => {
            if (statusEl) statusEl.innerHTML = '<i class="fas fa-check text-muted me-1"></i> Synced';
          }, 2500);
        } else {
          statusEl.innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-1"></i> Save error';
        }
      }
    })
    .catch(err => {
      if (statusEl) statusEl.innerHTML = '<i class="fas fa-wifi text-muted me-1"></i> Offline';
    });
  }

  // Drawer Management
  function toggleDrawer(drawerId) {
    const drawer = document.getElementById(drawerId);
    if (!drawer) return;
    
    // Close other drawers
    document.querySelectorAll('.reader-drawer').forEach(d => {
      if (d.id !== drawerId) d.classList.remove('active');
    });

    drawer.classList.toggle('active');
  }

  function closeAllDrawers() {
    document.querySelectorAll('.reader-drawer').forEach(d => d.classList.remove('active'));
  }

  // Bookmarking
  function addBookmark(title, note) {
    const pageNum = Math.max(1, Math.round(calculateProgress().chapterScroll / 10));
    const payload = new URLSearchParams({
      action: 'add',
      book_id: bookId,
      chapter_number: currentChapter,
      page_number: pageNum,
      title: title || ('Chapter ' + currentChapter + ' Marker'),
      note: note || ''
    });

    return fetch((window.APP_BASE_URL || '') + '/reader/bookmark.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: payload.toString()
    }).then(res => res.json());
  }

  // Notes
  function addNote(content) {
    const pageNum = Math.max(1, Math.round(calculateProgress().chapterScroll / 10));
    const payload = new URLSearchParams({
      action: 'add',
      book_id: bookId,
      chapter_number: currentChapter,
      page_number: pageNum,
      content: content
    });

    return fetch((window.APP_BASE_URL || '') + '/reader/note.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: payload.toString()
    }).then(res => res.json());
  }

  return {
    init,
    setTheme,
    setFontSize,
    setFontFamily,
    setWidth,
    toggleDrawer,
    closeAllDrawers,
    saveProgressToServer,
    addBookmark,
    addNote
  };
})();
