<?php
/**
 * Admin: Add New User
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$pageTitle = 'Add User - OpenShelf';
$roles = db_fetch_all("SELECT * FROM roles ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleId = (int)($_POST['role_id'] ?? 3);
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $studentId = trim($_POST['student_id'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        set_flash('danger', 'Full name, username, email, and password are required.');
    } else {
        $existing = db_fetch_one("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email], "ss");
        if ($existing) {
            set_flash('danger', 'Username or email already in use.');
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $inserted = db_execute(
                "INSERT INTO users (role_id, username, email, password, status) VALUES (?, ?, ?, ?, 'active')",
                [$roleId, $username, $email, $hashed],
                "isss"
            );

            if ($inserted) {
                $newId = db_last_id();
                db_execute(
                    "INSERT INTO user_profiles (user_id, full_name, student_id, phone, avatar) VALUES (?, ?, ?, ?, 'default-avatar.png')",
                    [$newId, $fullName, $studentId, $phone],
                    "isss"
                );

                log_audit('USER_CREATED', "Admin created user #{$newId}: {$username} (Role #{$roleId})");
                set_flash('success', "User '{$username}' created successfully!");
                header("Location: " . BASE_URL . "/admin/users/index.php");
                exit;
            } else {
                set_flash('danger', 'Failed to create user account.');
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-user-plus text-primary me-2"></i> Create New User Account</h3>
        <p class="text-muted text-sm mb-0">Register staff administrators, librarians, or student members</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 680px;">
      <form action="<?= BASE_URL ?>/admin/users/add.php" method="POST">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="role_id" class="form-label text-sm fw-semibold">Account Role <span class="text-danger">*</span></label>
            <select class="form-select" id="role_id" name="role_id" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $r['name'] === 'Student' ? 'selected' : '' ?>>
                  <?= clean_output($r['name']) ?> &mdash; <?= clean_output($r['description']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label for="student_id" class="form-label text-sm fw-semibold">Student / Staff ID</label>
            <input type="text" class="form-control" id="student_id" name="student_id" placeholder="e.g. BCA-2026-105">
          </div>
        </div>

        <div class="mb-3">
          <label for="full_name" class="form-label text-sm fw-semibold">Full Legal Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g. Robert Smith" required>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="username" class="form-label text-sm fw-semibold">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="username" name="username" placeholder="robertsmith" required>
          </div>
          <div class="col-md-6">
            <label for="email" class="form-label text-sm fw-semibold">Email Address <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="email" name="email" placeholder="robert@library.com" required>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="password" class="form-label text-sm fw-semibold">Initial Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
          </div>
          <div class="col-md-6">
            <label for="phone" class="form-label text-sm fw-semibold">Phone Number</label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="+1 555-0188">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-rounded px-4 py-2 fw-bold shadow-sm">
          <i class="fas fa-check-circle me-1"></i> Create User Account
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
