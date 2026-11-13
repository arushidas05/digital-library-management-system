<?php
/**
 * Password Recovery Request
 */
require_once __DIR__ . '/../config.php';

$pageTitle = 'Forgot Password - OpenShelf Library';

$tokenGenerated = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!empty($email)) {
        $user = db_fetch_one("SELECT id, username FROM users WHERE email = ?", [$email], "s");
        if ($user) {
            $token = bin2hex(random_bytes(20));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            db_execute("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?", [$token, $expires, $user['id']], "ssi");
            $tokenGenerated = $token;
            set_flash('success', 'Password reset instructions have been generated! (See demo reset link below).');
        } else {
            set_flash('danger', 'No account found with that email address.');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon">
        <i class="fas fa-key"></i>
      </div>
      <h4 class="fw-bold mb-1">Reset Password</h4>
      <p class="text-muted text-sm mb-0">Enter your registered email address to recover access</p>
    </div>

    <div class="auth-body">
      <?php render_flash(); ?>

      <?php if ($tokenGenerated): ?>
        <div class="alert alert-info border shadow-sm">
          <h6 class="fw-bold mb-1"><i class="fas fa-link me-1"></i> Demo Reset Link Generated:</h6>
          <p class="text-xs mb-2">In production an email would be dispatched. For this project demo, use the direct link below:</p>
          <a href="<?= BASE_URL ?>/auth/reset_password.php?token=<?= $tokenGenerated ?>" class="btn btn-sm btn-primary w-100">
            Proceed to Reset Password <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      <?php endif; ?>

      <form action="<?= BASE_URL ?>/auth/forgot_password.php" method="POST">
        <div class="mb-4">
          <label for="email" class="form-label fw-semibold text-sm">Account Email</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
            <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="student@library.com" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
          <i class="fas fa-paper-plane me-2"></i> Send Reset Link
        </button>
      </form>

      <div class="text-center mt-4 text-sm text-muted">
        Remembered your password? <a href="<?= BASE_URL ?>/login.php" class="text-primary fw-semibold text-decoration-none">Back to Login</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
