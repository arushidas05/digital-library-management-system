<?php
/**
 * User Login Page
 */
require_once __DIR__ . '/config.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    $role = user_role();
    if ($role === 'Admin') header("Location: " . BASE_URL . "/admin/dashboard.php");
    elseif ($role === 'Librarian') header("Location: " . BASE_URL . "/librarian/dashboard.php");
    else header("Location: " . BASE_URL . "/student/dashboard.php");
    exit;
}

$pageTitle = 'Sign In - OpenShelf Library';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon">
        <i class="fas fa-lock"></i>
      </div>
      <h4 class="fw-bold mb-1">Library Sign In</h4>
      <p class="text-muted text-sm mb-0">Sign in to your member, librarian, or admin account</p>
    </div>

    <div class="auth-body">
      <?php render_flash(); ?>

      <form action="<?= BASE_URL ?>/auth/login_process.php" method="POST">
        <div class="mb-3">
          <label for="username" class="form-label fw-semibold text-sm">Username or Email Address</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
            <input type="text" class="form-control border-start-0" id="username" name="username" placeholder="e.g. student or student@library.com" required autofocus>
          </div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <label for="password" class="form-label fw-semibold text-sm">Password</label>
            <a href="<?= BASE_URL ?>/auth/forgot_password.php" class="text-xs text-primary text-decoration-none">Forgot password?</a>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
            <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="••••••••" required>
            <button class="btn btn-outline-secondary border-start-0" type="button" onclick="togglePasswordVisibility('password', 'pwdToggleIcon')">
              <i class="far fa-eye" id="pwdToggleIcon"></i>
            </button>
          </div>
        </div>

        <div class="mb-4 form-check">
          <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
          <label class="form-check-label text-sm text-muted" for="remember">Keep me signed in on this computer</label>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fs-6">
          <i class="fas fa-right-to-bracket me-2"></i> Access Account
        </button>
      </form>

      <!-- Instant Demo Logins Box -->
      <div class="demo-account-box text-center">
        <div class="fw-bold text-xs text-uppercase text-muted mb-2" style="letter-spacing: 0.05em;">
          <i class="fas fa-bolt text-warning me-1"></i> Quick Demo Autofill
        </div>
        <div class="d-flex justify-content-center flex-wrap gap-1">
          <button type="button" class="btn btn-sm btn-outline-dark demo-btn" onclick="fillDemoLogin('student')">
            <i class="fas fa-user-graduate me-1 text-primary"></i> Student
          </button>
          <button type="button" class="btn btn-sm btn-outline-dark demo-btn" onclick="fillDemoLogin('librarian')">
            <i class="fas fa-book-bookmark me-1 text-success"></i> Librarian
          </button>
          <button type="button" class="btn btn-sm btn-outline-dark demo-btn" onclick="fillDemoLogin('admin')">
            <i class="fas fa-shield-halved me-1 text-danger"></i> Admin
          </button>
        </div>
        <small class="text-muted text-xs d-block mt-2">Password for all test accounts is <code>password123</code></small>
      </div>

      <div class="text-center mt-4 text-sm text-muted">
        Don't have an account yet? <a href="<?= BASE_URL ?>/register.php" class="text-primary fw-semibold text-decoration-none">Register as Student</a>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
