<?php
require_once 'includes/session.php';
require_once 'config/db_connect.php';
require_once 'includes/audit_log.php';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Filtering variables
$username = isset($_GET['username']) ? $_GET['username'] : '';
$module = isset($_GET['module']) ? $_GET['module'] : '';
$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the query
$query = "SELECT * FROM audit_logs WHERE 1=1";
$count_query = "SELECT COUNT(*) FROM audit_logs WHERE 1=1";
$params = [];

// Add filters to query
if (!empty($username)) {
    $query .= " AND username LIKE ?";
    $count_query .= " AND username LIKE ?";
    $params[] = "%$username%";
}

if (!empty($module)) {
    $query .= " AND module = ?";
    $count_query .= " AND module = ?";
    $params[] = $module;
}

if (!empty($transaction_id)) {
    $query .= " AND (action LIKE ? OR details LIKE ?)";
    $count_query .= " AND (action LIKE ? OR details LIKE ?)";
    $params[] = "%$transaction_id%";
    $params[] = "%$transaction_id%";
}

if (!empty($date_from)) {
    $query .= " AND DATE(created_at) >= ?";
    $count_query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(created_at) <= ?";
    $count_query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

// Add order and limit
$query .= " ORDER BY created_at DESC LIMIT $offset, $records_per_page";

// Execute count query
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique modules for filter dropdown
$module_stmt = $pdo->query("SELECT DISTINCT module FROM audit_logs ORDER BY module");
$modules = $module_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
try {
    // Total invoices created
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action LIKE 'Invoice created%'");
    $stmt->execute();
    $invoices_created = $stmt->fetchColumn();
    
    // Total deposits created
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action LIKE 'Deposit created%'");
    $stmt->execute();
    $deposits_created = $stmt->fetchColumn();
    
    // Total payments processed
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action LIKE 'Payment%'");
    $stmt->execute();
    $payments_processed = $stmt->fetchColumn();
    
    // Most active user
    $stmt = $pdo->query("SELECT username, COUNT(*) as count FROM audit_logs GROUP BY username ORDER BY count DESC LIMIT 1");
    $most_active = $stmt->fetch(PDO::FETCH_ASSOC);
    $most_active_user = $most_active ? $most_active['username'] : 'None';
    
} catch (PDOException $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
    $invoices_created = $deposits_created = $payments_processed = 0;
    $most_active_user = 'Error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Audit Logs - Sales Collection System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
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
        
        .stats-card.info {
            border-left-color: #17a2b8;
        }
        
        .stats-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        
        .pagination-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .highlight {
            background-color: #fff3cd;
        }
        
        .transaction-badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-journal-text me-2"></i>Transaction Audit Logs</h2>
            <a href="export_logs.php<?php echo !empty($_GET) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
            </a>
        </div>
        
        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Invoices Created</h6>
                                <h3><?php echo number_format($invoices_created); ?></h3>
                            </div>
                            <div class="stats-icon text-primary">
                                <i class="bi bi-file-earmark-text"></i>
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
                                <h6 class="text-muted">Deposits Created</h6>
                                <h3><?php echo number_format($deposits_created); ?></h3>
                            </div>
                            <div class="stats-icon text-success">
                                <i class="bi bi-cash-stack"></i>
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
                                <h6 class="text-muted">Payments Processed</h6>
                                <h3><?php echo number_format($payments_processed); ?></h3>
                            </div>
                            <div class="stats-icon text-warning">
                                <i class="bi bi-credit-card"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Most Active User</h6>
                                <h3><?php echo htmlspecialchars($most_active_user); ?></h3>
                            </div>
                            <div class="stats-icon text-info">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Transactions</h5>
            </div>
            <div class="card-body">
                <form method="get" action="audit_logs.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="username" class="form-label">User</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Search by username">
                    </div>
                    <div class="col-md-4">
                        <label for="module" class="form-label">Transaction Type</label>
                        <select class="form-select" id="module" name="module">
                            <option value="">All Types</option>
                            <?php foreach ($modules as $mod): ?>
                                <option value="<?php echo htmlspecialchars($mod); ?>" <?php echo $module === $mod ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mod); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="transaction_id" class="form-label">Invoice/Deposit #</label>
                        <input type="text" class="form-control" id="transaction_id" name="transaction_id" value="<?php echo htmlspecialchars($transaction_id); ?>" placeholder="Search by ID">
                    </div>
                    <div class="col-md-4">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="d-grid gap-2 d-md-flex w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                            <a href="audit_logs.php" class="btn btn-secondary flex-grow-1">
                                <i class="bi bi-x-circle me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Transaction History</h5>
                    <span class="pagination-info">
                        Showing <?php echo min($total_records, $offset + 1); ?> to 
                        <?php echo min($total_records, $offset + $records_per_page); ?> of 
                        <?php echo number_format($total_records); ?> entries
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Transaction Type</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <?php 
                                // Parse details JSON if available
                                $details = [];
                                if (!empty($log['details'])) {
                                    $details = json_decode($log['details'], true);
                                }
                                
                                // Determine badge color based on module
                                $badgeClass = 'bg-secondary';
                                if ($log['module'] == 'Invoices') $badgeClass = 'bg-primary';
                                if ($log['module'] == 'Deposits') $badgeClass = 'bg-success';
                                if ($log['module'] == 'Payments') $badgeClass = 'bg-warning text-dark';
                                if ($log['module'] == 'User Management') $badgeClass = 'bg-info';
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($log['module']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#logDetailModal" 
                                                data-log-id="<?php echo $log['log_id']; ?>"
                                                data-log-time="<?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>"
                                                data-log-user="<?php echo htmlspecialchars($log['username']); ?>"
                                                data-log-module="<?php echo htmlspecialchars($log['module']); ?>"
                                                data-log-action="<?php echo htmlspecialchars($log['action']); ?>"
                                                data-log-ip="<?php echo htmlspecialchars($log['ip_address']); ?>"
                                                data-log-details='<?php echo htmlspecialchars(json_encode($details)); ?>'>
                                            <i class="bi bi-info-circle"></i> View
                                        </button>
                                        
                                        <?php if (!empty($details)): ?>
                                            <?php if (isset($details['invoice_no'])): ?>
                                                <span class="badge bg-light text-dark transaction-badge ms-1">
                                                    Invoice: <?php echo htmlspecialchars($details['invoice_no']); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($details['deposit_no'])): ?>
                                                <span class="badge bg-light text-dark transaction-badge ms-1">
                                                    Deposit: <?php echo htmlspecialchars($details['deposit_no']); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bi bi-exclamation-circle text-muted fs-1 d-block mb-2"></i>
                                        <p class="text-muted">No transaction logs found matching your criteria</p>
                                        <?php if (!empty($username) || !empty($module) || !empty($transaction_id) || !empty($date_from) || !empty($date_to)): ?>
                                            <a href="audit_logs.php" class="btn btn-sm btn-outline-secondary">Clear filters</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Calculate range of page numbers to display
                            $range = 2; // Number of pages to show before and after current page
                            $start_page = max(1, $page - $range);
                            $end_page = min($total_pages, $page + $range);
                            
                            // Always show first page
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                            }
                            
                            // Display page numbers
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">';
                                echo '<a class="page-link" href="?page=' . $i . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '">' . $i . '</a>';
                                echo '</li>';
                            }
                            
                            // Always show last page
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Log Detail Modal -->
    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="logDetailModalLabel">Transaction Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Log ID:</strong> <span id="modal-log-id"></span></p>
                            <p><strong>Date & Time:</strong> <span id="modal-log-time"></span></p>
                            <p><strong>User:</strong> <span id="modal-log-user"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Transaction Type:</strong> <span id="modal-log-module"></span></p>
                            <p><strong>IP Address:</strong> <span id="modal-log-ip"></span></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <p><strong>Action:</strong></p>
                        <div class="p-3 bg-light rounded" id="modal-log-action"></div>
                    </div>
                    <div id="transaction-details-container">
                        <p><strong>Transaction Details:</strong></p>
                        <div class="p-3 bg-light rounded" id="modal-transaction-details">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody id="transaction-details-table">
                                    <!-- Details will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle log detail modal
            const logDetailModal = document.getElementById('logDetailModal');
            if (logDetailModal) {
                logDetailModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    // Extract info from data attributes
                    const logId = button.getAttribute('data-log-id');
                    const logTime = button.getAttribute('data-log-time');
                    const logUser = button.getAttribute('data-log-user');
                    const logModule = button.getAttribute('data-log-module');
                    const logAction = button.getAttribute('data-log-action');
                    const logIp = button.getAttribute('data-log-ip');
                    const logDetails = button.getAttribute('data-log-details');
                    
                    // Update modal content
                    document.getElementById('modal-log-id').textContent = logId;
                    document.getElementById('modal-log-time').textContent = logTime;
                    document.getElementById('modal-log-user').textContent = logUser;
                    document.getElementById('modal-log-module').textContent = logModule;
                    document.getElementById('modal-log-action').textContent = logAction;
                    document.getElementById('modal-log-ip').textContent = logIp;
                    
                    // Handle transaction details
                    const detailsContainer = document.getElementById('transaction-details-container');
                    const detailsTable = document.getElementById('transaction-details-table');
                    
                    if (logDetails && logDetails !== 'null') {
                        detailsContainer.style.display = 'block';
                        
                        try {
                            const details = JSON.parse(logDetails);
                            let tableHtml = '';
                            
                            // Handle invoice details
                            if (details.invoice_no) {
                                tableHtml += `<tr><td><strong>Invoice Number:</strong></td><td>${details.invoice_no}</td></tr>`;
                            }
                            
                            // Handle deposit details
                            if (details.deposit_no) {
                                tableHtml += `<tr><td><strong>Deposit Number:</strong></td><td>${details.deposit_no}</td></tr>`;
                            }
                            
                            // Handle payment details
                            if (details.amount) {
                                tableHtml += `<tr><td><strong>Amount:</strong></td><td>PHP ${parseFloat(details.amount).toFixed(2)}</td></tr>`;
                            }
                            
                            if (details.payment_type) {
                                tableHtml += `<tr><td><strong>Payment Type:</strong></td><td>${details.payment_type}</td></tr>`;
                            }
                            
                            // Handle additional data if available
                            if (details.data && typeof details.data === 'object') {
                                for (const [key, value] of Object.entries(details.data)) {
                                    if (value !== null && value !== undefined) {
                                        // Format the key for display (convert_snake_case to Title Case)
                                        const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                        
                                        // Format the value based on its type
                                        let formattedValue = value;
                                        if (typeof value === 'number' && key.includes('amount') || key.includes('total') || key.includes('price')) {
                                            formattedValue = `PHP ${parseFloat(value).toFixed(2)}`;
                                        } else if (typeof value === 'boolean') {
                                            formattedValue = value ? 'Yes' : 'No';
                                        } else if (value instanceof Date) {
                                            formattedValue = value.toLocaleDateString();
                                        }
                                        
                                        tableHtml += `<tr><td><strong>${formattedKey}:</strong></td><td>${formattedValue}</td></tr>`;
                                    }
                                }
                            }
                            
                            detailsTable.innerHTML = tableHtml || '<tr><td>No additional details available</td></tr>';
                        } catch (e) {
                            detailsTable.innerHTML = '<tr><td>Error parsing transaction details</td></tr>';
                            console.error('Error parsing details:', e);
                        }
                    } else {
                        detailsContainer.style.display = 'none';
                    }
                });
            }
            
            // Highlight search terms in the table
            function highlightSearchTerms() {
                const searchParams = new URLSearchParams(window.location.search);
                const username = searchParams.get('username');
                const transactionId = searchParams.get('transaction_id');
                
                if (username) {
                    highlightText('td:nth-child(2)', username);
                }
                
                if (transactionId) {
                    highlightText('td:nth-child(4)', transactionId);
                    highlightText('.transaction-badge', transactionId);
                }
            }
            
            function highlightText(selector, text) {
                if (!text) return;
                
                const cells = document.querySelectorAll(selector);
                cells.forEach(cell => {
                    const content = cell.innerHTML;
                    const regex = new RegExp('(' + text.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')', 'gi');
                    cell.innerHTML = content.replace(regex, '<span class="highlight">$1</span>');
                });
            }
            
            highlightSearchTerms();
        });
    </script>
</body>
</html>