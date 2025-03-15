<?php
require_once 'config/db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Reports</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Back Button -->
        <div class="back-button">
            <a href="main.php" class="btn btn-primary">← Back to Invoice</a>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="invoice.php">Main</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="list.php">List</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="reports.php">Reports</a>
            </li>
        </ul>

        <!-- Reports Section -->
        <div class="card mt-3">
            <div class="card-body">
                <h3 class="card-title mb-4">Invoice Reports</h3>
                
                <?php
                // Function to get daily revenue data
                function getDailyRevenueData($pdo) {
                    try {
                        $query = "SELECT 
                            DATE(created_at) as date,
                            SUM(amount) as daily_total
                            FROM invoice
                            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY date ASC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $labels = [];
                        $values = [];
                        
                        foreach ($chart_data as $row) {
                            $labels[] = date('M d', strtotime($row['date']));
                            $values[] = floatval($row['daily_total']);
                        }
                        
                        return [
                            'labels' => $labels,
                            'values' => $values
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting daily revenue: " . $e->getMessage());
                        return [
                            'labels' => [],
                            'values' => []
                        ];
                    }
                }
                
                // Function to get monthly revenue data
                function getMonthlyRevenueData($pdo) {
                    try {
                        $query = "SELECT 
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            SUM(amount) as monthly_total
                            FROM invoice 
                            WHERE YEAR(created_at) = YEAR(CURRENT_DATE)
                            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                            ORDER BY month ASC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $labels = [];
                        $values = [];
                        
                        foreach ($chart_data as $row) {
                            $labels[] = date('M Y', strtotime($row['month'] . '-01'));
                            $values[] = floatval($row['monthly_total']);
                        }
                        
                        return [
                            'labels' => $labels,
                            'values' => $values
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting monthly revenue: " . $e->getMessage());
                        return [
                            'labels' => [],
                            'values' => []
                        ];
                    }
                }
                
                // Function to get yearly revenue data
                function getYearlyRevenueData($pdo) {
                    try {
                        $query = "SELECT 
                            YEAR(created_at) as year,
                            SUM(amount) as yearly_total
                            FROM invoice 
                            GROUP BY YEAR(created_at)
                            ORDER BY year ASC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $labels = [];
                        $values = [];
                        
                        foreach ($chart_data as $row) {
                            $labels[] = $row['year'];
                            $values[] = floatval($row['yearly_total']);
                        }
                        
                        return [
                            'labels' => $labels,
                            'values' => $values
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting yearly revenue: " . $e->getMessage());
                        return [
                            'labels' => [],
                            'values' => []
                        ];
                    }
                }
                
                // Function to get VAT exempt revenue data
                function getVatExemptRevenueData($pdo) {
                    try {
                        $query = "SELECT 
                            DATE(created_at) as date,
                            SUM(amount - vat) as vat_exempt_total
                            FROM invoice 
                            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY date ASC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $labels = [];
                        $values = [];
                        
                        foreach ($chart_data as $row) {
                            $labels[] = date('M d', strtotime($row['date']));
                            $values[] = floatval($row['vat_exempt_total']);
                        }
                        
                        return [
                            'labels' => $labels,
                            'values' => $values
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting VAT exempt revenue: " . $e->getMessage());
                        return [
                            'labels' => [],
                            'values' => []
                        ];
                    }
                }
                
                // Function to get zero-rated revenue data
                function getZeroRatedRevenueData($pdo) {
                    try {
                        $query = "SELECT 
                            DATE(created_at) as date,
                            SUM(CASE 
                                WHEN vat = 0 OR vat = 0.00 THEN gross
                                ELSE 0 
                            END) as zero_rated_total
                            FROM invoice 
                            WHERE (vat = 0 OR vat = 0.00)
                            AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY date ASC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $labels = [];
                        $values = [];
                        
                        foreach ($chart_data as $row) {
                            $labels[] = date('M d', strtotime($row['date']));
                            $values[] = floatval($row['zero_rated_total']);
                        }
                        
                        return [
                            'labels' => $labels,
                            'values' => $values
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting zero-rated revenue: " . $e->getMessage());
                        return [
                            'labels' => [],
                            'values' => []
                        ];
                    }
                }
                
                // Function to get net revenue data
                function getNetRevenueData($pdo) {
                    try {
                        $query = "SELECT 
                            DATE(created_at) as date,
                            SUM(amount - returns - debit + credit) as net_total
                            FROM invoice 
                            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY date ASC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $labels = [];
                        $values = [];
                        
                        foreach ($chart_data as $row) {
                            $labels[] = date('M d', strtotime($row['date']));
                            $values[] = floatval($row['net_total']);
                        }
                        
                        return [
                            'labels' => $labels,
                            'values' => $values
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting net revenue: " . $e->getMessage());
                        return [
                            'labels' => [],
                            'values' => []
                        ];
                    }
                }
                
                // Function to get comparative revenue data
                function getComparativeRevenueData($pdo) {
                    try {
                        $query = "SELECT 
                            YEAR(created_at) as year,
                            MONTH(created_at) as month,
                            SUM(amount) as total_amount,
                            SUM(returns) as total_returns,
                            SUM(credit) as total_credits,
                            SUM(debit) as total_debits
                            FROM invoice 
                            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)
                            GROUP BY YEAR(created_at), MONTH(created_at)
                            ORDER BY year ASC, month ASC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $currentYear = date('Y');
                        $currentYearData = array_fill(0, 12, 0);
                        $previousYearData = array_fill(0, 12, 0);
                        
                        foreach ($chart_data as $row) {
                            $net_revenue = floatval($row['total_amount']) - 
                                         floatval($row['total_returns']) + 
                                         floatval($row['total_credits']) - 
                                         floatval($row['total_debits']);
                            
                            if ($row['year'] == $currentYear) {
                                $currentYearData[$row['month'] - 1] = $net_revenue;
                            } else {
                                $previousYearData[$row['month'] - 1] = $net_revenue;
                            }
                        }
                        
                        return [
                            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                            'currentYear' => array_values($currentYearData),
                            'previousYear' => array_values($previousYearData)
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting comparative revenue: " . $e->getMessage());
                        return ['labels' => [], 'currentYear' => [], 'previousYear' => []];
                    }
                }
                
                // Function to get contract receivables data
                function getContractReceivablesData($pdo) {
                    try {
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
                            LIMIT 12";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $labels = [];
                        $values = [];
                        $metadata = [];
                        
                        foreach ($chart_data as $row) {
                            $labels[] = date('M Y', strtotime($row['month'] . '-01'));
                            $values[] = floatval($row['total_outstanding']);
                            $metadata[] = [
                                'count' => $row['invoice_count'],
                                'projects' => $row['projects'] ? $row['projects'] : 'N/A'
                            ];
                        }
                        
                        return [
                            'labels' => $labels,
                            'values' => $values,
                            'metadata' => $metadata
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting contract receivables: " . $e->getMessage());
                        return ['labels' => [], 'values' => [], 'metadata' => []];
                    }
                }
                
                // Function to get subsidiary ledger data
                function getSubsidiaryLedgerData($pdo) {
                    try {
                        $query = "SELECT 
                            i.invoice_no,
                            i.date,
                            i.project_code,
                            i.project_title,
                            c.customer_name,
                            i.amount,
                            i.returns,
                            i.payments,
                            i.debit,
                            i.credit,
                            i.balance,
                            i.status
                            FROM invoice i
                            LEFT JOIN customers c ON i.customer_id = c.customer_id
                            WHERE i.balance > 0
                            ORDER BY i.date DESC
                            LIMIT 50";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $ledger_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        return $ledger_data;
                    } catch (Exception $e) {
                        error_log("Error getting subsidiary ledger: " . $e->getMessage());
                        return [];
                    }
                }
                
                // Function to get statement of accounts data
                function getStatementOfAccountsData($pdo) {
                    try {
                        $query = "SELECT 
                            c.customer_id,
                            c.customer_name,
                            COUNT(i.invoice_no) as invoice_count,
                            SUM(i.amount) as total_amount,
                            SUM(i.balance) as total_balance,
                            MAX(i.date) as latest_invoice_date
                            FROM customers c
                            LEFT JOIN invoice i ON c.customer_id = i.customer_id
                            GROUP BY c.customer_id, c.customer_name
                            HAVING SUM(i.balance) > 0
                            ORDER BY SUM(i.balance) DESC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $accounts_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        return $accounts_data;
                    } catch (Exception $e) {
                        error_log("Error getting statement of accounts: " . $e->getMessage());
                        return [];
                    }
                }
                
                // Function to get due and overdue accounts data
                function getDueAndOverdueAccountsData($pdo) {
                    try {
                        $query = "SELECT 
                            i.invoice_no,
                            i.date,
                            DATEDIFF(CURRENT_DATE, i.date) as days_outstanding,
                            c.customer_name,
                            i.project_code,
                            i.project_title,
                            i.amount,
                            i.balance,
                            i.status
                            FROM invoice i
                            LEFT JOIN customers c ON i.customer_id = c.customer_id
                            WHERE i.balance > 0 AND i.status = 'Pending'
                            ORDER BY days_outstanding DESC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $due_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Categorize by due status
                        $current = [];
                        $overdue30 = [];
                        $overdue60 = [];
                        $overdue90 = [];
                        $overdue90plus = [];
                        
                        foreach ($due_data as $invoice) {
                            if ($invoice['days_outstanding'] <= 30) {
                                $current[] = $invoice;
                            } elseif ($invoice['days_outstanding'] <= 60) {
                                $overdue30[] = $invoice;
                            } elseif ($invoice['days_outstanding'] <= 90) {
                                $overdue60[] = $invoice;
                            } elseif ($invoice['days_outstanding'] <= 120) {
                                $overdue90[] = $invoice;
                            } else {
                                $overdue90plus[] = $invoice;
                            }
                        }
                        
                        return [
                            'all' => $due_data,
                            'current' => $current,
                            'overdue30' => $overdue30,
                            'overdue60' => $overdue60,
                            'overdue90' => $overdue90,
                            'overdue90plus' => $overdue90plus
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting due and overdue accounts: " . $e->getMessage());
                        return [
                            'all' => [],
                            'current' => [],
                            'overdue30' => [],
                            'overdue60' => [],
                            'overdue90' => [],
                            'overdue90plus' => []
                        ];
                    }
                }
                
                // Function to get aging of contract receivables data
                function getAgingOfReceivablesData($pdo) {
                    try {
                        $query = "SELECT 
                            DATEDIFF(CURRENT_DATE, date) as days_outstanding,
                            SUM(balance) as total_balance,
                            COUNT(*) as invoice_count
                            FROM invoice
                            WHERE balance > 0 AND status = 'Pending'
                            GROUP BY 
                                CASE 
                                    WHEN DATEDIFF(CURRENT_DATE, date) <= 30 THEN '0-30'
                                    WHEN DATEDIFF(CURRENT_DATE, date) <= 60 THEN '31-60'
                                    WHEN DATEDIFF(CURRENT_DATE, date) <= 90 THEN '61-90'
                                    WHEN DATEDIFF(CURRENT_DATE, date) <= 120 THEN '91-120'
                                    ELSE '120+'
                                END
                            ORDER BY days_outstanding ASC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $aging_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Format data for chart
                        $categories = ['0-30 days', '31-60 days', '61-90 days', '91-120 days', 'Over 120 days'];
                        $values = array_fill(0, 5, 0);
                        $counts = array_fill(0, 5, 0);
                        
                        foreach ($aging_data as $row) {
                            $days = $row['days_outstanding'];
                            if ($days <= 30) {
                                $values[0] += floatval($row['total_balance']);
                                $counts[0] += $row['invoice_count'];
                            } elseif ($days <= 60) {
                                $values[1] += floatval($row['total_balance']);
                                $counts[1] += $row['invoice_count'];
                            } elseif ($days <= 90) {
                                $values[2] += floatval($row['total_balance']);
                                $counts[2] += $row['invoice_count'];
                            } elseif ($days <= 120) {
                                $values[3] += floatval($row['total_balance']);
                                $counts[3] += $row['invoice_count'];
                            } else {
                                $values[4] += floatval($row['total_balance']);
                                $counts[4] += $row['invoice_count'];
                            }
                        }
                        
                        return [
                            'categories' => $categories,
                            'values' => $values,
                            'counts' => $counts
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting aging of receivables: " . $e->getMessage());
                        return [
                            'categories' => [],
                            'values' => [],
                            'counts' => []
                        ];
                    }
                }
                
                // Function to get contract receivables per division
                function getReceivablesPerDivisionData($pdo) {
                    try {
                        // Assuming project_code first characters represent division
                        $query = "SELECT 
                            SUBSTRING(project_code, 1, 3) as division,
                            SUM(balance) as total_balance,
                            COUNT(*) as invoice_count
                            FROM invoice
                            WHERE balance > 0 AND status = 'Pending'
                            GROUP BY SUBSTRING(project_code, 1, 3)
                            ORDER BY total_balance DESC";
                            
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $division_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $labels = [];
                        $values = [];
                        $counts = [];
                        
                        foreach ($division_data as $row) {
                            $labels[] = $row['division'] ? $row['division'] : 'Unassigned';
                            $values[] = floatval($row['total_balance']);
                            $counts[] = $row['invoice_count'];
                        }
                        
                        return [
                            'labels' => $labels,
                            'values' => $values,
                            'counts' => $counts
                        ];
                    } catch (Exception $e) {
                        error_log("Error getting receivables per division: " . $e->getMessage());
                        return [
                            'labels' => [],
                            'values' => [],
                            'counts' => []
                        ];
                    }
                }
                
                // Get data for all charts
                $dailyRevenueData = getDailyRevenueData($pdo);
                $monthlyRevenueData = getMonthlyRevenueData($pdo);
                $yearlyRevenueData = getYearlyRevenueData($pdo);
                $vatExemptData = getVatExemptRevenueData($pdo);
                $zeroRatedData = getZeroRatedRevenueData($pdo);
                $netRevenueData = getNetRevenueData($pdo);
                $comparativeData = getComparativeRevenueData($pdo);
                $contractReceivablesData = getContractReceivablesData($pdo);
                $subsidiaryLedgerData = getSubsidiaryLedgerData($pdo);
                $statementOfAccountsData = getStatementOfAccountsData($pdo);
                $dueAndOverdueData = getDueAndOverdueAccountsData($pdo);
                $agingReceivablesData = getAgingOfReceivablesData($pdo);
                $receivablesPerDivisionData = getReceivablesPerDivisionData($pdo);
                
                // Check if we have any data
                $hasData = !empty($dailyRevenueData['labels']) || 
                           !empty($monthlyRevenueData['labels']) || 
                           !empty($yearlyRevenueData['labels']) ||
                           !empty($vatExemptData['labels']) ||
                           !empty($zeroRatedData['labels']) ||
                           !empty($netRevenueData['labels']) ||
                           !empty($comparativeData['currentYear']) ||
                           !empty($contractReceivablesData['labels']) ||
                           !empty($subsidiaryLedgerData) ||
                           !empty($statementOfAccountsData) ||
                           !empty($dueAndOverdueData['all']) ||
                           !empty($agingReceivablesData['categories']) ||
                           !empty($receivablesPerDivisionData['labels']);
                
                if (!$hasData) {
                    echo '<div class="alert alert-info">
                            <h5>No Invoice Data Available</h5>
                            <p>There is currently no invoice data in the system. Charts will be populated as invoices are created.</p>
                          </div>';
                }
                ?>
                
                <!-- Daily Revenue Chart -->
                <div class="report-section mb-5">
                    <h4>Daily Revenue (Last 7 Days)</h4>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%">
                        <canvas id="dailyRevenueChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Total Daily Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($dailyRevenueData['values']), 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Average Daily Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(count($dailyRevenueData['values']) > 0 ? array_sum($dailyRevenueData['values']) / count($dailyRevenueData['values']) : 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Revenue Chart -->
                <div class="report-section mb-5">
                    <h4>Monthly Revenue (Current Year)</h4>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Total Monthly Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($monthlyRevenueData['values']), 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Average Monthly Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(count($monthlyRevenueData['values']) > 0 ? array_sum($monthlyRevenueData['values']) / count($monthlyRevenueData['values']) : 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Yearly Revenue Chart -->
                <div class="report-section mb-5">
                    <h4>Yearly Revenue</h4>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%">
                        <canvas id="yearlyRevenueChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Total Yearly Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($yearlyRevenueData['values']), 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Average Yearly Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(count($yearlyRevenueData['values']) > 0 ? array_sum($yearlyRevenueData['values']) / count($yearlyRevenueData['values']) : 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- VAT Exempt Revenue Chart -->
                <div class="report-section mb-5">
                    <h4>VAT Exempt Revenue (Last 30 Days)</h4>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%">
                        <canvas id="vatExemptChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Total VAT Exempt Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($vatExemptData['values']), 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Average VAT Exempt Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(count($vatExemptData['values']) > 0 ? array_sum($vatExemptData['values']) / count($vatExemptData['values']) : 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Zero-Rated Revenue Chart -->
                <div class="report-section mb-5">
                    <h4>Zero-Rated Revenue (Last 30 Days)</h4>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%">
                        <canvas id="zeroRatedChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Total Zero-Rated Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($zeroRatedData['values']), 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Average Zero-Rated Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(count($zeroRatedData['values']) > 0 ? array_sum($zeroRatedData['values']) / count($zeroRatedData['values']) : 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Net Revenue Chart -->
                <div class="report-section mb-5">
                    <h4>Net Revenue (Last 30 Days)</h4>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%">
                        <canvas id="netRevenueChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Total Net Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($netRevenueData['values']), 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Average Net Revenue:</h6>
                                <p class="h4">₱<?php echo number_format(count($netRevenueData['values']) > 0 ? array_sum($netRevenueData['values']) / count($netRevenueData['values']) : 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comparative Revenue Chart -->
                <div class="report-section mb-5">
                    <h4>Comparative Revenue Report</h4>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%">
                        <canvas id="comparativeChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Current Year Total:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($comparativeData['currentYear']), 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Previous Year Total:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($comparativeData['previousYear']), 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contract Receivables Summary -->
                <div class="report-section mb-5">
                    <h4>Contract Receivables Summary</h4>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%">
                        <canvas id="contractReceivablesChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Total Outstanding Balance:</h6>
                                <p class="h4">₱<?php echo number_format(array_sum($contractReceivablesData['values']), 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Total Pending Invoices:</h6>
                                <p class="h4"><?php 
                                    $totalInvoices = 0;
                                    foreach ($contractReceivablesData['metadata'] as $meta) {
                                        $totalInvoices += $meta['count'];
                                    }
                                    echo $totalInvoices;
                                ?></p>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($contractReceivablesData['labels'])): ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Month</th>
                                    <th>Outstanding Amount</th>
                                    <th>Invoice Count</th>
                                    <th>Projects</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contractReceivablesData['labels'] as $index => $label): ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td>₱<?php echo number_format($contractReceivablesData['values'][$index], 2); ?></td>
                                    <td><?php echo $contractReceivablesData['metadata'][$index]['count']; ?></td>
                                    <td><?php echo $contractReceivablesData['metadata'][$index]['projects']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Subsidiary Ledger Contract Receivable -->
                <div class="report-section mb-5">
                    <h4>Subsidiary Ledger Contract Receivable</h4>
                    <?php if (empty($subsidiaryLedgerData)): ?>
                        <div class="alert alert-info">No receivables data available.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Project</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Returns</th>
                                    <th>Payments</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subsidiaryLedgerData as $ledger): ?>
                                <tr>
                                    <td><?php echo $ledger['invoice_no']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ledger['date'])); ?></td>
                                    <td><?php echo $ledger['project_code']; ?><br><small><?php echo $ledger['project_title']; ?></small></td>
                                    <td><?php echo $ledger['customer_name']; ?></td>
                                    <td>₱<?php echo number_format($ledger['amount'], 2); ?></td>
                                    <td>₱<?php echo number_format($ledger['returns'], 2); ?></td>
                                    <td>₱<?php echo number_format($ledger['payments'], 2); ?></td>
                                    <td>₱<?php echo number_format($ledger['debit'], 2); ?></td>
                                    <td>₱<?php echo number_format($ledger['credit'], 2); ?></td>
                                    <td class="<?php echo $ledger['balance'] > 0 ? 'text-danger' : ''; ?>">
                                        <strong>₱<?php echo number_format($ledger['balance'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $ledger['status'] == 'Pending' ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo $ledger['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Statement of Accounts -->
                <div class="report-section mb-5">
                    <h4>Statement of Accounts</h4>
                    <?php if (empty($statementOfAccountsData)): ?>
                        <div class="alert alert-info">No statement of accounts data available.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Customer</th>
                                    <th>Invoices</th>
                                    <th>Total Amount</th>
                                    <th>Outstanding Balance</th>
                                    <th>Latest Invoice</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statementOfAccountsData as $account): ?>
                                <tr>
                                    <td><?php echo $account['customer_name']; ?></td>
                                    <td><?php echo $account['invoice_count']; ?></td>
                                    <td>₱<?php echo number_format($account['total_amount'], 2); ?></td>
                                    <td class="text-danger">
                                        <strong>₱<?php echo number_format($account['total_balance'], 2); ?></strong>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($account['latest_invoice_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Due and Overdue Accounts -->
                <div class="report-section mb-5">
                    <h4>Due and Overdue Accounts</h4>
                    <?php if (empty($dueAndOverdueData['all'])): ?>
                        <div class="alert alert-info">No due or overdue accounts data available.</div>
                    <?php else: ?>
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Current</h6>
                                        <p class="h4"><?php echo count($dueAndOverdueData['current']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-warning bg-opacity-25">
                                    <div class="card-body text-center">
                                        <h6>30-60 Days</h6>
                                        <p class="h4"><?php echo count($dueAndOverdueData['overdue30']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-warning bg-opacity-50">
                                    <div class="card-body text-center">
                                        <h6>60-90 Days</h6>
                                        <p class="h4"><?php echo count($dueAndOverdueData['overdue60']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-danger bg-opacity-25">
                                    <div class="card-body text-center">
                                        <h6>90-120 Days</h6>
                                        <p class="h4"><?php echo count($dueAndOverdueData['overdue90']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-danger bg-opacity-50">
                                    <div class="card-body text-center">
                                        <h6>Over 120 Days</h6>
                                        <p class="h4"><?php echo count($dueAndOverdueData['overdue90plus']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-dark text-white">
                                    <div class="card-body text-center">
                                        <h6>Total</h6>
                                        <p class="h4"><?php echo count($dueAndOverdueData['all']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Days Outstanding</th>
                                        <th>Customer</th>
                                        <th>Project</th>
                                        <th>Amount</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dueAndOverdueData['all'] as $invoice): ?>
                                    <tr class="<?php 
                                        if ($invoice['days_outstanding'] > 120) echo 'table-danger';
                                        elseif ($invoice['days_outstanding'] > 90) echo 'table-warning';
                                        elseif ($invoice['days_outstanding'] > 60) echo 'table-warning bg-opacity-50';
                                        elseif ($invoice['days_outstanding'] > 30) echo 'table-warning bg-opacity-25';
                                    ?>">
                                        <td><?php echo $invoice['invoice_no']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($invoice['date'])); ?></td>
                                        <td>
                                            <strong><?php echo $invoice['days_outstanding']; ?></strong>
                                            <?php if ($invoice['days_outstanding'] > 30): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $invoice['customer_name']; ?></td>
                                        <td><?php echo $invoice['project_code']; ?><br><small><?php echo $invoice['project_title']; ?></small></td>
                                        <td>₱<?php echo number_format($invoice['amount'], 2); ?></td>
                                        <td>₱<?php echo number_format($invoice['balance'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php echo $invoice['status'] == 'Pending' ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo $invoice['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Aging of Contract Receivables -->
                <div class="report-section mb-5">
                    <h4>Aging of Contract Receivables</h4>
                    <?php if (empty($agingReceivablesData['categories'])): ?>
                        <div class="alert alert-info">No aging of receivables data available.</div>
                    <?php else: ?>
                        <div class="chart-container" style="position: relative; height:40vh; width:100%">
                            <canvas id="agingChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Age Range</th>
                                            <th>Outstanding Amount</th>
                                            <th>Invoice Count</th>
                                            <th>Percentage of Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalAmount = array_sum($agingReceivablesData['values']);
                                        foreach ($agingReceivablesData['categories'] as $index => $category): 
                                            if (isset($agingReceivablesData['values'][$index])):
                                                $amount = $agingReceivablesData['values'][$index];
                                                $percentage = $totalAmount > 0 ? ($amount / $totalAmount) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $category; ?></td>
                                            <td>₱<?php echo number_format($amount, 2); ?></td>
                                            <td><?php echo $agingReceivablesData['counts'][$index]; ?></td>
                                            <td><?php echo number_format($percentage, 2); ?>%</td>
                                        </tr>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                        <tr class="table-dark">
                                            <td><strong>Total</strong></td>
                                            <td><strong>₱<?php echo number_format($totalAmount, 2); ?></strong></td>
                                            <td><strong><?php echo array_sum($agingReceivablesData['counts']); ?></strong></td>
                                            <td><strong>100.00%</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Contract Receivables per Division -->
                <div class="report-section mb-5">
                    <h4>Contract Receivables per Division</h4>
                    <?php if (empty($receivablesPerDivisionData['labels'])): ?>
                        <div class="alert alert-info">No receivables per division data available.</div>
                    <?php else: ?>
                        <div class="chart-container" style="position: relative; height:40vh; width:100%">
                            <canvas id="divisionChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Division</th>
                                            <th>Outstanding Amount</th>
                                            <th>Invoice Count</th>
                                            <th>Percentage of Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalAmount = array_sum($receivablesPerDivisionData['values']);
                                        foreach ($receivablesPerDivisionData['labels'] as $index => $division): 
                                            $amount = $receivablesPerDivisionData['values'][$index];
                                            $percentage = $totalAmount > 0 ? ($amount / $totalAmount) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $division; ?></td>
                                            <td>₱<?php echo number_format($amount, 2); ?></td>
                                            <td><?php echo $receivablesPerDivisionData['counts'][$index]; ?></td>
                                            <td><?php echo number_format($percentage, 2); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-dark">
                                            <td><strong>Total</strong></td>
                                            <td><strong>₱<?php echo number_format($totalAmount, 2); ?></strong></td>
                                            <td><strong><?php echo array_sum($receivablesPerDivisionData['counts']); ?></strong></td>
                                            <td><strong>100.00%</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Charts Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Daily Revenue Chart
            const dailyCtx = document.getElementById('dailyRevenueChart').getContext('2d');
            new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($dailyRevenueData['labels']); ?>,
                    datasets: [{
                        label: 'Daily Revenue',
                        data: <?php echo json_encode($dailyRevenueData['values']); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (PHP)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });
            
            // Monthly Revenue Chart
            const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($monthlyRevenueData['labels']); ?>,
                    datasets: [{
                        label: 'Monthly Revenue',
                        data: <?php echo json_encode($monthlyRevenueData['values']); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (PHP)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
            
            // Yearly Revenue Chart
            const yearlyCtx = document.getElementById('yearlyRevenueChart').getContext('2d');
            new Chart(yearlyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($yearlyRevenueData['labels']); ?>,
                    datasets: [{
                        label: 'Yearly Revenue',
                        data: <?php echo json_encode($yearlyRevenueData['values']); ?>,
                        backgroundColor: 'rgba(153, 102, 255, 0.5)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (PHP)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Year'
                            }
                        }
                    }
                }
            });
            
            // VAT Exempt Revenue Chart
            const vatExemptCtx = document.getElementById('vatExemptChart').getContext('2d');
            new Chart(vatExemptCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($vatExemptData['labels']); ?>,
                    datasets: [{
                        label: 'VAT Exempt Revenue',
                        data: <?php echo json_encode($vatExemptData['values']); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (PHP)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });
            
            // Zero-Rated Revenue Chart
            const zeroRatedCtx = document.getElementById('zeroRatedChart').getContext('2d');
            new Chart(zeroRatedCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($zeroRatedData['labels']); ?>,
                    datasets: [{
                        label: 'Zero-Rated Revenue',
                        data: <?php echo json_encode($zeroRatedData['values']); ?>,
                        backgroundColor: 'rgba(255, 206, 86, 0.5)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 2,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (PHP)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });
            
            // Net Revenue Chart
            const netRevenueCtx = document.getElementById('netRevenueChart').getContext('2d');
            new Chart(netRevenueCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($netRevenueData['labels']); ?>,
                    datasets: [{
                        label: 'Net Revenue',
                        data: <?php echo json_encode($netRevenueData['values']); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (PHP)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    }
                }
            });

            // Aging of Receivables Chart
            const agingCtx = document.getElementById('agingChart').getContext('2d');
            new Chart(agingCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($agingReceivablesData['categories']); ?>,
                    datasets: [{
                        data: <?php echo json_encode($agingReceivablesData['values']); ?>,
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += '₱' + new Intl.NumberFormat().format(context.raw);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            // Division Receivables Chart
            const divisionCtx = document.getElementById('divisionChart').getContext('2d');
            new Chart(divisionCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($receivablesPerDivisionData['labels']); ?>,
                    datasets: [{
                        data: <?php echo json_encode($receivablesPerDivisionData['values']); ?>,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(201, 203, 207, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(201, 203, 207, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += '₱' + new Intl.NumberFormat().format(context.raw);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html> 