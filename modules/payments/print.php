<?php
$page_title = 'Print Receipt';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Get payment ID
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$payment_id) {
    flashMessage('error', 'Invalid payment ID');
    redirect('index.php');
}

// Get payment details with student and invoice information
$stmt = $conn->prepare("
    SELECT p.*, 
           i.invoice_number, i.term, i.academic_year,
           s.first_name, s.last_name, s.admission_number, s.guardian_name, s.phone_number,
           s.class, s.education_level
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN students s ON i.student_id = s.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();

if (!$payment) {
    flashMessage('error', 'Payment not found');
    redirect('index.php');
}

// Get payment items
$stmt = $conn->prepare("
    SELECT pi.*, fs.fee_item
    FROM payment_items pi
    JOIN invoice_items ii ON pi.invoice_item_id = ii.id
    JOIN fee_structure fs ON ii.fee_structure_id = fs.id
    WHERE pi.payment_id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$payment_items = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo htmlspecialchars($payment['payment_number']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        <a href="view.php?id=<?php echo $payment_id; ?>" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
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

        <!-- Receipt Title -->
        <div class="text-center mb-8">
            <h2 class="text-xl font-bold">PAYMENT RECEIPT</h2>
            <p class="text-gray-600">Receipt #: <?php echo htmlspecialchars($payment['payment_number']); ?></p>
            <p class="text-gray-600">Date: <?php echo date('F j, Y', strtotime($payment['created_at'])); ?></p>
        </div>

        <!-- Student Details -->
        <div class="mb-8">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="font-bold mb-2">Student Details:</h3>
                    <p>Name: <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></p>
                    <p>Admission No: <?php echo htmlspecialchars($payment['admission_number']); ?></p>
                    <p>Class: <?php echo ucfirst($payment['class']); ?></p>
                    <p>Level: <?php echo ucfirst(str_replace('_', ' ', $payment['education_level'])); ?></p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Payment Details:</h3>
                    <p>Invoice #: <?php echo htmlspecialchars($payment['invoice_number']); ?></p>
                    <p>Term: <?php echo $payment['term']; ?></p>
                    <p>Academic Year: <?php echo $payment['academic_year']; ?></p>
                    <p>Payment Mode: <?php echo ucfirst($payment['payment_mode']); ?></p>
                    <?php if ($payment['reference_number']): ?>
                        <p>Reference #: <?php echo htmlspecialchars($payment['reference_number']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment Items -->
        <table class="min-w-full mb-8">
            <thead>
                <tr class="border-b-2 border-gray-300">
                    <th class="text-left py-2">Description</th>
                    <th class="text-right py-2">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payment_items as $item): ?>
                <tr class="border-b border-gray-200">
                    <td class="py-2"><?php echo htmlspecialchars($item['fee_item']); ?></td>
                    <td class="text-right py-2"><?php echo number_format($item['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-300 font-bold">
                    <th class="text-left py-2">Total Amount Paid</th>
                    <th class="text-right py-2">KES <?php echo number_format($payment['amount'], 2); ?></th>
                </tr>
            </tfoot>
        </table>

        <?php if ($payment['remarks']): ?>
        <!-- Remarks -->
        <div class="mb-8">
            <h3 class="font-bold mb-2">Remarks:</h3>
            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($payment['remarks'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-16">
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <div class="border-t-2 border-gray-300 pt-2">
                        <p class="text-center text-sm text-gray-600">Received By</p>
                    </div>
                </div>
                <div>
                    <div class="border-t-2 border-gray-300 pt-2">
                        <p class="text-center text-sm text-gray-600">Official Stamp</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center text-sm text-gray-600 mt-8">
            <p>This is an official receipt</p>
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
