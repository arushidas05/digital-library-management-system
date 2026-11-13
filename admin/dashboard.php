<?php
/**
 * Administrator Overview Dashboard
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role('Admin');

$pageTitle = 'Administrator Console - OpenShelf';

// Admin KPI metrics
$totalUsers = db_count('users');
$totalStudents = db_count('users', "role_id = 3");
$totalLibrarians = db_count('users', "role_id = 2");
$totalAdmins = db_count('users', "role_id = 1");

$totalBooks = db_count('books', "status = 'active'");
$totalCopies = db_count('book_copies');
$activeLoans = db_count('borrowings', "status IN ('Issued', 'Overdue')");
$totalCategories = db_count('categories');
$totalAuthors = db_count('authors');

// Recent Audit Logs
$recentLogs = db_fetch_all(
    "SELECT l.*, u.username 
     FROM audit_logs l 
     LEFT JOIN users u ON l.user_id = u.id 
     ORDER BY l.created_at DESC LIMIT 8"
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-shield-halved text-primary me-2"></i> System Administration Portal</h3>
        <p class="text-muted text-sm mb-0">Platform governance, user authorization, system configurations, and security audit logs</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/admin/settings/index.php" class="btn btn-outline-primary btn-sm btn-rounded">
          <i class="fas fa-sliders me-1"></i> Configure Settings
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Stat KPI Row -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon primary">
            <i class="fas fa-users"></i>
          </div>
          <div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Total Accounts (<?= $totalStudents ?> Students)</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon success">
            <i class="fas fa-book"></i>
          </div>
          <div>
            <div class="stat-value"><?= $totalBooks ?></div>
            <div class="stat-label">Active Book Titles</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon info">
            <i class="fas fa-barcode"></i>
          </div>
          <div>
            <div class="stat-value"><?= $totalCopies ?></div>
            <div class="stat-label">Physical Copies (<?= $activeLoans ?> on loan)</div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stat-card">
          <div class="stat-icon warning">
            <i class="fas fa-user-shield"></i>
          </div>
          <div>
            <div class="stat-value"><?= $totalLibrarians + $totalAdmins ?></div>
            <div class="stat-label">Staff & Librarians</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin Shortcuts Grid -->
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="card p-3 border shadow-sm h-100">
          <div class="d-flex align-items-center gap-3 mb-2">
            <div class="stat-icon primary" style="width:40px; height:40px; font-size:1.1rem;"><i class="fas fa-users-gear"></i></div>
            <h6 class="fw-bold mb-0">User & Role Management</h6>
          </div>
          <p class="text-muted text-xs mb-3">Manage user accounts, assign admin/librarian privileges, and suspend unauthorized users.</p>
          <div class="d-flex gap-2 mt-auto">
            <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn btn-sm btn-outline-primary btn-rounded flex-grow-1">View Users</a>
            <a href="<?= BASE_URL ?>/admin/users/add.php" class="btn btn-sm btn-primary btn-rounded">+ Add User</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 border shadow-sm h-100">
          <div class="d-flex align-items-center gap-3 mb-2">
            <div class="stat-icon success" style="width:40px; height:40px; font-size:1.1rem;"><i class="fas fa-tags"></i></div>
            <h6 class="fw-bold mb-0">Taxonomy & Authors</h6>
          </div>
          <p class="text-muted text-xs mb-3">Organize <?= $totalCategories ?> categories, <?= $totalAuthors ?> author biographies, and publishing house records.</p>
          <div class="d-flex gap-2 mt-auto">
            <a href="<?= BASE_URL ?>/admin/categories/index.php" class="btn btn-sm btn-outline-success btn-rounded flex-grow-1">Categories</a>
            <a href="<?= BASE_URL ?>/admin/authors/index.php" class="btn btn-sm btn-outline-success btn-rounded flex-grow-1">Authors</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 border shadow-sm h-100">
          <div class="d-flex align-items-center gap-3 mb-2">
            <div class="stat-icon warning" style="width:40px; height:40px; font-size:1.1rem;"><i class="fas fa-sliders"></i></div>
            <h6 class="fw-bold mb-0">System Parameters</h6>
          </div>
          <p class="text-muted text-xs mb-3">Configure overdue fine rates, maximum borrowing days, hold expiration periods, and currency symbols.</p>
          <div class="d-flex gap-2 mt-auto">
            <a href="<?= BASE_URL ?>/admin/settings/index.php" class="btn btn-sm btn-outline-warning btn-rounded flex-grow-1 text-dark">Open Settings</a>
            <a href="<?= BASE_URL ?>/admin/audit_logs/index.php" class="btn btn-sm btn-outline-secondary btn-rounded flex-grow-1">Audit Logs</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Audit Logs Table -->
    <div class="card shadow-sm border">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-clipboard-list text-primary me-2"></i> System Activity Audit Trail</span>
        <a href="<?= BASE_URL ?>/admin/audit_logs/index.php" class="text-xs text-primary text-decoration-none">Full Audit Log</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 text-sm">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>User / Agent</th>
                <th>Operation</th>
                <th>Details</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentLogs as $log): ?>
                <tr>
                  <td><?= format_date($log['created_at'], 'M d, Y h:i A') ?></td>
                  <td class="fw-semibold"><?= clean_output($log['username'] ?: 'System Guest') ?></td>
                  <td><span class="badge bg-light text-primary border"><?= clean_output($log['action']) ?></span></td>
                  <td class="text-xs"><?= clean_output($log['details']) ?></td>
                  <td><code><?= clean_output($log['ip_address']) ?></code></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
