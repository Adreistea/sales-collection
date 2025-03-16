<?php
require_once 'includes/session.php';
require_once 'config/db_connect.php';
require_once 'includes/audit_log.php';

// Require login
requireLogin();

// Log page access
logActivity($pdo, "Accessed main page", "Dashboard");

// Get recent invoices for dashboard
try {
    $stmt = $pdo->query("SELECT invoice_no, date, customer_id, amount, status FROM invoice ORDER BY date DESC LIMIT 5");
    $recent_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching recent invoices: " . $e->getMessage();
    $recent_invoices = [];
}

// Get statistics
try {
    // Total invoices
    $stmt = $pdo->query("SELECT COUNT(*) FROM invoice");
    $total_invoices = $stmt->fetchColumn();
    
    // Total paid invoices
    $stmt = $pdo->query("SELECT COUNT(*) FROM invoice WHERE status = 'Paid' OR status = 'Deposited'");
    $total_paid = $stmt->fetchColumn();
    
    // Total pending invoices
    $stmt = $pdo->query("SELECT COUNT(*) FROM invoice WHERE status = 'Pending'");
    $total_pending = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(amount) FROM invoice");
    $total_revenue = $stmt->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
    $total_invoices = $total_paid = $total_pending = $total_revenue = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales & Collection System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            height: 100%;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .main-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .card-description {
            color: #6c757d;
        }
        
        .stats-card {
            border-left: 4px solid;
            border-radius: 4px;
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
        }
        
        .stats-card.primary {
            border-left-color: #007bff;
        }
        
        .stats-card.success {
            border-left-color: #28a745;
        }
        
        .stats-card.warning {
            border-left-color: #ffc107;
        }
        
        .stats-card.danger {
            border-left-color: #dc3545;
        }
        
        .stats-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <!-- Include the navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 mb-4">Welcome to Sales & Collection System</h1>
            </div>
        </div>
        
        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total Invoices</h6>
                                <h3><?php echo number_format($total_invoices); ?></h3>
                            </div>
                            <div class="stats-icon text-primary">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Paid Invoices</h6>
                                <h3><?php echo number_format($total_paid); ?></h3>
                            </div>
                            <div class="stats-icon text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Pending Invoices</h6>
                                <h3><?php echo number_format($total_pending); ?></h3>
                            </div>
                            <div class="stats-icon text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total Revenue</h6>
                                <h3>PHP <?php echo number_format($total_revenue, 2); ?></h3>
                            </div>
                            <div class="stats-icon text-danger">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <a href="invoice.php" class="text-decoration-none">
                    <div class="card dashboard-card text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-file-invoice card-icon text-primary"></i>
                            <h5 class="card-title">Invoice</h5>
                            <p class="card-description">Create and manage invoices</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="list.php" class="text-decoration-none">
                    <div class="card dashboard-card text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-list card-icon text-success"></i>
                            <h5 class="card-title">List</h5>
                            <p class="card-description">View and search invoices</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="deposit.php" class="text-decoration-none">
                    <div class="card dashboard-card text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-money-check-alt card-icon text-warning"></i>
                            <h5 class="card-title">Deposit</h5>
                            <p class="card-description">Manage deposits and payments</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-12 mt-4 text-center">
                <div class="d-flex justify-content-center gap-3">
                    <a href="reports.php" class="btn btn-outline-primary">
                        <i class="fas fa-chart-bar me-2"></i>View Reports
                    </a>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-users me-2"></i>User Management
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Invoices -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Recent Invoices</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_invoices)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No invoices found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_invoices as $invoice): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($invoice['date'])); ?></td>
                                                <td>PHP <?php echo number_format($invoice['amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $invoice['status'] == 'Paid' ? 'success' : 
                                                            ($invoice['status'] == 'Deposited' ? 'info' : 'warning'); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($invoice['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="print_view.php?invoice_no=<?php echo htmlspecialchars($invoice['invoice_no']); ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-print"></i> Print
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© <?php echo date('Y'); ?> Sales & Collection System</span>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 