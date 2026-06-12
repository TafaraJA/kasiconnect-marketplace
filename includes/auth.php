<?php
// Session management and role-based access control functions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Get current user's role
function userRole() {
    return $_SESSION['role'] ?? null;
}

function requireRole($allowed_roles) {
    requireLogin();
    if (!in_array(userRole(), (array)$allowed_roles)) {
        http_response_code(403);
        die("Access denied. You don't have permission to view this page.");
    }
}
?>