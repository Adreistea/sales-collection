<?php
require_once 'config/db_connect.php';

// Handle status update if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invoice_id'])) {
    try {
        $invoice_id = $_POST['invoice_id'];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update invoice status to "Deposited"
        $stmt = $pdo->prepare("UPDATE invoice SET status = 'Deposited' WHERE invoice_no = ?");
        $stmt->execute([$invoice_id]);
        
        // Also update request_for_billing if it exists
        $stmt = $pdo->prepare("UPDATE request_for_billing SET status = 'Deposited' WHERE invoice_no = ?");
        $stmt->execute([$invoice_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Set success message
        $success_message = "Invoice #$invoice_id has been marked as Deposited";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Fetch all invoices with "Paid" status
try {
    $stmt = $pdo->prepare("SELECT i.*, c.customer_name 
                          FROM invoice i 
                          LEFT JOIN customers c ON i.customer_id = c.customer_id 
                          WHERE i.status = 'Paid' 
                          ORDER BY i.date DESC");
    $stmt->execute();
    $paid_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching invoices: " . $e->getMessage();
    $paid_invoices = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testing - Paid Invoices</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Back Button -->
        <div class="back-button mb-3">
            <a href="main.php" class="btn btn-primary">← Back to Main</a>
        </div>

        <div class="card">
            <div class="card-header bg-danger text-white">
                <h3 class="mb-0"><i class="fas fa-vial me-2"></i>Testing - Paid Invoices</h3>
            </div>
            <div class="card-body">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <?php if (empty($paid_invoices)): ?>
                        <div class="alert alert-info">
                            No paid invoices found. All invoices have either been deposited or are still pending.
                        </div>
                    <?php else: ?>
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Project</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paid_invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($invoice['project_code']); ?>
                                        <br>
                                        <small><?php echo htmlspecialchars($invoice['project_title']); ?></small>
                                    </td>
                                    <td>₱<?php echo number_format($invoice['amount'], 2); ?></td>
                                    <td>₱<?php echo number_format($invoice['balance'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo htmlspecialchars($invoice['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Are you sure you want to mark this invoice as Deposited?');">
                                            <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($invoice['invoice_no']); ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-exchange-alt me-1"></i> Turn into Deposited
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 