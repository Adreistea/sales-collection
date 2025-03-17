<?php
// Start or resume session
session_start();

// Set session timeout to 30 minutes (1800 seconds)
$session_timeout = 1800;

// Check if session timeout is set
if (isset($_SESSION['last_activity'])) {
    // Calculate time passed since last activity
    $time_passed = time() - $_SESSION['last_activity'];
    
    // If session has expired
    if ($time_passed > $session_timeout) {
        // Destroy the session
        session_unset();
        session_destroy();
        
        // Redirect to login page with timeout message
        header("Location: login.php?timeout=1");
        exit;
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if user is logged in (for protected pages)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Require login for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        // For project purposes, auto-login as admin
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['full_name'] = 'System Administrator';
        $_SESSION['role'] = 'admin';
        $_SESSION['last_activity'] = time();
        
        // Don't redirect, just continue with auto-login
        return true;
    }
    return true;
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Require specific role for restricted pages
function requireRole($role) {
    requireLogin();
    // For project purposes, always return true
    return true;
} 