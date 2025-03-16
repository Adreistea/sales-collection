<?php
require_once 'includes/session.php';
require_once 'config/db_connect.php';
require_once 'includes/audit_log.php';

// Require login
requireLogin();

// Log page access
logActivity($pdo, "Accessed invoice page", "Invoices");

// Rest of your invoice.php code

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get form data
        $deposit_no = $_POST['deposit_no'];
        $invoice_no = $_POST['invoice_no'];
        $date = $_POST['date'];
        $bank = $_POST['bank'] ?? null;
        $bank_acc_no = $_POST['bank_acc_no'] ?? null;
        $check_no = $_POST['check_no'] ?? null;
        $remarks = $_POST['remarks'] ?? null;
        $total_checks = str_replace(',', '', $_POST['total_checks'] ?? 0);
        $total_cash = str_replace(',', '', $_POST['total_cash'] ?? 0);
        
        // Prepare additional data for logging
        $log_data = [
            'invoice_no' => $invoice_no,
            'date' => $date,
            'total_cash' => $total_cash,
            'total_checks' => $total_checks
        ];
        
        if (!empty($bank)) $log_data['bank'] = $bank;
        if (!empty($bank_acc_no)) $log_data['bank_acc_no'] = $bank_acc_no;
        if (!empty($check_no)) $log_data['check_no'] = $check_no;
        if (!empty($remarks)) $log_data['remarks'] = $remarks;
        
        // Debug output
        error_log("Deposit data: " . json_encode([
            'deposit_no' => $deposit_no,
            'invoice_no' => $invoice_no,
            'date' => $date,
            'bank' => $bank,
            'bank_acc_no' => $bank_acc_no,
            'check_no' => $check_no,
            'remarks' => $remarks,
            'total_checks' => $total_checks,
            'total_cash' => $total_cash
        ]));
        
        // Check if deposit already exists
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE deposit_no = ?");
        $stmt->execute([$deposit_no]);
        $existing_deposit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_deposit) {
            // Update existing deposit
            $stmt = $pdo->prepare("UPDATE deposits SET 
                                  date = ?, bank = ?, bank_acc_no = ?, check_no = ?, 
                                  remarks = ?, total_checks = ?, total_cash = ? 
                                  WHERE deposit_no = ?");
            $stmt->execute([
                $date, $bank, $bank_acc_no, $check_no,
                $remarks, $total_checks, $total_cash, $deposit_no
            ]);
            
            // Log the update activity
            logDepositActivity($pdo, "updated", $deposit_no, $log_data);
            
            error_log("Updated existing deposit: " . $deposit_no);
        } else {
            // Insert new deposit
            $stmt = $pdo->prepare("INSERT INTO deposits (deposit_no, invoice_no, date, bank, bank_acc_no, check_no, remarks, total_checks, total_cash, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed')");
            $stmt->execute([
                $deposit_no, $invoice_no, $date, $bank, $bank_acc_no, $check_no, $remarks, $total_checks, $total_cash
            ]);
            
            // Log the create activity
            logDepositActivity($pdo, "created", $deposit_no, $log_data);
            
            error_log("Created new deposit: " . $deposit_no);
        }
        
        // Update invoice status to Deposited
        $stmt = $pdo->prepare("UPDATE invoice SET status = 'Deposited' WHERE invoice_no = ?");
        $stmt->execute([$invoice_no]);
        
        // Get payment type and amount from invoice
        $stmt = $pdo->prepare("SELECT payment_type, payments FROM invoice WHERE invoice_no = ?");
        $stmt->execute([$invoice_no]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($invoice) {
            // Log the payment activity
            $payment_type = $invoice['payment_type'] ?? ($total_checks > 0 ? 'Check' : 'Cash');
            $payment_amount = $invoice['payments'] ?? ($total_checks + $total_cash);
            
            logPaymentActivity($pdo, "deposited", $invoice_no, $payment_amount, $payment_type);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect back to deposit page with success message
        header("Location: deposit.php?success=1&id=" . urlencode($deposit_no));
        exit;
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        // Log the error
        error_log("Error saving deposit: " . $e->getMessage());
        
        // Redirect with error message
        header("Location: deposit.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Invalid request method
    header("Location: deposit.php?error=invalid_request");
    exit;
}
?>