<?php
require_once 'includes/session.php';
require_once 'config/db_connect.php';
require_once 'includes/audit_log.php';

// Require admin role
requireRole('admin');

// Log the page access
logActivity($pdo, "Accessed audit logs", "Administration");

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Filtering variables
$username = isset($_GET['username']) ? $_GET['username'] : '';
$module = isset($_GET['module']) ? $_GET['module'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
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

if (!empty($action)) {
    $query .= " AND action LIKE ?";
    $count_query .= " AND action LIKE ?";
    $params[] = "%$action%";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Sales Collection System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 24px;
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .btn {
            border-radius: 6px;
            font-weight: 500;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            font-weight: 600;
            color: #495057;
        }
        
        .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
        }
        
        .pagination-info {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 300;
        }
        
        .highlight {
            background-color: #fff3cd;
            border-radius: 3px;
            padding: 0 3px;
        }
        
        .page-link {
            border-radius: 4px;
            margin: 0 2px;
        }
        
        .modal-content {
            border-radius: 10px;
            border: none;
        }
        
        .modal-header {
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="bi bi-journal-text me-2"></i>Audit Logs</h2>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-funnel me-2"></i>Filter Logs</h5>
            </div>
            <div class="card-body">
                <form method="get" action="audit_logs.php" class="row g-3">
                    <div class="col-md-4">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Search by username">
                    </div>
                    <div class="col-md-4">
                        <label for="module" class="form-label">Module</label>
                        <select class="form-select" id="module" name="module">
                            <option value="">All Modules</option>
                            <?php foreach ($modules as $mod): ?>
                                <option value="<?php echo htmlspecialchars($mod); ?>" <?php echo $module === $mod ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mod); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="action" class="form-label">Action</label>
                        <input type="text" class="form-control" id="action" name="action" value="<?php echo htmlspecialchars($action); ?>" placeholder="Search by action">
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
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Log Entries</h5>
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
                                <th>Username</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($log['module']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
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
                                                data-log-agent="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-exclamation-circle text-muted fs-1 d-block mb-2"></i>
                                        <p class="text-muted">No logs found matching your criteria</p>
                                        <?php if (!empty($username) || !empty($module) || !empty($action) || !empty($date_from) || !empty($date_to)): ?>
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
                    <h5 class="modal-title fw-semibold" id="logDetailModalLabel">Log Entry Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Log ID:</strong> <span id="modal-log-id"></span></p>
                            <p><strong>Date & Time:</strong> <span id="modal-log-time"></span></p>
                            <p><strong>Username:</strong> <span id="modal-log-user"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Module:</strong> <span id="modal-log-module"></span></p>
                            <p><strong>IP Address:</strong> <span id="modal-log-ip"></span></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <p><strong>Action:</strong></p>
                        <div class="p-3 bg-light rounded" id="modal-log-action"></div>
                    </div>
                    <div>
                        <p><strong>User Agent:</strong></p>
                        <div class="p-3 bg-light rounded" style="word-break: break-all;" id="modal-log-agent"></div>
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
                    const logAgent = button.getAttribute('data-log-agent');
                    
                    // Update modal content
                    document.getElementById('modal-log-id').textContent = logId;
                    document.getElementById('modal-log-time').textContent = logTime;
                    document.getElementById('modal-log-user').textContent = logUser;
                    document.getElementById('modal-log-module').textContent = logModule;
                    document.getElementById('modal-log-action').textContent = logAction;
                    document.getElementById('modal-log-ip').textContent = logIp;
                    document.getElementById('modal-log-agent').textContent = logAgent;
                });
            }
            
            // Highlight search terms in the table
            function highlightSearchTerms() {
                const searchParams = new URLSearchParams(window.location.search);
                const username = searchParams.get('username');
                const action = searchParams.get('action');
                
                if (username) {
                    highlightText('td:nth-child(2)', username);
                }
                
                if (action) {
                    highlightText('td:nth-child(4)', action);
                }
            }
            
            function highlightText(selector, text) {
                if (!text) return;
                
                const cells = document.querySelectorAll(selector);
                cells.forEach(cell => {
                    const content = cell.innerHTML;
                    const regex = new RegExp('(' + text + ')', 'gi');
                    cell.innerHTML = content.replace(regex, '<span class="highlight">$1</span>');
                });
            }
            
            highlightSearchTerms();
        });
    </script>
</body>
</html> 