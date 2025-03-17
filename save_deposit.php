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
        $total_checks = str_replace(',', '', $_POST['total_checks']);
        $total_cash = str_replace(',', '', $_POST['total_cash']);
        
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
            
            error_log("Updated existing deposit: " . $deposit_no);
        } else {
            // Insert new deposit
            $stmt = $pdo->prepare("INSERT INTO deposits (deposit_no, invoice_no, date, bank, bank_acc_no, 
                                  check_no, remarks, total_checks, total_cash) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $deposit_no, $invoice_no, $date, $bank, $bank_acc_no, 
                $check_no, $remarks, $total_checks, $total_cash
            ]);
            
            error_log("Inserted new deposit: " . $deposit_no);
        }
        
        // Update invoice status to "Deposited"
        $stmt = $pdo->prepare("UPDATE invoice SET status = 'Deposited' WHERE invoice_no = ?");
        $stmt->execute([$invoice_no]);
        
        // Also update request_for_billing if it exists
        $stmt = $pdo->prepare("UPDATE request_for_billing SET status = 'Deposited' WHERE invoice_no = ?");
        $stmt->execute([$invoice_no]);
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect back to list page with success message
        header("Location: list.php?success=deposit_saved");
        exit;
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Database Error: " . $e->getMessage());
        header("Location: deposit.php?error=" . urlencode("Database Error: " . $e->getMessage()));
        exit;
    } catch (Exception $e) {
        // Handle other exceptions
        error_log("General Error: " . $e->getMessage());
        header("Location: deposit.php?error=" . urlencode("Error: " . $e->getMessage()));
        exit;
    }
} else {
    // Invalid request method
    header("Location: deposit.php?error=invalid_request");
    exit;
}
?>