<?php
require_once 'config/db_connect.php';

// Set header to JSON
header('Content-Type: application/json');

try {
    // Get the 10 most recent invoices
    $query = "SELECT 
        invoice_no, 
        created_at, 
        project_code, 
        amount, 
        status 
        FROM invoice 
        ORDER BY created_at DESC 
        LIMIT 10";
        
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'invoices' => $invoices
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Error: ' . $e->getMessage(),
        'invoices' => []
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'General Error: ' . $e->getMessage(),
        'invoices' => []
    ]);
}
?> 