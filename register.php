<?php
/**
 * Student Registration Page
 */
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit;
}

$pageTitle = 'Join OpenShelf Library - Student Registration';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card" style="max-width: 560px;">
    <div class="auth-header">
      <div class="auth-icon">
        <i class="fas fa-user-plus"></i>
      </div>
      <h4 class="fw-bold mb-1">Create Student Account</h4>
      <p class="text-muted text-sm mb-0">Register for digital reading and physical borrowing privileges</p>
    </div>

    <div class="auth-body">
      <?php render_flash(); ?>

      <form action="<?= BASE_URL ?>/auth/register_process.php" method="POST">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="full_name" class="form-label fw-semibold text-sm">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="John Doe" required>
          </div>
          <div class="col-md-6">
            <label for="student_id" class="form-label fw-semibold text-sm">Student ID / Roll No</label>
            <input type="text" class="form-control" id="student_id" name="student_id" placeholder="BCA-2026-101">
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="username" class="form-label fw-semibold text-sm">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="username" name="username" placeholder="johndoe" required>
          </div>
          <div class="col-md-6">
            <label for="phone" class="form-label fw-semibold text-sm">Phone Number</label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="+1 555-0199">
          </div>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label fw-semibold text-sm">Email Address <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="email" name="email" placeholder="john.doe@university.edu" required>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="password" class="form-label fw-semibold text-sm">Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" required>
          </div>
          <div class="col-md-6">
            <label for="confirm_password" class="form-label fw-semibold text-sm">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
          </div>
        </div>

        <div class="mb-4 form-check">
          <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
          <label class="form-check-label text-sm text-muted" for="terms">
            I agree to the Library Rules, return physical books on time, and respect digital reading terms.
          </label>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fs-6">
          <i class="fas fa-check-circle me-2"></i> Complete Registration
        </button>
      </form>

      <div class="text-center mt-4 text-sm text-muted">
        Already registered? <a href="<?= BASE_URL ?>/login.php" class="text-primary fw-semibold text-decoration-none">Sign In Here</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
