<?php
require_once 'config/db_connect.php';
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
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="row w-100">
            <div class="col-12 mb-4 text-center">
                <h1 class="display-4">Sales & Collection System</h1>
                <p class="lead">Select a module to continue</p>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="invoice.php" class="text-decoration-none">
                    <div class="card dashboard-card text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-file-invoice card-icon text-primary"></i>
                            <h5 class="card-title">Invoice</h5>
                            <p class="card-description">Create, manage, and view invoices</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="list.php" class="text-decoration-none">
                    <div class="card dashboard-card text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-receipt card-icon text-success"></i>
                            <h5 class="card-title">Receipts</h5>
                            <p class="card-description">View and manage receipt records</p>
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
                <a href="reports.php" class="btn btn-outline-primary">
                    <i class="fas fa-chart-bar me-2"></i>View Reports
                </a>
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