<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Include TCPDF library
require_once '../lib/tcpdf/tcpdf.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

// Fetch invoice with client details
$stmt = $pdo->prepare("SELECT i.*, c.name as client_name, c.company, c.email, c.phone, c.address, c.city, c.state, c.postal_code, c.country
                       FROM invoices i
                       JOIN customers c ON i.client_id = c.id
                       WHERE i.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: list.php');
    exit;
}

// Fetch invoice items
$items = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// Calculate totals
$subtotal = array_reduce($items, function($carry, $item) {
    return $carry + ($item['quantity'] * $item['unit_price']);
}, 0);

$tax = $subtotal * ($invoice['tax_rate'] / 100);
$total = $subtotal + $tax - $invoice['discount'];

// Generate PDF function
function generateInvoicePDF($invoice, $items, $subtotal, $tax, $total) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($APP_NAME);
    $pdf->SetTitle('Invoice ' . $invoice['invoice_number']);
    $pdf->SetSubject('Invoice');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Logo (using placeholder - replace with your actual logo path)
    $logoPath = '../images/company_logo.png';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 15, 15, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    } else {
        $pdf->Image('https://placehold.co/150x50?text=Company+Logo', 15, 15, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    
    // Invoice title and number
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 5, 'No: ' . $invoice['invoice_number'], 0, 1, 'R');
    
    // Date
    $pdf->Cell(0, 5, 'Date: ' . date('d/m/Y', strtotime($invoice['date'])), 0, 1, 'R');
    $pdf->Cell(0, 5, 'Due Date: ' . date('d/m/Y', strtotime($invoice['due_date'])), 0, 1, 'R');
    
    // Spacer
    $pdf->Ln(10);
    
    // From/To addresses
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(85, 5, 'From:', 0, 0);
    $pdf->Cell(85, 5, 'To:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    // Company address (update with your details)
    $pdf->MultiCell(85, 5, "$APP_NAME\n123 Business Street\nJohannesburg\nSouth Africa\nVAT: 123456789", 0, 'L', false, 0);
    $pdf->MultiCell(85, 5, $invoice['client_name'] . "\n" . 
        ($invoice['company'] ? $invoice['company'] . "\n" : "") . 
        $invoice['address'] . "\n" . 
        $invoice['city'] . ", " . $invoice['state'] . " " . $invoice['postal_code'] . "\n" . 
        $invoice['country'], 0, 'L', false, 1);
    
    // Spacer
    $pdf->Ln(10);
    
    // Invoice items table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(90, 7, 'Description', 1, 0, 'L');
    $pdf->Cell(20, 7, 'Qty', 1, 0, 'R');
    $pdf->Cell(25, 7, 'Unit Price', 1, 0, 'R');
    $pdf->Cell(25, 7, 'Tax', 1, 0, 'R');
    $pdf->Cell(25, 7, 'Amount', 1, 1, 'R');
    
    // Invoice items
    $pdf->SetFont('helvetica', '', 10);
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['unit_price'];
        $itemTax = $itemTotal * ($item['tax_rate'] / 100);
        
        $pdf->Cell(90, 7, $item['description'], 1, 0, 'L');
        $pdf->Cell(20, 7, number_format($item['quantity'], 2, ',', ' '), 1, 0, 'R');
        $pdf->Cell(25, 7, 'R ' . number_format($item['unit_price'], 2, ',', ' '), 1, 0, 'R');
        $pdf->Cell(25, 7, $item['tax_rate'] . '%', 1, 0, 'R');
        $pdf->Cell(25, 7, 'R ' . number_format($itemTotal + $itemTax, 2, ',', ' '), 1, 1, 'R');
    }
    
    // Display totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(135, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($subtotal, 2, ',', ' '), 1, 1, 'R');
    
    $pdf->Cell(135, 7, 'Tax (' . $invoice['tax_rate'] . '%):', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($tax, 2, ',', ' '), 1, 1, 'R');
    
    if ($invoice['discount'] > 0) {
        $pdf->Cell(135, 7, 'Discount:', 0, 0, 'R');
        $pdf->Cell(25, 7, 'R ' . number_format($invoice['discount'], 2, ',', ' '), 1, 1, 'R');
    }
    
    $pdf->Cell(135, 7, 'Total:', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($total, 2, ',', ' '), 1, 1, 'R');
    
    // Notes
    if (!empty($invoice['notes'])) {
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 5, 'Notes: ' . $invoice['notes'], 0, 'L');
    }
    
    // Footer
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Thank you for your business!', 0, 1, 'C');
    
    return $pdf;
}

// Handle PDF download
if (isset($_GET['download_pdf'])) {
    $pdf = generateInvoicePDF($invoice, $items, $subtotal, $tax, $total);
    $pdf->Output('invoice_' . $invoice['invoice_number'] . '.pdf', 'D');
    exit;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm mb-4">
                <!-- In the card header section, update the download button link -->
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Invoice #<?= $invoice['invoice_number'] ?></h4>
                    <div>
                        <a href="download.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-file-pdf me-1"></i> Download PDF
                        </a>
                        <span class="badge bg-<?= 
                            $invoice['status'] === 'paid' ? 'success' : 
                            ($invoice['status'] === 'overdue' ? 'danger' : 
                            ($invoice['status'] === 'sent' ? 'primary' : 'secondary')) 
                        ?>">
                            <?= ucfirst($invoice['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>From</h5>
                            <p>
                                <strong><?= htmlspecialchars($APP_NAME) ?></strong><br>
                                123 Business Street<br>
                                Johannesburg, South Africa<br>
                                VAT: 123456789
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5>Bill To</h5>
                            <p>
                                <strong><?= htmlspecialchars($invoice['client_name']) ?></strong><br>
                                <?php if ($invoice['company']): ?>
                                    <?= htmlspecialchars($invoice['company']) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars($invoice['address']) ?><br>
                                <?= htmlspecialchars($invoice['city']) ?>, <?= htmlspecialchars($invoice['state']) ?> <?= htmlspecialchars($invoice['postal_code']) ?><br>
                                <?= htmlspecialchars($invoice['country']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p><strong>Invoice Date:</strong> <?= date('M j, Y', strtotime($invoice['date'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Due Date:</strong> <?= date('M j, Y', strtotime($invoice['due_date'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= 
                                    $invoice['status'] === 'paid' ? 'success' : 
                                    ($invoice['status'] === 'overdue' ? 'danger' : 
                                    ($invoice['status'] === 'sent' ? 'primary' : 'secondary')) 
                                ?>">
                                    <?= ucfirst($invoice['status']) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Tax</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                    <td><?= number_format($item['quantity'], 2, ',', ' ') ?></td>
                                    <td>R <?= number_format($item['unit_price'], 2, ',', ' ') ?></td>
                                    <td><?= $item['tax_rate'] ?>%</td>
                                    <td>R <?= number_format($item['quantity'] * $item['unit_price'] * (1 + ($item['tax_rate'] / 100)), 2, ',', ' ') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end">Subtotal:</td>
                                    <td>R <?= number_format($subtotal, 2, ',', ' ') ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">Tax (<?= $invoice['tax_rate'] ?>%):</td>
                                    <td>R <?= number_format($tax, 2, ',', ' ') ?></td>
                                </tr>
                                <?php if ($invoice['discount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end">Discount:</td>
                                    <td>-R <?= number_format($invoice['discount'], 2, ',', ' ') ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold">R <?= number_format($total, 2, ',', ' ') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if ($invoice['notes']): ?>
                    <div class="border-top pt-3">
                        <h6>Notes</h6>
                        <p><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <a href="edit.php?id=<?= $invoice['id'] ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <button class="btn btn-success">
                                <i class="fas fa-paper-plane me-1"></i> Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Watermark Section -->
            <div class="text-center mt-4" style="opacity: 0.6;">
                <img src="/assets/images/watermarks/1.png" alt="Probi Notes Logo" style="height: 20px; vertical-align: middle;">
                <span class="text-muted" style="font-size: 0.9rem;">Created using Probi Notes</span>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>