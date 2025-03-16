<?php
session_start();

// Auto-login as admin
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';
$_SESSION['last_activity'] = time();

// Redirect to index page
header("Location: index.php");
exit;
?> 