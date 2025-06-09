<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../lib/tcpdf/tcpdf.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT i.*, c.name as client_name, c.company, c.email, c.phone, c.address, c.city, c.state, c.postal_code, c.country
                           FROM invoices i
                           JOIN customers c ON i.client_id = c.id
                           WHERE i.id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();

    if (!$invoice) throw new Exception("Invoice not found");

    $items = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $items->execute([$id]);
    $items = $items->fetchAll();

    $subtotal = array_reduce($items, fn($carry, $item) => $carry + ($item['quantity'] * $item['unit_price']), 0);
    $tax = $subtotal * ($invoice['tax_rate'] / 100);
    $total = $subtotal + $tax - $invoice['discount'];

    // Create PDF with custom header/footer
    class MYPDF extends TCPDF {
        public function Header() {
            // Logo (using placeholder)
            $this->Image('https://placehold.co/150x50?text=Binary+Intel&font=roboto', 15, 10, 40, 0, 'PNG', '', 'T', false, 300);
            
            // Company info
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 5, 'Binary Intel (Pty) Ltd', 0, 1, 'R');
            $this->SetFont('helvetica', '', 8);
            $this->Cell(0, 5, 'Reg: 2024/602620/07 | VAT: 1234567890', 0, 1, 'R');
            $this->Cell(0, 5, 'Empowering Digital Transformation', 0, 1, 'R');
            $this->Ln(10);
        }

        public function Footer() {
            $this->SetY(-25);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 5, 'Thank you for your business!', 0, 1, 'C');
            $this->Cell(0, 5, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
        }
    }

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Binary Intel');
    $pdf->SetTitle('Invoice '.$invoice['invoice_number']);
    $pdf->SetMargins(15, 35, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(15);
    $pdf->AddPage();

    // Invoice title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'TAX INVOICE', 0, 1, 'C');
    $pdf->Ln(5);

    // Invoice details
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(100, 5, 'From:', 0, 0);
    $pdf->Cell(0, 5, 'Invoice To:', 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 5, 'Binary Intel (Pty) Ltd', 0, 0);
    $pdf->Cell(0, 5, $invoice['client_name'], 0, 1);
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(100, 5, "123 Business Street\nJohannesburg\nSouth Africa\nPhone: +27 11 123 4567\nEmail: info@binaryintel.com", 0, 'L', false, 0);
    $pdf->MultiCell(0, 5, $invoice['address']."\n".$invoice['city']."\nPhone: ".$invoice['phone']."\nEmail: ".$invoice['email'], 0, 'L', false, 1);
    $pdf->Ln(10);

    // Invoice metadata
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(40, 7, 'Invoice Number:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 7, $invoice['invoice_number'], 1, 0, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(40, 7, 'Issue Date:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, date('F j, Y', strtotime($invoice['date'])), 1, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(40, 7, 'Due Date:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 7, date('F j, Y', strtotime($invoice['due_date'])), 1, 0, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(40, 7, 'Status:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, ucfirst($invoice['status']), 1, 1, 'L');
    $pdf->Ln(10);

    // Items table
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 7, 'Description', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Qty', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Unit Price', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Tax %', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Amount', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 9);
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['unit_price'];
        $itemTax = $itemTotal * ($item['tax_rate'] / 100);
        
        $pdf->Cell(100, 7, $item['description'], 1, 0, 'L');
        $pdf->Cell(20, 7, number_format($item['quantity'], 2, ',', ' '), 1, 0, 'R');
        $pdf->Cell(25, 7, 'R ' . number_format($item['unit_price'], 2, ',', ' '), 1, 0, 'R');
        $pdf->Cell(20, 7, $item['tax_rate'] . '%', 1, 0, 'R');
        $pdf->Cell(25, 7, 'R ' . number_format($itemTotal + $itemTax, 2, ',', ' '), 1, 1, 'R');
    }
    $pdf->Ln(5);

    // Totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($subtotal, 2, ',', ' '), 1, 1, 'R');
    
    $pdf->Cell(140, 7, 'Tax (' . $invoice['tax_rate'] . '%):', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($tax, 2, ',', ' '), 1, 1, 'R');
    
    if ($invoice['discount'] > 0) {
        $pdf->Cell(140, 7, 'Discount:', 0, 0, 'R');
        $pdf->Cell(25, 7, '-R ' . number_format($invoice['discount'], 2, ',', ' '), 1, 1, 'R');
    }
    
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(140, 8, 'Total Amount:', 0, 0, 'R');
    $pdf->Cell(25, 8, 'R ' . number_format($total, 2, ',', ' '), 1, 1, 'R');
    $pdf->Ln(10);

    // Payment details
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'Banking Details', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, 
        "Bank Name: Standard Bank\n" .
        "Branch Name: Newcastle\n" .
        "Branch Code: 007724\n" .
        "Account Holder: The Director Binary Intel (Pty) Ltd\n" .
        "Account Number: 10 23 310 557 1\n" .
        "Account Type: Current", 0, 'L');
    $pdf->Ln(5);

    // Contact information
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'For any inquiries or adjustments, please contact us at:', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Email: Binaryinteltech@gmail.com | Phone: 078 844 0649', 0, 1);
    $pdf->Ln(10);

    // Terms and conditions
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->MultiCell(0, 4, 
        "Terms & Conditions:\n" .
        "1. Payment is due within 14 days from the date of invoice.\n" .
        "2. Please make payment to the bank account details provided.\n" .
        "3. Late payment may incur a fee of 2% per month.\n" .
        "4. All amounts are in South African Rand (ZAR).", 0, 'L');

    // Clear output buffer and force download
    while (ob_get_level()) ob_end_clean();
    $pdf->Output('invoice_'.$invoice['invoice_number'].'.pdf', 'D');
    exit;

} catch (Exception $e) {
    error_log('PDF Error: '.$e->getMessage());
    $_SESSION['error'] = 'Failed to generate PDF: '.$e->getMessage();
    header('Location: list.php');
    exit;
}