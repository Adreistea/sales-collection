<?php
require_once 'includes/session.php';
require_once 'config/db_connect.php';
require_once 'includes/audit_log.php';

// Require login
requireLogin();

// Log page access
logActivity($pdo, "Accessed invoice page", "Invoices");

// Rest of your invoice.php code

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
    $stmt = $pdo->prepare("SELECT i.*, c.customer_name, c.contact_info, c.vat_registered 
                          FROM invoice i 
                          LEFT JOIN customers c ON i.customer_id = c.customer_id 
                          WHERE i.invoice_no = ?");
    $stmt->execute([$invoice_no]);
    
    if ($stmt->rowCount() == 0) {
        // Try to get from request_for_billing if not found in invoice
        $stmt = $pdo->prepare("SELECT r.*, c.customer_name, c.contact_info, c.vat_registered 
                              FROM request_for_billing r 
                              LEFT JOIN customers c ON r.customer_id = c.customer_id 
                              WHERE r.invoice_no = ?");
        $stmt->execute([$invoice_no]);
        
        if ($stmt->rowCount() == 0) {
            die("Invoice not found.");
        }
    }
    
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get deposit details if status is Paid or Deposited
    $deposit = null;
    if ($invoice['status'] == 'Paid' || $invoice['status'] == 'Deposited') {
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE invoice_no = ?");
        $stmt->execute([$invoice_no]);
        $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Determine VAT status
    $vat_status = ($invoice['vat_registered'] ?? 0) == 1 ? 'Vat: Applied' : 'Vat: Not Applied';
    
    // Create PDF
    class PDF extends FPDF {
        // Logo path
        private $logoPath;
        
        function __construct($logoPath = null) {
            parent::__construct();
            $this->logoPath = $logoPath;
        }
        
        function Header() {
            // Check if logo exists before trying to add it
            if ($this->logoPath && file_exists($this->logoPath)) {
                // Logo
                $this->Image($this->logoPath, 10, 10, 30);
            }
            
            // Company name
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(0, 10, 'JK LNG Co.', 0, 1, 'R');
            // Address
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, '123 Four Five, Six City, Seven Country', 0, 1, 'R');
            $this->Cell(0, 5, 'Phone: (123) 456-7890 | Email: info@company.com', 0, 1, 'R');
            $this->Ln(10);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
        }
    }
    
    // Check for logo in multiple possible locations
    $logoPath = null;
    $possibleLogoPaths = [
        'assets/img/logo.png',
        'assets/images/logo.png',
        'img/logo.png',
        'images/logo.png',
        'logo.png'
    ];
    
    foreach ($possibleLogoPaths as $path) {
        if (file_exists($path)) {
            $logoPath = $path;
            break;
        }
    }
    
    $pdf = new PDF($logoPath);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Invoice Title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Invoice Details
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Invoice Number:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(60, 7, $invoice['invoice_no'], 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Date:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 7, date('F d, Y', strtotime($invoice['date'])), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Status:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(60, 7, $invoice['status'], 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'VAT Status:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 7, $vat_status, 0, 1);
    
    $pdf->Ln(5);
    
    // Customer Information
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Customer Information', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Customer:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $invoice['customer_name'] ?? 'N/A', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Contact Info:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $invoice['contact_info'] ?? 'N/A', 0, 1);
    
    $pdf->Ln(5);
    
    // Project Information
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Project Information', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Project Code:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(60, 7, $invoice['project_code'] ?? 'N/A', 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Product Name:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 7, $invoice['product_name'] ?? 'N/A', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Project Title:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 7, $invoice['project_title'] ?? 'N/A', 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Remarks:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 7, $invoice['remarks'] ?? 'N/A', 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Internal Terms:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 7, $invoice['internal_terms'] ?? 'N/A', 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Customer Terms:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 7, $invoice['customer_terms'] ?? 'N/A', 0);
    
    $pdf->Ln(5);
    
    // Financial Information
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Financial Information', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Amount:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(60, 7, 'PHP ' . number_format($invoice['amount'] ?? 0, 2), 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Billed:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 7, 'PHP ' . number_format($invoice['billed'] ?? 0, 2), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Gross:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(60, 7, 'PHP ' . number_format($invoice['gross'] ?? 0, 2), 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'VAT Amount:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 7, 'PHP ' . number_format($invoice['vat'] ?? 0, 2), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Returns:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(60, 7, 'PHP ' . number_format($invoice['returns'] ?? 0, 2), 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Payments:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 7, 'PHP ' . number_format($invoice['payments'] ?? 0, 2), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Debit:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(60, 7, 'PHP ' . number_format($invoice['debit'] ?? 0, 2), 0);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Credit:', 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 7, 'PHP ' . number_format($invoice['credit'] ?? 0, 2), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Balance:', 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 7, 'PHP ' . number_format($invoice['balance'] ?? 0, 2), 0, 1);
    
    // Payment Details Section (only if status is Paid or Deposited)
    if ($invoice['status'] == 'Paid' || $invoice['status'] == 'Deposited') {
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Payment Details', 0, 1);
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(40, 7, 'Payment Type:', 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 7, $invoice['payment_type'] ?? 'N/A', 0, 1);
        
        if ($deposit) {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Deposit Number:', 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 7, $deposit['deposit_no'] ?? 'N/A', 0, 1);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Deposit Date:', 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 7, date('F d, Y', strtotime($deposit['date'])), 0, 1);
            
            if ($invoice['payment_type'] == 'Check') {
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(40, 7, 'Bank:', 0);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(60, 7, $deposit['bank'] ?? 'N/A', 0, 1);
                
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(40, 7, 'Bank Account No:', 0);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(60, 7, $deposit['bank_acc_no'] ?? 'N/A', 0, 1);
                
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(40, 7, 'Check Number:', 0);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(60, 7, $deposit['check_no'] ?? 'N/A', 0, 1);
                
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(40, 7, 'Check Amount:', 0);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(60, 7, 'PHP ' . number_format($deposit['total_checks'] ?? 0, 2), 0, 1);
            }
            
            if ($invoice['payment_type'] == 'Cash') {
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(40, 7, 'Cash Amount:', 0);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(60, 7, 'PHP ' . number_format($deposit['total_cash'] ?? 0, 2), 0, 1);
            }
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Remarks:', 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->MultiCell(0, 7, $deposit['remarks'] ?? 'N/A', 0);
        }
    }
    
    $pdf->Ln(10);
    
    // Signature Section
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, 'This is a computer-generated document. No signature is required.', 0, 1, 'C');
    
    // Output PDF
    $pdf->Output('Invoice_' . $invoice_no . '.pdf', 'I');
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?> 