<?php
require_once 'includes/session.php';
require_once 'config/db_connect.php';
require_once 'includes/audit_log.php';

// Require login
requireLogin();

// Log page access
logActivity($pdo, "Accessed main page", "Dashboard");
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
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-icon {
            font-size: 4.5rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }
        
        .main-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.75rem;
            font-weight: bold;
            margin-bottom: 0.75rem;
        }
        
        .card-description {
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .action-buttons {
            margin-top: 3rem;
        }
        
        .action-buttons .btn {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            border-radius: 8px;
            font-weight: 500;
            margin: 0 0.5rem;
        }
        
        .welcome-text {
            margin-bottom: 3rem;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .footer {
            margin-top: 4rem;
        }
    </style>
</head>
<body>
    <!-- Include the navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row mb-5">
            <div class="col-12 text-center welcome-text">
                <h1 class="display-4 fw-bold">Welcome to Sales & Collection System</h1>
                <p class="lead text-muted">Manage your invoices, deposits, and reports in one place</p>
            </div>
        </div>
        
        <div class="row justify-content-center">
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
        </div>
        
        <div class="row">
            <div class="col-12 text-center action-buttons">
                <a href="reports.php" class="btn btn-outline-primary">
                    <i class="fas fa-chart-bar me-2"></i>View Reports
                </a>
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="fas fa-users me-2"></i>User Management
                </a>
            </div>
        </div>
    </div>
    
    <footer class="footer py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© <?php echo date('Y'); ?> Sales & Collection System</span>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 