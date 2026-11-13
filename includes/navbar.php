<?php
/**
 * Navigation Bar Include
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

$currentUser = current_user();
$unreadNotifs = $currentUser ? get_recent_notifications($currentUser['id'], 5) : [];
$unreadCount = $currentUser ? get_unread_notifications_count($currentUser['id']) : 0;
$userRole = user_role();
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
  <div class="container-fluid">
    <!-- Brand -->
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
      <i class="fas fa-book-reader"></i>
      <span>OpenShelf</span>
    </a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler" type="button" data-mdb-collapse-init data-mdb-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <i class="fas fa-bars"></i>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <!-- Search Bar -->
      <form class="d-flex mx-auto my-2 my-lg-0" style="max-width: 420px; width: 100%;" action="<?= BASE_URL ?>/student/catalogue.php" method="GET">
        <div class="input-group">
          <input type="search" name="search" class="form-control rounded-start-pill bg-light border-0" placeholder="Search title, author, ISBN..." aria-label="Search" style="padding-left: 1.25rem;">
          <button class="btn btn-outline-secondary border-0 bg-light rounded-end-pill px-3" type="submit">
            <i class="fas fa-search text-muted"></i>
          </button>
        </div>
      </form>

      <!-- Nav Links -->
      <ul class="navbar-nav ms-auto align-items-center gap-1">
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/student/catalogue.php">
            <i class="fas fa-compass me-1"></i> Catalogue
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/student/catalogue.php?format=digital">
            <i class="fas fa-tablet-screen-button me-1"></i> Digital Library
          </a>
        </li>

        <?php if ($currentUser): ?>
          <!-- Role Dashboard Link -->
          <?php if ($userRole === 'Admin'): ?>
            <li class="nav-item">
              <a class="nav-link text-primary fw-semibold" href="<?= BASE_URL ?>/admin/dashboard.php">
                <i class="fas fa-shield-halved me-1"></i> Admin Portal
              </a>
            </li>
          <?php elseif ($userRole === 'Librarian'): ?>
            <li class="nav-item">
              <a class="nav-link text-primary fw-semibold" href="<?= BASE_URL ?>/librarian/dashboard.php">
                <i class="fas fa-book-bookmark me-1"></i> Librarian Desk
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/student/dashboard.php">
                <i class="fas fa-layer-group me-1"></i> My Dashboard
              </a>
            </li>
          <?php endif; ?>

          <!-- In-App Notifications Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link position-relative cursor-pointer" id="notifDropdown" role="button" data-mdb-dropdown-init aria-expanded="false">
              <i class="fas fa-bell fs-5 text-secondary"></i>
              <?php if ($unreadCount > 0): ?>
                <span id="notifBadge" class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                  <?= $unreadCount ?>
                </span>
              <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end notification-menu shadow-lg" aria-labelledby="notifDropdown">
              <li class="p-2 border-bottom d-flex justify-content-between align-items-center bg-light">
                <span class="fw-bold text-sm">Notifications</span>
                <span class="badge bg-primary rounded-pill"><?= $unreadCount ?> new</span>
              </li>
              <?php if (empty($unreadNotifs)): ?>
                <li class="p-3 text-center text-muted text-sm">No new notifications</li>
              <?php else: ?>
                <?php foreach ($unreadNotifs as $notif): ?>
                  <li>
                    <a class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>" href="<?= BASE_URL ?>/student/<?= clean_output($notif['link'] ?: 'dashboard.php') ?>" data-id="<?= $notif['id'] ?>">
                      <div class="d-flex align-items-start">
                        <div class="me-2 mt-1">
                          <?php if ($notif['type'] === 'overdue'): ?>
                            <i class="fas fa-circle-exclamation text-danger"></i>
                          <?php elseif ($notif['type'] === 'reservation'): ?>
                            <i class="fas fa-bookmark text-primary"></i>
                          <?php elseif ($notif['type'] === 'fine'): ?>
                            <i class="fas fa-receipt text-warning"></i>
                          <?php else: ?>
                            <i class="fas fa-bell text-info"></i>
                          <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                          <div class="fw-semibold text-sm mb-1"><?= clean_output($notif['title']) ?></div>
                          <div class="text-muted text-xs mb-1"><?= clean_output($notif['message']) ?></div>
                          <div class="text-muted text-xs"><i class="far fa-clock me-1"></i><?= time_ago($notif['created_at']) ?></div>
                        </div>
                      </div>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>
          </li>

          <!-- User Profile Dropdown -->
          <li class="nav-item dropdown ms-2">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" id="userMenuDropdown" role="button" data-mdb-dropdown-init aria-expanded="false">
              <img src="<?= get_user_avatar($currentUser['avatar'], $currentUser['full_name'] ?: $currentUser['username']) ?>" class="avatar-img" alt="Avatar">
              <div class="d-none d-md-block text-start">
                <span class="fw-semibold text-sm d-block text-dark"><?= clean_output($currentUser['full_name'] ?: $currentUser['username']) ?></span>
                <span class="badge bg-light text-muted border text-xs"><?= clean_output($currentUser['role_name']) ?></span>
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="userMenuDropdown">
              <li>
                <a class="dropdown-item" href="<?= BASE_URL ?>/student/profile.php">
                  <i class="fas fa-id-badge me-2 text-muted"></i> My Profile
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="<?= BASE_URL ?>/student/my-library.php">
                  <i class="fas fa-book-open-reader me-2 text-muted"></i> My Library
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                  <i class="fas fa-right-from-bracket me-2"></i> Sign Out
                </a>
              </li>
            </ul>
          </li>

        <?php else: ?>
          <!-- Guest Links -->
          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-primary btn-rounded btn-sm" href="<?= BASE_URL ?>/login.php">
              <i class="fas fa-right-to-bracket me-1"></i> Sign In
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-primary btn-rounded btn-sm text-white" href="<?= BASE_URL ?>/register.php">
              <i class="fas fa-user-plus me-1"></i> Join Library
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
