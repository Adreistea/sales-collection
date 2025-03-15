<?php
require_once 'config/db_connect.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'All';

try {
    // Prepare the SQL query
    $sql = "SELECT invoice_no, project_code, project_name, status FROM request_for_billing WHERE 1=1";
    
    // Add status filter if not "All"
    if ($status !== 'All') {
        $sql .= " AND status = :status";
    }
    
    // Add search filter if provided
    if (!empty($search)) {
        $sql .= " AND (invoice_no LIKE :search OR project_code LIKE :search OR project_name LIKE :search)";
    }
    
    // Prepare and execute the statement
    $stmt = $pdo->prepare($sql);
    
    if ($status !== 'All') {
        $stmt->bindValue(':status', $status);
    }
    
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }
    
    $stmt->execute();
    
    // Start building the table
    echo '<table class="table table-striped table-bordered">';
    echo '<thead class="table-primary">';
    echo '<tr>';
    echo '<th>Invoice No</th>';
    echo '<th>Project Code</th>';
    echo '<th>Project Name</th>';
    echo '<th>Status</th>';
    echo '<th>Action</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    // Check if we have results
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['invoice_no']) . '</td>';
            echo '<td>' . htmlspecialchars($row['project_code']) . '</td>';
            echo '<td>' . htmlspecialchars($row['project_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '<td class="text-center">';
            echo '<a href="print_view.php?invoice_no=' . htmlspecialchars($row['invoice_no']) . '" class="btn btn-sm btn-primary">Print View</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="text-center">No records found</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
}
?> 