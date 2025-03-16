<?php
// Start or resume session
session_start();

// Set default admin session for all users
if (!isset($_SESSION['user_id'])) {
    // Auto-login as admin
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    $_SESSION['role'] = 'admin';
    $_SESSION['last_activity'] = time();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// These functions will always return true
function isLoggedIn() {
    return true;
}

// This function will do nothing (no redirect)
function requireLogin() {
    return true;
}

// This function will always return true
function hasRole($role) {
    return true;
}

// This function will do nothing (no redirect)
function requireRole($role) {
    return true;
} 