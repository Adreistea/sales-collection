<?php
require_once 'includes/session.php';
require_once 'config/db_connect.php';
require_once 'includes/audit_log.php';

// Require login
requireLogin();

// Log page access
logActivity($pdo, "Accessed deposit page", "Deposits");

// Generate a unique deposit number if not editing an existing one
$deposit_no = 'DEP-' . date('YmdHis');
$current_date = date('Y-m-d');
$status = 'Pending';
$payment_type = '';
$total_checks = 0;
$total_cash = 0;
$error_message = '';

// If editing an existing deposit slip
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE deposit_no = ?");
        $stmt->execute([$_GET['id']]);
        $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($deposit) {
            $deposit_no = $deposit['deposit_no'];
            $current_date = date('Y-m-d', strtotime($deposit['date']));
            $status = $deposit['status'] ?? 'Pending';
            // Other fields will be populated in the form
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching deposit: " . $e->getMessage();
    }
}

// If an invoice is selected
$selected_invoice = null;
if (isset($_GET['invoice_no'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT i.*, c.customer_name 
            FROM invoice i 
            LEFT JOIN customers c ON i.customer_id = c.customer_id 
            WHERE i.invoice_no = ? AND i.status = 'Paid'
        ");
        $stmt->execute([$_GET['invoice_no']]);
        $selected_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_invoice) {
            // Pre-fill form with invoice data
            $payment_type = $selected_invoice['payment_type'] ?? '';
            $total_checks = ($payment_type == 'Check') ? $selected_invoice['payments'] : 0;
            $total_cash = ($payment_type == 'Cash') ? $selected_invoice['payments'] : 0;
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching invoice: " . $e->getMessage();
    }
}

// Fetch deposit details if deposit_no exists
$deposit_details = [];
if (isset($deposit_no)) {
    try {
        $stmt = $pdo->prepare("
            SELECT d.*, i.payment_type, i.invoice_no
            FROM deposits d
            LEFT JOIN invoice i ON d.invoice_no = i.invoice_no
            WHERE d.deposit_no = ?
        ");
        $stmt->execute([$deposit_no]);
        $deposit_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching deposit details: " . $e->getMessage();
    }
}

// Fetch all invoices with "Paid" status
try {
    $stmt = $pdo->prepare("
        SELECT i.*, c.customer_name 
        FROM invoice i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id 
        WHERE i.status = 'Paid' 
        ORDER BY i.date DESC
    ");
    $stmt->execute();
    $paid_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching paid invoices: " . $e->getMessage();
    $paid_invoices = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Slip</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .form-control, .form-select {
            border-radius: 0;
        }
        .nav-tabs .nav-link {
            border-radius: 0;
            padding: 10px 20px;
        }
        .deposit-header {
            margin-bottom: 20px;
        }
        .deposit-section {
            margin-bottom: 30px;
        }
        .details-table th {
            background-color: #4da6ff;
            color: white;
        }
        .invoice-list-table tr {
            cursor: pointer;
        }
        .invoice-list-table tr:hover {
            background-color: #f8f9fa;
        }
        .selected-invoice {
            background-color: #e2f0ff !important;
        }
    </style>
</head>
<body>
    <!-- Include the navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="main.php" class="btn btn-primary">← Back to Main</a>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" href="invoice.php">Main</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="list.php">List</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="deposit.php">Deposit</a>
            </li>
        </ul>

        <div class="row">
            <!-- Paid Invoices List -->
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Paid Invoices</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($paid_invoices)): ?>
                            <div class="alert alert-info">No paid invoices available for deposit.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered invoice-list-table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paid_invoices as $invoice): ?>
                                            <tr class="<?php echo (isset($_GET['invoice_no']) && $_GET['invoice_no'] == $invoice['invoice_no']) ? 'selected-invoice' : ''; ?>" 
                                                onclick="window.location.href='deposit.php?invoice_no=<?php echo $invoice['invoice_no']; ?>'">
                                                <td><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($invoice['date'])); ?></td>
                                                <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                                <td class="text-end">PHP <?php echo number_format($invoice['payments'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $invoice['payment_type'] == 'Cash' ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo htmlspecialchars($invoice['payment_type']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Deposit Form -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Deposit Slip</h2>
                        
                        <?php if (isset($error_message) && !empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!isset($_GET['invoice_no']) && empty($deposit_details)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please select a paid invoice from the list to create a deposit slip.
                            </div>
                        <?php else: ?>
                            <form id="depositForm" method="post" action="save_deposit.php">
                                <!-- Deposit Header -->
                                <div class="row deposit-header">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="deposit_no" class="form-label">Deposit No:</label>
                                            <input type="text" class="form-control" id="deposit_no" name="deposit_no" value="<?php echo htmlspecialchars($deposit_no); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="date" class="form-label">Date:</label>
                                            <input type="text" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($current_date); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status:</label>
                                            <input type="text" class="form-control" id="status" name="status" value="<?php echo htmlspecialchars($status); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($_GET['invoice_no']) && $selected_invoice): ?>
                                    <input type="hidden" name="invoice_no" value="<?php echo htmlspecialchars($selected_invoice['invoice_no']); ?>">
                                    
                                    <div class="alert alert-success mb-3">
                                        <strong>Selected Invoice:</strong> <?php echo htmlspecialchars($selected_invoice['invoice_no']); ?> - 
                                        <?php echo htmlspecialchars($selected_invoice['customer_name']); ?> - 
                                        PHP <?php echo number_format($selected_invoice['payments'], 2); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Deposit Slip Information -->
                                <div class="deposit-section">
                                    <h5>Deposit Slip Information</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="bank" class="form-label">Bank:</label>
                                                <input type="text" class="form-control" id="bank" name="bank" 
                                                    value="<?php echo isset($deposit) ? htmlspecialchars($deposit['bank']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="bank_acc_no" class="form-label">Bank Acct No:</label>
                                                <input type="text" class="form-control" id="bank_acc_no" name="bank_acc_no" 
                                                    value="<?php echo isset($deposit) ? htmlspecialchars($deposit['bank_acc_no']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="check_no" class="form-label">Check Number:</label>
                                                <input type="text" class="form-control" id="check_no" name="check_no" 
                                                    value="<?php echo isset($deposit) ? htmlspecialchars($deposit['check_no']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="remarks" class="form-label">Remarks:</label>
                                                <input type="text" class="form-control" id="remarks" name="remarks" 
                                                    value="<?php echo isset($deposit) ? htmlspecialchars($deposit['remarks']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="total_checks" class="form-label">Total Checks:</label>
                                                <input type="text" class="form-control text-end" id="total_checks" name="total_checks" 
                                                    value="<?php echo isset($total_checks) ? number_format($total_checks, 2) : (isset($deposit) ? number_format($deposit['total_checks'], 2) : '0.00'); ?>" 
                                                    <?php echo isset($selected_invoice) && $selected_invoice['payment_type'] == 'Check' ? 'readonly' : ''; ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="total_cash" class="form-label">Total Cash:</label>
                                                <input type="text" class="form-control text-end" id="total_cash" name="total_cash" 
                                                    value="<?php echo isset($total_cash) ? number_format($total_cash, 2) : (isset($deposit) ? number_format($deposit['total_cash'], 2) : '0.00'); ?>" 
                                                    <?php echo isset($selected_invoice) && $selected_invoice['payment_type'] == 'Cash' ? 'readonly' : ''; ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="total_dwocr" class="form-label">Total DWOCR:</label>
                                                <input type="text" class="form-control text-end" id="total_dwocr" name="total_dwocr" value="0.00" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Details Section -->
                                <div class="deposit-section">
                                    <h5>Details</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered details-table">
                                            <thead>
                                                <tr>
                                                    <th>Payment Type</th>
                                                    <th>Check Number</th>
                                                    <th>Bank</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detailsTableBody">
                                                <?php if (isset($selected_invoice)): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($selected_invoice['payment_type'] ?? 'N/A'); ?></td>
                                                        <td id="check_no_display">To be entered</td>
                                                        <td id="bank_display">To be entered</td>
                                                        <td class="text-end">PHP <?php echo number_format($selected_invoice['payments'], 2); ?></td>
                                                    </tr>
                                                <?php elseif (!empty($deposit_details)): ?>
                                                    <?php foreach ($deposit_details as $detail): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($detail['payment_type'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($detail['check_no'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($detail['bank'] ?? 'N/A'); ?></td>
                                                            <td class="text-end">
                                                                <?php 
                                                                    $amount = 0;
                                                                    if (isset($detail['payment_type'])) {
                                                                        if ($detail['payment_type'] == 'Check') {
                                                                            $amount = $detail['total_checks'];
                                                                        } elseif ($detail['payment_type'] == 'Cash') {
                                                                            $amount = $detail['total_cash'];
                                                                        }
                                                                    }
                                                                    echo number_format($amount, 2);
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No details available</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="row mt-4">
                                    <div class="col-md-12 text-end">
                                        <button type="button" class="btn btn-secondary me-2" id="cancelBtn">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update the details table when bank or check number is entered
            const bankInput = document.getElementById('bank');
            const checkNoInput = document.getElementById('check_no');
            const bankDisplay = document.getElementById('bank_display');
            const checkNoDisplay = document.getElementById('check_no_display');
            
            if (bankInput && bankDisplay) {
                bankInput.addEventListener('input', function() {
                    bankDisplay.textContent = this.value || 'To be entered';
                });
            }
            
            if (checkNoInput && checkNoDisplay) {
                checkNoInput.addEventListener('input', function() {
                    checkNoDisplay.textContent = this.value || 'To be entered';
                });
            }
            
            // Cancel button action
            document.getElementById('cancelBtn')?.addEventListener('click', function() {
                window.location.href = 'main.php';
            });
            
            // Form submission handling
            document.getElementById('depositForm')?.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Basic validation
                const bank = document.getElementById('bank').value.trim();
                if (!bank) {
                    alert('Please enter a bank name');
                    return;
                }
                
                // Format numbers to remove commas before submission
                const totalChecks = document.getElementById('total_checks');
                const totalCash = document.getElementById('total_cash');
                
                if (totalChecks) {
                    totalChecks.value = totalChecks.value.replace(/,/g, '');
                }
                
                if (totalCash) {
                    totalCash.value = totalCash.value.replace(/,/g, '');
                }
                
                // Submit the form if validation passes
                this.submit();
            });
        });
    </script>
</body>
</html>