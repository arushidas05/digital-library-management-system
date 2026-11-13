<?php
/**
 * Online Book Reading & Library Management System
 * Global Configuration File
 */

// Enable error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_db');
define('DB_PORT', 3306);

// Establish MySQLi database connection
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:30px;background:#fde8e8;color:#9b1c1c;border-radius:8px;max-width:600px;margin:50px auto;border:1px solid #f8b4b4;'>" .
        "<h3 style='margin-top:0;'>Database Connection Error</h3>" .
        "<p>Could not connect to MySQL server. Please ensure XAMPP MySQL is running.</p>" .
        "<code>" . htmlspecialchars($conn->connect_error) . "</code>" .
        "</div>");
}

$conn->set_charset("utf8mb4");

// Dynamic Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Determine project subfolder if hosted in subdirectory (e.g. /online-library-system or /PHP Project)
$scriptDir = str_replace('\\', '/', dirname($scriptName));
// Find position of known project root folders or default
$basePath = '';
if (strpos($scriptDir, '/online-library-system') !== false) {
    $basePath = '/online-library-system';
} elseif (strpos($scriptDir, '/student') !== false || strpos($scriptDir, '/librarian') !== false || strpos($scriptDir, '/admin') !== false || strpos($scriptDir, '/reader') !== false || strpos($scriptDir, '/auth') !== false) {
    // Parent folder is the root
    $parts = explode('/', trim($scriptDir, '/'));
    if (count($parts) > 1) {
        $basePath = '/' . $parts[0];
    } else {
        $basePath = '';
    }
} else {
    $basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
}

define('BASE_URL', rtrim($protocol . $host . $basePath, '/'));
define('ROOT_PATH', __DIR__);

// Load helper functions
require_once __DIR__ . '/includes/functions.php';
