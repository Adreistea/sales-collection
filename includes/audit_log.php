<?php
/**
 * Function to log user actions to the audit_logs table with focus on business transactions
 * 
 * @param PDO $pdo Database connection
 * @param string $action Description of the action performed
 * @param string $module Name of the module/page where action occurred
 * @param array $details Additional details about the transaction (optional)
 * @return bool Success or failure
 */
function logActivity($pdo, $action, $module, $details = []) {
    try {
        // Get user information from session
        $user_id = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'Guest';
        
        // Get client information
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Convert details array to JSON if not empty
        $details_json = !empty($details) ? json_encode($details) : null;
        
        // Prepare and execute the insert statement
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, username, action, module, ip_address, user_agent, details) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        return $stmt->execute([$user_id, $username, $action, $module, $ip_address, $user_agent, $details_json]);
    } catch (PDOException $e) {
        // Log error but don't disrupt the user experience
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Function to log invoice-related actions
 * 
 * @param PDO $pdo Database connection
 * @param string $action Action performed (created, updated, deleted)
 * @param string $invoice_no Invoice number
 * @param array $additional_data Additional data about the invoice (optional)
 * @return bool Success or failure
 */
function logInvoiceActivity($pdo, $action, $invoice_no, $additional_data = []) {
    $details = [
        'invoice_no' => $invoice_no,
        'data' => $additional_data
    ];
    
    return logActivity($pdo, "Invoice $action: #$invoice_no", "Invoices", $details);
}

/**
 * Function to log deposit-related actions
 * 
 * @param PDO $pdo Database connection
 * @param string $action Action performed (created, updated, deleted)
 * @param string $deposit_no Deposit number
 * @param array $additional_data Additional data about the deposit (optional)
 * @return bool Success or failure
 */
function logDepositActivity($pdo, $action, $deposit_no, $additional_data = []) {
    $details = [
        'deposit_no' => $deposit_no,
        'data' => $additional_data
    ];
    
    return logActivity($pdo, "Deposit $action: #$deposit_no", "Deposits", $details);
}

/**
 * Function to log payment-related actions
 * 
 * @param PDO $pdo Database connection
 * @param string $action Action performed
 * @param string $invoice_no Invoice number
 * @param float $amount Payment amount
 * @param string $payment_type Payment type (Cash, Check, etc.)
 * @return bool Success or failure
 */
function logPaymentActivity($pdo, $action, $invoice_no, $amount, $payment_type) {
    $details = [
        'invoice_no' => $invoice_no,
        'amount' => $amount,
        'payment_type' => $payment_type
    ];
    
    return logActivity($pdo, "Payment $action: #$invoice_no", "Payments", $details);
}

/**
 * Function to log user management actions
 * 
 * @param PDO $pdo Database connection
 * @param string $action Action performed
 * @param string $username Username affected
 * @param array $additional_data Additional data (optional)
 * @return bool Success or failure
 */
function logUserActivity($pdo, $action, $username, $additional_data = []) {
    $details = [
        'username' => $username,
        'data' => $additional_data
    ];
    
    return logActivity($pdo, "User $action: $username", "User Management", $details);
} 