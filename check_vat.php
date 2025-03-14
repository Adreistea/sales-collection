<?php
require_once 'config/db_connect.php';

if (isset($_GET['customer_id'])) {
    $customer_id = (int)$_GET['customer_id'];
    
    $stmt = $pdo->prepare("SELECT vat_registered FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'vat_registered' => $customer && $customer['vat_registered'] ? true : false
    ]);
} else {
    echo json_encode(['vat_registered' => false]);
}
?> 