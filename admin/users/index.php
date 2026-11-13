<?php
/**
 * Admin: Users Management
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$pageTitle = 'User Management - OpenShelf';

// Handle Toggle Suspend/Activate Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($userId !== (int)$_SESSION['user_id']) { // Cannot suspend own account
        if ($act === 'suspend') {
            db_execute("UPDATE users SET status = 'suspended' WHERE id = ?", [$userId], "i");
            log_audit('USER_SUSPENDED', "Admin suspended user #{$userId}");
            set_flash('warning', 'User account suspended.');
        } elseif ($act === 'activate') {
            db_execute("UPDATE users SET status = 'active' WHERE id = ?", [$userId], "i");
            log_audit('USER_ACTIVATED', "Admin activated user #{$userId}");
            set_flash('success', 'User account activated.');
        }
        header("Location: " . BASE_URL . "/admin/users/index.php");
        exit;
    }
}

$roleFilter = (int)($_GET['role'] ?? 0);
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];
$types = "";

if ($roleFilter > 0) {
    $where[] = "u.role_id = ?";
    $params[] = $roleFilter;
    $types .= "i";
}

if (!empty($search)) {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR p.full_name LIKE ? OR p.student_id LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}

$whereSql = implode(" AND ", $where);

$users = db_fetch_all(
    "SELECT u.*, r.name as role_name, p.full_name, p.student_id, p.phone, p.avatar,
            (SELECT COUNT(*) FROM borrowings br WHERE br.user_id = u.id AND br.status IN ('Issued', 'Overdue')) as active_loans_count
     FROM users u
     JOIN roles r ON u.role_id = r.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE {$whereSql}
     ORDER BY u.id ASC",
    $params,
    $types
);

$roles = db_fetch_all("SELECT * FROM roles ORDER BY id ASC");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-users-gear text-primary me-2"></i> User & Member Management</h3>
        <p class="text-muted text-sm mb-0">Manage roles, membership statuses, librarians, and administrative credentials</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/admin/users/add.php" class="btn btn-primary btn-sm btn-rounded fw-semibold">
          <i class="fas fa-user-plus me-1"></i> Add New User
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <!-- Filter Card -->
    <div class="card p-3 mb-4 border bg-white shadow-sm">
      <form action="<?= BASE_URL ?>/admin/users/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
            <input type="text" class="form-control" name="search" value="<?= clean_output($search) ?>" placeholder="Search by name, username, email, or Student ID...">
          </div>
        </div>
        <div class="col-md-4">
          <select class="form-select form-select-sm" name="role">
            <option value="0">All System Roles</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= $roleFilter === (int)$r['id'] ? 'selected' : '' ?>>
                <?= clean_output($r['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
          <?php if (!empty($search) || $roleFilter > 0): ?>
            <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Users Table -->
    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 text-sm">
            <thead>
              <tr>
                <th>User / Name</th>
                <th>Role</th>
                <th>Email Address</th>
                <th>Student / Member ID</th>
                <th>Active Loans</th>
                <th>Account Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img src="<?= get_user_avatar($u['avatar'], $u['full_name'] ?: $u['username']) ?>" class="avatar-img" style="width: 32px; height: 32px;" alt="Avatar">
                      <div>
                        <div class="fw-bold text-dark"><?= clean_output($u['full_name'] ?: $u['username']) ?></div>
                        <div class="text-xs text-muted">@<?= clean_output($u['username']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($u['role_name'] === 'Admin'): ?>
                      <span class="badge bg-danger">Admin</span>
                    <?php elseif ($u['role_name'] === 'Librarian'): ?>
                      <span class="badge bg-success">Librarian</span>
                    <?php else: ?>
                      <span class="badge bg-primary">Student</span>
                    <?php endif; ?>
                  </td>
                  <td><?= clean_output($u['email']) ?></td>
                  <td><code><?= clean_output($u['student_id'] ?: '—') ?></code></td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= $u['active_loans_count'] ?> active</span>
                  </td>
                  <td>
                    <?php if ($u['status'] === 'active'): ?>
                      <span class="badge bg-success">Active</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Suspended</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group">
                      <a href="<?= BASE_URL ?>/admin/users/edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary table-action-btn" title="Edit User">
                        <i class="fas fa-pen"></i>
                      </a>

                      <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <?php if ($u['status'] === 'active'): ?>
                          <a href="<?= BASE_URL ?>/admin/users/index.php?action=suspend&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning table-action-btn" title="Suspend Account" onclick="return confirm('Suspend this user account?');">
                            <i class="fas fa-ban"></i>
                          </a>
                        <?php else: ?>
                          <a href="<?= BASE_URL ?>/admin/users/index.php?action=activate&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success table-action-btn" title="Reactivate Account">
                            <i class="fas fa-check"></i>
                          </a>
                        <?php endif; ?>

                        <a href="<?= BASE_URL ?>/admin/users/delete.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete User" onclick="return confirm('Permanently delete this user account?');">
                          <i class="fas fa-trash-can"></i>
                        </a>
                      <?php endif; ?>
                    </div>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
