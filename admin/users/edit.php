<?php
/**
 * Admin: Edit User Record
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_role('Admin');

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    header("Location: " . BASE_URL . "/admin/users/index.php");
    exit;
}

$user = db_fetch_one(
    "SELECT u.*, r.name as role_name, p.full_name, p.student_id, p.phone, p.address, p.bio
     FROM users u
     JOIN roles r ON u.role_id = r.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE u.id = ?",
    [$userId],
    "i"
);

if (!$user) {
    set_flash('danger', 'User not found.');
    header("Location: " . BASE_URL . "/admin/users/index.php");
    exit;
}

$pageTitle = 'Edit User: ' . clean_output($user['username']) . ' - OpenShelf';
$roles = db_fetch_all("SELECT * FROM roles ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleId = (int)($_POST['role_id'] ?? $user['role_id']);
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($fullName) || empty($email)) {
        set_flash('danger', 'Full name and email are required.');
    } else {
        // Update user
        db_execute(
            "UPDATE users SET role_id = ?, email = ?, status = ? WHERE id = ?",
            [$roleId, $email, $status, $userId],
            "issi"
        );

        // Update profile
        $profExists = db_fetch_one("SELECT id FROM user_profiles WHERE user_id = ?", [$userId], "i");
        if ($profExists) {
            db_execute(
                "UPDATE user_profiles SET full_name = ?, student_id = ?, phone = ? WHERE user_id = ?",
                [$fullName, $studentId, $phone, $userId],
                "sssi"
            );
        } else {
            db_execute(
                "INSERT INTO user_profiles (user_id, full_name, student_id, phone, avatar) VALUES (?, ?, ?, ?, 'default-avatar.png')",
                [$userId, $fullName, $studentId, $phone],
                "isss"
            );
        }

        // If password reset provided
        if (!empty($newPassword) && strlen($newPassword) >= 6) {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            db_execute("UPDATE users SET password = ? WHERE id = ?", [$hashed, $userId], "si");
        }

        log_audit('USER_UPDATED', "Admin updated user #{$userId} (@{$user['username']})");
        set_flash('success', "User '{$user['username']}' updated successfully!");
        header("Location: " . BASE_URL . "/admin/users/index.php");
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold mb-1"><i class="fas fa-user-pen text-primary me-2"></i> Edit User Account</h3>
        <p class="text-muted text-sm mb-0">Modify role authorizations, member details, and credentials</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn btn-outline-secondary btn-sm btn-rounded">
          <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="card p-4 border shadow-sm" style="max-width: 680px;">
      <form action="<?= BASE_URL ?>/admin/users/edit.php?id=<?= $userId ?>" method="POST">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="role_id" class="form-label text-sm fw-semibold">Role <span class="text-danger">*</span></label>
            <select class="form-select" id="role_id" name="role_id" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                  <?= clean_output($r['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label for="status" class="form-label text-sm fw-semibold">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="status" name="status" required>
              <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label for="full_name" class="form-label text-sm fw-semibold">Full Legal Name</label>
          <input type="text" class="form-control" id="full_name" name="full_name" value="<?= clean_output($user['full_name']) ?>" required>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="email" class="form-label text-sm fw-semibold">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= clean_output($user['email']) ?>" required>
          </div>
          <div class="col-md-6">
            <label for="student_id" class="form-label text-sm fw-semibold">Student / Staff ID</label>
            <input type="text" class="form-control" id="student_id" name="student_id" value="<?= clean_output($user['student_id']) ?>">
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="phone" class="form-label text-sm fw-semibold">Phone Number</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?= clean_output($user['phone']) ?>">
          </div>
          <div class="col-md-6">
            <label for="new_password" class="form-label text-sm fw-semibold">Reset Password (Leave blank to keep)</label>
            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="New password...">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-rounded px-4 py-2 fw-bold shadow-sm">
          <i class="fas fa-save me-1"></i> Save Changes
        </button>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
