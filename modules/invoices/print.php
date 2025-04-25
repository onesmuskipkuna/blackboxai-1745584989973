<?php
$page_title = 'Print Invoice';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Get invoice ID
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$invoice_id) {
    flashMessage('error', 'Invalid invoice ID');
    redirect('index.php');
}

// Get invoice details with student information
$stmt = $conn->prepare("
    SELECT i.*, 
           s.first_name, s.last_name, s.admission_number, s.guardian_name, s.phone_number,
           s.class, s.education_level
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    WHERE i.id = ?
");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    flashMessage('error', 'Invoice not found');
    redirect('index.php');
}

// Get invoice items
$stmt = $conn->prepare("
    SELECT ii.*, fs.fee_item
    FROM invoice_items ii
    JOIN fee_structure fs ON ii.fee_structure_id = fs.id
    WHERE ii.invoice_id = ?
");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice_items = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                margin: 0;
                size: A4;
            }
            body {
                margin: 1.6cm;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-white">
    <!-- Print Button -->
    <div class="fixed top-4 right-4 print:hidden no-print">
        <button onclick="window.print()" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
            <i class="fas fa-print mr-2"></i>Print
        </button>
        <a href="view.php?id=<?php echo $invoice_id; ?>" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
    </div>

    <div class="max-w-4xl mx-auto p-8">
        <!-- School Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?></h1>
            <p class="text-gray-600">P.O. Box 123, City</p>
            <p class="text-gray-600">Phone: +254 123 456 789</p>
            <p class="text-gray-600">Email: info@school.com</p>
        </div>

        <!-- Invoice Title -->
        <div class="text-center mb-8">
            <h2 class="text-xl font-bold">FEE INVOICE</h2>
            <p class="text-gray-600">Invoice #: <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
            <p class="text-gray-600">Date: <?php echo date('F j, Y', strtotime($invoice['created_at'])); ?></p>
        </div>

        <!-- Student Details -->
        <div class="mb-8">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="font-bold mb-2">Student Details:</h3>
                    <p>Name: <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></p>
                    <p>Admission No: <?php echo htmlspecialchars($invoice['admission_number']); ?></p>
                    <p>Class: <?php echo ucfirst($invoice['class']); ?></p>
                    <p>Level: <?php echo ucfirst(str_replace('_', ' ', $invoice['education_level'])); ?></p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Guardian Details:</h3>
                    <p>Name: <?php echo htmlspecialchars($invoice['guardian_name']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($invoice['phone_number']); ?></p>
                    <p>Term: <?php echo $invoice['term']; ?></p>
                    <p>Academic Year: <?php echo $invoice['academic_year']; ?></p>
                </div>
            </div>
        </div>

        <!-- Fee Items -->
        <table class="min-w-full mb-8">
            <thead>
                <tr class="border-b-2 border-gray-300">
                    <th class="text-left py-2">Description</th>
                    <th class="text-right py-2">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice_items as $item): ?>
                <tr class="border-b border-gray-200">
                    <td class="py-2"><?php echo htmlspecialchars($item['fee_item']); ?></td>
                    <td class="text-right py-2"><?php echo number_format($item['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-300">
                    <th class="text-left py-2">Total Amount</th>
                    <th class="text-right py-2"><?php echo number_format($invoice['total_amount'], 2); ?></th>
                </tr>
                <tr>
                    <th class="text-left py-2">Amount Paid</th>
                    <th class="text-right py-2 text-green-600"><?php echo number_format($invoice['paid_amount'], 2); ?></th>
                </tr>
                <tr class="font-bold">
                    <th class="text-left py-2">Balance</th>
                    <th class="text-right py-2 text-red-600"><?php echo number_format($invoice['balance'], 2); ?></th>
                </tr>
            </tfoot>
        </table>

        <!-- Payment Details -->
        <div class="mb-8">
            <h3 class="font-bold mb-2">Payment Details:</h3>
            <p>Bank: School Bank Account</p>
            <p>Account Name: School Fees Account</p>
            <p>Account Number: 1234567890</p>
            <p>Bank Code: 01234</p>
        </div>

        <!-- Notes -->
        <div class="mb-8">
            <h3 class="font-bold mb-2">Notes:</h3>
            <ol class="list-decimal list-inside">
                <li>Please present this invoice when making payment</li>
                <li>All payments should be made to the school's bank account</li>
                <li>Cash payments should be made at the school's accounts office</li>
                <li>This invoice is due by: <?php echo date('F j, Y', strtotime($invoice['due_date'])); ?></li>
            </ol>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-600 mt-16">
            <p>This is a computer generated invoice</p>
            <p>Printed on: <?php echo date('F j, Y H:i:s'); ?></p>
        </div>
    </div>

    <script>
    // Auto-print when the page loads
    window.onload = function() {
        if (!window.location.search.includes('noprint')) {
            window.print();
        }
    };
    </script>
</body>
</html>
