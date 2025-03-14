<?php
require_once 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get form data
        $invoice_no = $_POST['invoiceNo'] ?? '';
        $rfb_id = $_POST['rfb_id'] ?? null;
        $date = $_POST['date'] ?? date('Y-m-d');
        $project_code = $_POST['project_id'] ?? '';
        $project_title = $_POST['project_title'] ?? '';
        
        // Debug output
        error_log("Customer ID from form: " . ($_POST['customer_id'] ?? 'not set'));
        error_log("Customer display from form: " . ($_POST['customer_display'] ?? 'not set'));
        
        // Try to get customer_id from either field
        $customer_id = null;
        if (!empty($_POST['customer_id'])) {
            $customer_id = (int)$_POST['customer_id'];
        } else if (!empty($_POST['customer_display'])) {
            $customer_id = (int)$_POST['customer_display'];
        }
        
        // Make sure customer_id is valid
        if (empty($customer_id)) {
            throw new Exception("Customer ID is required");
        }
        
        $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : 1; // Default to 1 if not set
        $product_name = $_POST['product_name'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $internal_terms = $_POST['internal_terms'] ?? '';
        $customer_terms = $_POST['customer_terms'] ?? '';
        $payment_type = $_POST['payment_type'] ?? '';
        $amount = isset($_POST['amount']) ? (float)str_replace(',', '', $_POST['amount']) : 0;
        $billed = isset($_POST['billed']) ? (float)str_replace(',', '', $_POST['billed']) : 0;
        $gross = isset($_POST['gross']) ? (float)str_replace(',', '', $_POST['gross']) : 0;
        $returns = isset($_POST['returns']) ? (float)str_replace(',', '', $_POST['returns']) : 0;
        $payments = isset($_POST['payments']) ? (float)str_replace(',', '', $_POST['payments']) : 0;
        $debit = isset($_POST['debit']) ? (float)str_replace(',', '', $_POST['debit']) : 0;
        $credit = isset($_POST['credit']) ? (float)str_replace(',', '', $_POST['credit']) : 0;
        $balance = isset($_POST['balance']) ? (float)str_replace(',', '', $_POST['balance']) : 0;
        
        // Debug output
        error_log("Financial values:");
        error_log("Amount: $amount");
        error_log("Billed: $billed");
        error_log("Gross: $gross");
        error_log("Returns: $returns");
        error_log("Payments: $payments");
        error_log("Debit: $debit");
        error_log("Credit: $credit");
        error_log("Balance: $balance");
        
        // Check if customer is VAT registered and calculate VAT
        $vat = 0;
        if ($customer_id) {
            $stmt = $pdo->prepare("SELECT vat_registered FROM customers WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug output
            error_log("Customer VAT registered: " . ($customer['vat_registered'] ? 'Yes' : 'No'));
            
            if ($customer && $customer['vat_registered']) {
                // Calculate VAT (12% of the VAT base)
                $vatBase = $gross - $returns + $debit - $credit;
                $vat = $vatBase * 0.12;
                
                // Debug output
                error_log("VAT calculation:");
                error_log("VAT Base: $vatBase = $gross - $returns + $debit - $credit");
                error_log("VAT (12%): $vat");
            } else {
                error_log("Customer is not VAT registered, no VAT applied");
            }
        }
        
        // Determine status based on payment type
        $status = "Pending";
        if ($payment_type === "Cash") {
            $status = "Deposited";
        } else if ($payment_type === "Bank Transfer" || $payment_type === "Check") {
            $status = "Paid";
        }
        
        // Alternative approach with named parameters
        $sql = "INSERT INTO invoice (
                    invoice_no, rfb_id, date, project_code, project_title, 
                    customer_id, manager_id, product_name, remarks, internal_terms, 
                    customer_terms, payment_type, amount, billed, gross, 
                    vat, returns, payments, debit, credit, 
                    balance, status, created_at
                ) VALUES (
                    :invoice_no, :rfb_id, :date, :project_code, :project_title, 
                    :customer_id, :manager_id, :product_name, :remarks, :internal_terms, 
                    :customer_terms, :payment_type, :amount, :billed, :gross, 
                    :vat, :returns, :payments, :debit, :credit, 
                    :balance, :status, NOW()
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':invoice_no', $invoice_no);
        $stmt->bindParam(':rfb_id', $rfb_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':project_code', $project_code);
        $stmt->bindParam(':project_title', $project_title);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':manager_id', $manager_id);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':internal_terms', $internal_terms);
        $stmt->bindParam(':customer_terms', $customer_terms);
        $stmt->bindParam(':payment_type', $payment_type);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':billed', $billed);
        $stmt->bindParam(':gross', $gross);
        $stmt->bindParam(':vat', $vat);
        $stmt->bindParam(':returns', $returns);
        $stmt->bindParam(':payments', $payments);
        $stmt->bindParam(':debit', $debit);
        $stmt->bindParam(':credit', $credit);
        $stmt->bindParam(':balance', $balance);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        // If payment type is Cash or Check, insert into deposits table
        if ($payment_type === "Cash" || $payment_type === "Check" || $payment_type === "Bank Transfer") {
            $bank = $_POST['bank'] ?? null;
            $bank_acc_no = $_POST['bank_acc_no'] ?? null;
            $check_no = $_POST['check_no'] ?? null;
            $total_cash = ($payment_type === "Cash") ? $payments : 0;
            $total_checks = ($payment_type === "Check") ? $payments : 0;
            
            $stmt = $pdo->prepare("INSERT INTO deposits (deposit_no, invoice_no, date, bank, bank_acc_no, 
                                  check_no, remarks, total_checks, total_cash) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $deposit_no = 'DEP-' . date('YmdHis');
            $stmt->execute([
                $deposit_no, $invoice_no, $date, $bank, $bank_acc_no, $check_no, $remarks, 
                $total_checks, $total_cash
            ]);
        }
        
        // Update request_for_billing status if it exists
        if (!empty($invoice_no)) {
            $stmt = $pdo->prepare("UPDATE request_for_billing SET status = ? WHERE invoice_no = ?");
            $stmt->execute([$status, $invoice_no]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Invoice saved successfully', 'status' => $status]);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        // Handle other exceptions
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 