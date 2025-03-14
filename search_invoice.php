<?php
require_once 'config/db_connect.php';

if(isset($_POST['invoice_no'])) {
    $invoice_no = $_POST['invoice_no'];
    
    // First check if it exists in invoice table
    $stmt = $pdo->prepare("SELECT i.*, c.customer_name, d.bank, d.bank_acc_no, d.check_no 
                          FROM invoice i 
                          LEFT JOIN customers c ON i.customer_id = c.customer_id 
                          LEFT JOIN deposits d ON i.invoice_no = d.invoice_no
                          WHERE i.invoice_no = ?");
    $stmt->execute([$invoice_no]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Debug output
        error_log("Found invoice: " . json_encode($result));
        
        // Return invoice data
        echo json_encode($result);
    } else {
        // If not found in invoice, check request_for_billing
        $stmt = $pdo->prepare("SELECT r.*, c.customer_name, r.project_name as project_title
                              FROM request_for_billing r 
                              LEFT JOIN customers c ON r.customer_id = c.customer_id 
                              WHERE r.invoice_no = ?");
        $stmt->execute([$invoice_no]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Found request_for_billing: " . json_encode($result));
        
        echo json_encode($result);
    }
} else {
    echo json_encode(['error' => 'No invoice number provided']);
}
?> 