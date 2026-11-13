<?php
/**
 * Member Profile Management
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_role(['Student', 'Librarian', 'Admin']);

$pageTitle = 'My Profile - OpenShelf';
$user = current_user();
$userId = $user['id'];

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $studentId = trim($_POST['student_id'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if (empty($fullName)) {
            set_flash('danger', 'Full name is required.');
        } else {
            // Update profile
            $profExists = db_fetch_one("SELECT id FROM user_profiles WHERE user_id = ?", [$userId], "i");
            if ($profExists) {
                db_execute(
                    "UPDATE user_profiles SET full_name = ?, student_id = ?, phone = ?, address = ?, bio = ? WHERE user_id = ?",
                    [$fullName, $studentId, $phone, $address, $bio, $userId],
                    "sssssi"
                );
            } else {
                db_execute(
                    "INSERT INTO user_profiles (user_id, full_name, student_id, phone, address, bio) VALUES (?, ?, ?, ?, ?, ?)",
                    [$userId, $fullName, $studentId, $phone, $address, $bio],
                    "isssss"
                );
            }
            log_audit('PROFILE_UPDATED', "User {$user['username']} updated profile");
            set_flash('success', 'Profile details updated successfully!');
            header("Location: " . BASE_URL . "/student/profile.php");
            exit;
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $dbUser = db_fetch_one("SELECT password FROM users WHERE id = ?", [$userId], "i");

        if (!password_verify($currentPassword, $dbUser['password'])) {
            set_flash('danger', 'Current password entered is incorrect.');
        } elseif (strlen($newPassword) < 6) {
            set_flash('danger', 'New password must be at least 6 characters.');
        } elseif ($newPassword !== $confirmPassword) {
            set_flash('danger', 'New passwords do not match.');
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            db_execute("UPDATE users SET password = ? WHERE id = ?", [$newHash, $userId], "si");
            log_audit('PASSWORD_CHANGED', "User {$user['username']} changed password");
            set_flash('success', 'Password updated successfully!');
            header("Location: " . BASE_URL . "/student/profile.php");
            exit;
        }
    }
}

// Re-fetch updated user profile
$profile = db_fetch_one(
    "SELECT u.*, r.name as role_name, p.full_name, p.student_id, p.phone, p.address, p.avatar, p.bio
     FROM users u
     JOIN roles r ON u.role_id = r.id
     LEFT JOIN user_profiles p ON u.id = p.user_id
     WHERE u.id = ?",
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
        <h3 class="fw-bold mb-1"><i class="fas fa-id-badge text-primary me-2"></i> Account Profile</h3>
        <p class="text-muted text-sm mb-0">Manage your member credentials, personal information, and password</p>
      </div>
    </div>

    <?php render_flash(); ?>

    <div class="row g-4">
      <!-- Profile Card Left -->
      <div class="col-lg-4">
        <div class="card p-4 text-center border shadow-sm">
          <img src="<?= get_user_avatar($profile['avatar'], $profile['full_name'] ?: $profile['username']) ?>" class="rounded-circle mx-auto mb-3 shadow" style="width: 110px; height: 110px; object-fit: cover;" alt="Avatar">
          <h5 class="fw-bold mb-1"><?= clean_output($profile['full_name'] ?: $profile['username']) ?></h5>
          <div class="text-muted text-sm mb-2">@<?= clean_output($profile['username']) ?></div>
          <div>
            <span class="badge bg-primary px-3 py-2 fs-6 mb-3"><?= clean_output($profile['role_name']) ?></span>
          </div>

          <div class="p-3 bg-light rounded-3 text-start text-xs text-muted mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span>Member ID:</span>
              <strong class="text-dark"><?= clean_output($profile['student_id'] ?: 'Not Assigned') ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span>Email:</span>
              <strong class="text-dark"><?= clean_output($profile['email']) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span>Account Status:</span>
              <span class="badge bg-success"><?= strtoupper(clean_output($profile['status'])) ?></span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Member Since:</span>
              <span class="text-dark"><?= format_date($profile['created_at']) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Forms Right -->
      <div class="col-lg-8">
        <!-- Personal Details -->
        <div class="card p-4 border shadow-sm mb-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-user-edit text-primary me-2"></i> Update Personal Details</h5>
          <form action="<?= BASE_URL ?>/student/profile.php" method="POST">
            <input type="hidden" name="action" value="update_profile">

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="full_name" class="form-label text-sm fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= clean_output($profile['full_name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label for="student_id" class="form-label text-sm fw-semibold">Student ID / Roll No</label>
                <input type="text" class="form-control" id="student_id" name="student_id" value="<?= clean_output($profile['student_id']) ?>">
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="phone" class="form-label text-sm fw-semibold">Phone Number</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?= clean_output($profile['phone']) ?>">
              </div>
              <div class="col-md-6">
                <label for="email" class="form-label text-sm fw-semibold">Email Address (Read Only)</label>
                <input type="email" class="form-control bg-light" id="email" value="<?= clean_output($profile['email']) ?>" readonly>
              </div>
            </div>

            <div class="mb-3">
              <label for="address" class="form-label text-sm fw-semibold">Campus Address / Residence</label>
              <textarea class="form-control" id="address" name="address" rows="2"><?= clean_output($profile['address']) ?></textarea>
            </div>

            <div class="mb-3">
              <label for="bio" class="form-label text-sm fw-semibold">About / Reading Bio</label>
              <textarea class="form-control" id="bio" name="bio" rows="2" placeholder="Tell us about your favorite subjects, authors, and interests..."><?= clean_output($profile['bio']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-rounded px-4">
              <i class="fas fa-save me-1"></i> Save Changes
            </button>
          </form>
        </div>

        <!-- Change Password -->
        <div class="card p-4 border shadow-sm">
          <h5 class="fw-bold mb-3"><i class="fas fa-key text-primary me-2"></i> Change Account Password</h5>
          <form action="<?= BASE_URL ?>/student/profile.php" method="POST">
            <input type="hidden" name="action" value="change_password">

            <div class="mb-3">
              <label for="current_password" class="form-label text-sm fw-semibold">Current Password</label>
              <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="new_password" class="form-label text-sm fw-semibold">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="At least 6 characters" required>
              </div>
              <div class="col-md-6">
                <label for="confirm_password" class="form-label text-sm fw-semibold">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              </div>
            </div>

            <button type="submit" class="btn btn-outline-primary btn-rounded px-4">
              <i class="fas fa-lock me-1"></i> Update Password
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
