<?php
/**
 * Authentication Check Guard
 * Ensures user is authenticated before accessing protected pages.
 */
require_once __DIR__ . '/../config.php';

if (!is_logged_in()) {
    $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
    if (!empty($currentUrl) && strpos($currentUrl, 'login.php') === false) {
        $_SESSION['redirect_after_login'] = $currentUrl;
    }
    set_flash('warning', 'Please sign in to access that page.');
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Check if user is suspended
$currentUser = current_user();
if ($currentUser && $currentUser['status'] === 'suspended') {
    session_destroy();
    session_start();
    set_flash('danger', 'Your account has been suspended by the administrator. Please contact library support.');
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
