<?php
require_once 'config/db_connect.php';

// Set header to JSON
header('Content-Type: application/json');

try {
    // Get the report type from the query string
    $type = $_GET['type'];

    if ($type === 'contract_receivables') {
        // Get contract receivables data for pending invoices by month
        $query = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as invoice_count,
            SUM(balance) as total_outstanding,
            GROUP_CONCAT(project_code) as projects
            FROM invoice 
            WHERE status = 'Pending'
            AND balance > 0
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12";  // Show last 12 months
            
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($chart_data)) {
            echo json_encode([
                'status' => 'success',
                'chart_data' => [
                    'labels' => [],
                    'values' => []
                ],
                'message' => 'No pending receivables found'
            ]);
            exit;
        }

        // Format data for the chart
        $labels = [];
        $values = [];
        foreach ($chart_data as $row) {
            // Format month for display (e.g., "Jan 2024")
            $labels[] = date('M Y', strtotime($row['month'] . '-01'));
            
            // Add value with additional information
            $values[] = floatval($row['total_outstanding']);
        }

        echo json_encode([
            'status' => 'success',
            'chart_data' => [
                'labels' => $labels,
                'values' => $values
            ]
        ]);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Error: ' . $e->getMessage(),
        'chart_data' => [
            'labels' => [],
            'values' => []
        ]
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'General Error: ' . $e->getMessage(),
        'chart_data' => [
            'labels' => [],
            'values' => []
        ]
    ]);
}
?> 