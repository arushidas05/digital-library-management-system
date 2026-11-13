<?php
/**
 * Password Reset Confirmation
 */
require_once __DIR__ . '/../config.php';

$pageTitle = 'Set New Password - OpenShelf Library';
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));

$validUser = null;
if (!empty($token)) {
    $validUser = db_fetch_one("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW()", [$token], "s");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validUser) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        set_flash('danger', 'Password must be at least 6 characters long.');
    } elseif ($password !== $confirm) {
        set_flash('danger', 'Passwords do not match.');
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        db_execute("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?", [$hash, $validUser['id']], "si");
        log_audit('PASSWORD_RESET', "User {$validUser['username']} updated password via reset token");
        set_flash('success', 'Password reset successfully! Please sign in with your new password.');
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon">
        <i class="fas fa-shield-alt"></i>
      </div>
      <h4 class="fw-bold mb-1">Set New Password</h4>
      <p class="text-muted text-sm mb-0">Enter a secure new password for your account</p>
    </div>

    <div class="auth-body">
      <?php render_flash(); ?>

      <?php if (!$validUser): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle me-2"></i> This reset link is invalid or has expired.
        </div>
        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>/auth/forgot_password.php" class="btn btn-outline-primary btn-sm">Request New Link</a>
        </div>
      <?php else: ?>
        <form action="<?= BASE_URL ?>/auth/reset_password.php" method="POST">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

          <div class="mb-3">
            <label for="password" class="form-label fw-semibold text-sm">New Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" required>
          </div>

          <div class="mb-4">
            <label for="confirm_password" class="form-label fw-semibold text-sm">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="fas fa-check me-2"></i> Save New Password
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
