<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them

// Set header to JSON
header('Content-Type: application/json');

try {
    // Database connection
    require_once 'config/db_connect.php';
    
    if (isset($_GET['type']) && $_GET['type'] === 'daily_revenue') {
        // Get revenue data for the last 7 days
        $query = "SELECT 
            DATE(created_at) as date,
            SUM(amount) as daily_total
            FROM invoices
            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC";
            
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($chart_data)) {
            echo json_encode([
                'status' => 'success',
                'chart_data' => [
                    'labels' => [],
                    'values' => []
                ],
                'message' => 'No data found'
            ]);
            exit;
        }

        // Format data for the chart
        $labels = [];
        $values = [];
        foreach ($chart_data as $row) {
            $labels[] = date('M d', strtotime($row['date']));
            $values[] = floatval($row['daily_total']);
        }

        echo json_encode([
            'status' => 'success',
            'chart_data' => [
                'labels' => $labels,
                'values' => $values
            ]
        ]);
    } else {
        // Default case - return dummy data
        echo json_encode([
            'status' => 'success',
            'chart_data' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'values' => [1000, 1500, 2000, 1800, 2200, 2500]
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