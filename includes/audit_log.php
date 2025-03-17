<?php
/**
 * Function to log user actions to the audit_logs table
 * 
 * @param PDO $pdo Database connection
 * @param string $action Description of the action performed
 * @param string $module Name of the module/page where action occurred
 * @return bool Success or failure
 */
function logActivity($pdo, $action, $module) {
    try {
        // Get user information from session
        $user_id = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'Guest';
        
        // Get client information
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Prepare and execute the insert statement
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, username, action, module, ip_address, user_agent) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        
        return $stmt->execute([$user_id, $username, $action, $module, $ip_address, $user_agent]);
    } catch (PDOException $e) {
        // Log error but don't disrupt the user experience
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
} 