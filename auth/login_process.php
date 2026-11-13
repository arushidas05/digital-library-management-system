<?php
/**
 * Authentication Processing Script
 */
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$loginInput = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($loginInput) || empty($password)) {
    set_flash('danger', 'Please provide both username/email and password.');
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Find user by username OR email
$user = db_fetch_one(
    "SELECT u.*, r.name as role_name 
     FROM users u 
     JOIN roles r ON u.role_id = r.id 
     WHERE u.username = ? OR u.email = ?",
    [$loginInput, $loginInput],
    "ss"
);

if (!$user) {
    log_audit('LOGIN_FAILED', "Unknown user attempt: {$loginInput}");
    set_flash('danger', 'Invalid credentials. Please verify username/email and password.');
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Check status
if ($user['status'] === 'suspended') {
    log_audit('LOGIN_BLOCKED', "Suspended account login attempt: {$loginInput}");
    set_flash('danger', 'Your account has been suspended by the administrator. Contact library desk.');
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    log_audit('LOGIN_FAILED', "Password mismatch for user: {$user['username']}");
    set_flash('danger', 'Invalid credentials. Please verify your password.');
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Regenerate session id for security
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role_id'] = $user['role_id'];
$_SESSION['role_name'] = $user['role_name'];

// Run overdue calculations routine
process_all_overdue_loans();

log_audit('LOGIN_SUCCESS', "User {$user['username']} logged in successfully as {$user['role_name']}");

set_flash('success', "Welcome back, " . htmlspecialchars($user['username']) . "!");

// Check for pending redirect
if (isset($_SESSION['redirect_after_login'])) {
    $redirect = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);
    header("Location: " . $redirect);
    exit;
}

// Redirect according to user role
switch ($user['role_name']) {
    case 'Admin':
        header("Location: " . BASE_URL . "/admin/dashboard.php");
        break;
    case 'Librarian':
        header("Location: " . BASE_URL . "/librarian/dashboard.php");
        break;
    case 'Student':
    default:
        header("Location: " . BASE_URL . "/student/dashboard.php");
        break;
}
exit;
