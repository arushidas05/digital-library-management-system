<?php
/**
 * Unified Responsive Dashboard Sidebar
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

$currentUser = current_user();
$userRole = user_role();
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';

function is_active_route($path) {
    global $currentScript;
    return strpos($currentScript, $path) !== false ? 'active' : '';
}
?>
<aside class="sidebar d-none d-lg-flex">
  <div class="sidebar-content w-100">

    <?php if ($userRole === 'Admin'): ?>
      <!-- Admin Navigation -->
      <div class="sidebar-title">System Administration</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= is_active_route('/admin/dashboard.php') ?>">
            <i class="fas fa-gauge"></i> Overview
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/users/index.php" class="<?= is_active_route('/admin/users/') ?>">
            <i class="fas fa-users-gear"></i> Manage Users
          </a>
        </li>
      </ul>

      <div class="sidebar-title">Catalogue Master</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/librarian/books/index.php" class="<?= is_active_route('/librarian/books/') ?>">
            <i class="fas fa-book"></i> Book Records
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/categories/index.php" class="<?= is_active_route('/admin/categories/') ?>">
            <i class="fas fa-tags"></i> Categories
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/authors/index.php" class="<?= is_active_route('/admin/authors/') ?>">
            <i class="fas fa-feather-pointed"></i> Authors
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/publishers/index.php" class="<?= is_active_route('/admin/publishers/') ?>">
            <i class="fas fa-building"></i> Publishers
          </a>
        </li>
      </ul>

      <div class="sidebar-title">Circulation & Operations</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/librarian/copies/index.php" class="<?= is_active_route('/librarian/copies/') ?>">
            <i class="fas fa-barcode"></i> Physical Copies
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/borrowing/index.php" class="<?= is_active_route('/librarian/borrowing/') ?>">
            <i class="fas fa-hand-holding-hand"></i> Circulation Desk
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/reservations/index.php" class="<?= is_active_route('/librarian/reservations/') ?>">
            <i class="fas fa-clock-rotate-left"></i> Reservations
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/fines/index.php" class="<?= is_active_route('/librarian/fines/') ?>">
            <i class="fas fa-receipt"></i> Fines Ledger
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/reports/index.php" class="<?= is_active_route('/librarian/reports/') ?>">
            <i class="fas fa-chart-pie"></i> Reports & Analytics
          </a>
        </li>
      </ul>

      <div class="sidebar-title">Platform Config</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/admin/settings/index.php" class="<?= is_active_route('/admin/settings/') ?>">
            <i class="fas fa-sliders"></i> System Settings
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/audit_logs/index.php" class="<?= is_active_route('/admin/audit_logs/') ?>">
            <i class="fas fa-clipboard-list"></i> Audit Logs
          </a>
        </li>
      </ul>

    <?php elseif ($userRole === 'Librarian'): ?>
      <!-- Librarian Navigation -->
      <div class="sidebar-title">Desk Operations</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/librarian/dashboard.php" class="<?= is_active_route('/librarian/dashboard.php') ?>">
            <i class="fas fa-gauge"></i> Desk Dashboard
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/borrowing/issue.php" class="<?= is_active_route('/borrowing/issue.php') ?>">
            <i class="fas fa-plus-circle text-success"></i> Issue Book
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/borrowing/return.php" class="<?= is_active_route('/borrowing/return.php') ?>">
            <i class="fas fa-arrow-rotate-left text-primary"></i> Return Book
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/borrowing/index.php" class="<?= is_active_route('/borrowing/index.php') ?>">
            <i class="fas fa-list-check"></i> Active Loans
          </a>
        </li>
      </ul>

      <div class="sidebar-title">Catalogue & Copies</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/librarian/books/index.php" class="<?= is_active_route('/librarian/books/index.php') ?>">
            <i class="fas fa-book"></i> Books Catalogue
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/books/add.php" class="<?= is_active_route('/librarian/books/add.php') ?>">
            <i class="fas fa-folder-plus"></i> Add New Book
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/copies/index.php" class="<?= is_active_route('/librarian/copies/') ?>">
            <i class="fas fa-barcode"></i> Manage Physical Copies
          </a>
        </li>
      </ul>

      <div class="sidebar-title">Reservations & Fines</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/librarian/reservations/index.php" class="<?= is_active_route('/librarian/reservations/') ?>">
            <i class="fas fa-clock-rotate-left"></i> Reservations Queue
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/fines/index.php" class="<?= is_active_route('/librarian/fines/') ?>">
            <i class="fas fa-file-invoice-dollar"></i> Fines & Payments
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/librarian/reports/index.php" class="<?= is_active_route('/librarian/reports/') ?>">
            <i class="fas fa-chart-column"></i> Library Reports
          </a>
        </li>
      </ul>

    <?php else: ?>
      <!-- Student / Member Navigation -->
      <div class="sidebar-title">Member Space</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/student/dashboard.php" class="<?= is_active_route('/student/dashboard.php') ?>">
            <i class="fas fa-house"></i> Overview
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/student/my-library.php" class="<?= is_active_route('/student/my-library.php') ?>">
            <i class="fas fa-book-open-reader"></i> My Library
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/student/catalogue.php" class="<?= is_active_route('/student/catalogue.php') ?>">
            <i class="fas fa-compass"></i> Discover Books
          </a>
        </li>
      </ul>

      <div class="sidebar-title">Circulation & Holds</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/student/borrowed-books.php" class="<?= is_active_route('/student/borrowed-books.php') ?>">
            <i class="fas fa-hand-holding-box"></i> Borrowed Books
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/student/reservations.php" class="<?= is_active_route('/student/reservations.php') ?>">
            <i class="fas fa-clock-rotate-left"></i> My Reservations
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/student/wishlist.php" class="<?= is_active_route('/student/wishlist.php') ?>">
            <i class="fas fa-heart text-danger"></i> Wishlist
          </a>
        </li>
      </ul>

      <div class="sidebar-title">Reading Space</div>
      <ul class="sidebar-menu">
        <li>
          <a href="<?= BASE_URL ?>/student/reading-history.php" class="<?= is_active_route('/student/reading-history.php') ?>">
            <i class="fas fa-clock"></i> Reading History
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/student/bookmarks.php" class="<?= is_active_route('/student/bookmarks.php') ?>">
            <i class="fas fa-bookmark text-primary"></i> Bookmarks
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/student/notes.php" class="<?= is_active_route('/student/notes.php') ?>">
            <i class="fas fa-note-sticky text-warning"></i> Reading Notes
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/student/reading-statistics.php" class="<?= is_active_route('/student/reading-statistics.php') ?>">
            <i class="fas fa-chart-line text-success"></i> Reading Stats
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/student/reviews.php" class="<?= is_active_route('/student/reviews.php') ?>">
            <i class="fas fa-star text-warning"></i> My Reviews
          </a>
        </li>
      </ul>
    <?php endif; ?>

  </div>
</aside>
