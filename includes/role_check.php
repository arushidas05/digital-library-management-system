<?php
/**
 * Role-Based Access Control Guard
 */
require_once __DIR__ . '/auth_check.php';

function require_role($allowedRoles) {
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    $currentRole = user_role();
    if (!in_array($currentRole, $allowedRoles, true)) {
        set_flash('danger', "Access denied. You do not have permission to view that section ({$currentRole}).");
        
        // Redirect to appropriate dashboard based on their role
        switch ($currentRole) {
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
    }
}
