<?php
require_once 'config/db_connect.php';

// Check if FPDF library exists
if (!file_exists('fpdf186/fpdf.php')) {
    die("Error: FPDF library not found. Please download it from http://www.fpdf.org/ and extract to the fpdf186 folder.");
}

require 'fpdf186/fpdf.php';

if (!isset($_GET['invoice_no'])) {
    die("No invoice selected.");
}

try {
    $invoice_no = $_GET['invoice_no'];
    
    // Get invoice details
    $stmt = $pdo->prepare("SELECT r.*, c.customer_name, c.contact_info, c.vat_registered 
                          FROM request_for_billing r 
                          LEFT JOIN customers c ON r.customer_id = c.customer_id 
                          WHERE r.invoice_no = ?");
    $stmt->execute([$invoice_no]);
    
    if ($stmt->rowCount() == 0) {
        die("Record not found.");
    }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Determine VAT status
    $vat_status = ($row['vat_registered'] == 1) ? 'Vat: Applied' : 'Vat: Not Applied';
    
    // Create PDF
    try {
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Company Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(190, 10, $row['customer_name'] ?? 'Customer Name', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        
        // Display contact info and VAT status
        $pdf->Cell(190, 7, ($row['contact_info'] ?? 'Contact Info') . ' | ' . $vat_status, 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->Cell(190, 0, '', 'T', 1, 'C'); // Line break
        $pdf->Ln(5);
        
        // Invoice Title
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(190, 10, 'INVOICE', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Invoice Info
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 10, 'Invoice No:', 1, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(140, 10, $row['invoice_no'], 1, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 10, 'Date:', 1, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(140, 10, date('Y-m-d'), 1, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 10, 'Project Code:', 1, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(140, 10, $row['project_code'], 1, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 10, 'Project Name:', 1, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(140, 10, $row['project_name'], 1, 1);
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 10, 'Status:', 1, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(140, 10, $row['status'], 1, 1);
        
        $pdf->Ln(10);
        
        // Footer
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(190, 5, 'This is an accounting copy.', 0, 1, 'C');
        $pdf->Output('I', 'Billing_Detail.pdf');
    } catch (Exception $e) {
        echo "<div style='color:red; padding:20px; border:1px solid red;'>";
        echo "<h2>PDF Generation Error</h2>";
        echo "<p>There was an error generating the PDF: " . $e->getMessage() . "</p>";
        echo "<p>Please make sure you have the full FPDF library installed.</p>";
        echo "<p>You can download it from <a href='http://www.fpdf.org/'>http://www.fpdf.org/</a></p>";
        echo "<p>Extract the contents to the 'fpdf186' folder in your project.</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 