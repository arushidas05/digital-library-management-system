<?php
/**
 * Registration Processing Script
 */
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/register.php");
    exit;
}

$fullName = trim($_POST['full_name'] ?? '');
$studentId = trim($_POST['student_id'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validation
if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
    set_flash('danger', 'Please fill in all mandatory fields.');
    header("Location: " . BASE_URL . "/register.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Invalid email address provided.');
    header("Location: " . BASE_URL . "/register.php");
    exit;
}

if (strlen($password) < 6) {
    set_flash('danger', 'Password must be at least 6 characters long.');
    header("Location: " . BASE_URL . "/register.php");
    exit;
}

if ($password !== $confirmPassword) {
    set_flash('danger', 'Passwords do not match.');
    header("Location: " . BASE_URL . "/register.php");
    exit;
}

// Check existing username or email
$existing = db_fetch_one("SELECT id, username, email FROM users WHERE username = ? OR email = ?", [$username, $email], "ss");
if ($existing) {
    if (strtolower($existing['username']) === strtolower($username)) {
        set_flash('danger', 'This username is already taken. Please pick another.');
    } else {
        set_flash('danger', 'An account with this email address already exists.');
    }
    header("Location: " . BASE_URL . "/register.php");
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert user (role_id = 3 for Student)
$insertUser = db_execute(
    "INSERT INTO users (role_id, username, email, password, status) VALUES (3, ?, ?, ?, 'active')",
    [$username, $email, $hashedPassword],
    "sss"
);

if ($insertUser) {
    $newUserId = db_last_id();
    
    // Insert profile
    db_execute(
        "INSERT INTO user_profiles (user_id, full_name, student_id, phone, avatar) VALUES (?, ?, ?, ?, 'default-avatar.png')",
        [$newUserId, $fullName, $studentId, $phone],
        "isss"
    );

    // Send Welcome in-app notification
    send_notification(
        $newUserId,
        'Welcome to OpenShelf Library!',
        'Your student membership is active. You can search the catalogue, read digital books online, and borrow physical copies.',
        'general',
        'catalogue.php'
    );

    log_audit('USER_REGISTERED', "New student registered: {$username} ({$email})");

    set_flash('success', 'Registration successful! You can now log in with your credentials.');
    header("Location: " . BASE_URL . "/login.php");
    exit;
} else {
    set_flash('danger', 'Failed to register account. Please try again.');
    header("Location: " . BASE_URL . "/register.php");
    exit;
}
