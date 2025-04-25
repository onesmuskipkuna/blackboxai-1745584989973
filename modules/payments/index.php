<?php
$page_title = 'Payments Management';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle payment deletion
if (isset($_POST['delete_payment'])) {
    $payment_id = (int)$_POST['payment_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get payment details first
        $stmt = $conn->prepare("SELECT invoice_id, amount FROM payments WHERE id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        
        if ($payment) {
            // Update invoice paid amount and balance
            $stmt = $conn->prepare("
                UPDATE invoices 
                SET paid_amount = paid_amount - ?,
                    balance = balance + ?,
                    status = CASE 
                        WHEN (paid_amount - ?) = 0 THEN 'due'
                        WHEN (paid_amount - ?) < total_amount THEN 'partially_paid'
                        ELSE status
                    END
                WHERE id = ?
            ");
            $stmt->bind_param("ddddi", 
                $payment['amount'], 
                $payment['amount'], 
                $payment['amount'], 
                $payment['amount'], 
                $payment['invoice_id']
            );
            $stmt->execute();
            
            // Delete payment items first
            $stmt = $conn->prepare("DELETE FROM payment_items WHERE payment_id = ?");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            
            // Then delete the payment
            $stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            flashMessage('success', 'Payment deleted successfully.');
        }
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        flashMessage('error', 'Error deleting payment: ' . $e->getMessage());
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get filter parameters
$student = isset($_GET['student']) ? sanitize($_GET['student']) : '';
$payment_mode = isset($_GET['payment_mode']) ? sanitize($_GET['payment_mode']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query
$query = "
    SELECT p.*, 
           i.invoice_number,
           s.first_name, s.last_name, s.admission_number
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN students s ON i.student_id = s.id
    WHERE 1=1
";

if ($student) {
    $query .= " AND (s.first_name LIKE '%$student%' OR s.last_name LIKE '%$student%' OR s.admission_number LIKE '%$student%')";
}
if ($payment_mode) {
    $query .= " AND p.payment_mode = '$payment_mode'";
}
if ($date_from) {
    $query .= " AND DATE(p.created_at) >= '$date_from'";
}
if ($date_to) {
    $query .= " AND DATE(p.created_at) <= '$date_to'";
}

$query .= " ORDER BY p.created_at DESC";

$result = $conn->query($query);
$payments = $result->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header.php';
require_once '../../includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">Payments Management</h1>
            <a href="add.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>Record New Payment
            </a>
        </div>

        <!-- Filters -->
        <div class="mt-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
            <form method="GET" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                <div>
                    <label for="student" class="block text-sm font-medium text-gray-700">Student</label>
                    <input type="text" name="student" id="student" value="<?php echo htmlspecialchars($student); ?>"
                           class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                           placeholder="Name or Admission No.">
                </div>

                <div>
                    <label for="payment_mode" class="block text-sm font-medium text-gray-700">Payment Mode</label>
                    <select id="payment_mode" name="payment_mode" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Modes</option>
                        <option value="cash" <?php echo $payment_mode === 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="mpesa" <?php echo $payment_mode === 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
                        <option value="bank" <?php echo $payment_mode === 'bank' ? 'selected' : ''; ?>>Bank</option>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>"
                           class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>"
                           class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div class="mt-6 sm:mt-0">
                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="mt-6 flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Receipt Number
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Invoice Number
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Payment Mode
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reference
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($payment['payment_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                        <br>
                                        <span class="text-gray-500"><?php echo htmlspecialchars($payment['admission_number']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($payment['invoice_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        KES <?php echo number_format($payment['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo ucfirst($payment['payment_mode']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($payment['reference_number'] ?? '-'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($payment['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="view.php?id=<?php echo $payment['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="print.php?id=<?php echo $payment['id']; ?>" class="text-green-600 hover:text-green-900" title="Print Receipt">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $payment['id']; ?>)" class="text-red-600 hover:text-red-900" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(paymentId) {
    if (confirm('Are you sure you want to delete this payment? This will update the invoice balance. This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_payment" value="1">
            <input type="hidden" name="payment_id" value="${paymentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
