<?php
$page_title = 'Record New Payment';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

$error = '';
$success = '';

// Get active students for dropdown
$students_result = $conn->query("
    SELECT DISTINCT s.id, s.admission_number, s.first_name, s.last_name 
    FROM students s 
    JOIN invoices i ON s.id = i.student_id 
    WHERE s.status = 'active' 
    AND i.status != 'fully_paid'
    ORDER BY s.admission_number
");
$students = $students_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $invoice_id = (int)$_POST['invoice_id'];
    $payment_mode = sanitize($_POST['payment_mode']);
    $reference_number = sanitize($_POST['reference_number']);
    $remarks = sanitize($_POST['remarks']);
    $fee_items = isset($_POST['fee_items']) ? $_POST['fee_items'] : [];
    
    if (!$invoice_id || !$payment_mode || empty($fee_items)) {
        $error = 'All fields are required';
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Generate payment number (RCP/YEAR/SERIAL)
            $year = date('Y');
            $result = $conn->query("SELECT MAX(CAST(SUBSTRING_INDEX(payment_number, '/', -1) AS UNSIGNED)) as max_serial FROM payments WHERE payment_number LIKE 'RCP/$year/%'");
            $row = $result->fetch_assoc();
            $next_serial = ($row['max_serial'] ?? 0) + 1;
            $payment_number = "RCP/$year/" . str_pad($next_serial, 4, '0', STR_PAD_LEFT);
            
            // Calculate total payment amount
            $total_amount = 0;
            foreach ($fee_items as $item) {
                $total_amount += (float)$item['amount'];
            }
            
            // Create payment record
            $stmt = $conn->prepare("INSERT INTO payments (invoice_id, payment_number, amount, payment_mode, reference_number, remarks) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsss", $invoice_id, $payment_number, $total_amount, $payment_mode, $reference_number, $remarks);
            $stmt->execute();
            
            $payment_id = $conn->insert_id;
            
            // Add payment items
            $stmt = $conn->prepare("INSERT INTO payment_items (payment_id, invoice_item_id, amount) VALUES (?, ?, ?)");
            foreach ($fee_items as $item) {
                $invoice_item_id = (int)$item['invoice_item_id'];
                $amount = (float)$item['amount'];
                $stmt->bind_param("iid", $payment_id, $invoice_item_id, $amount);
                $stmt->execute();
            }
            
            // Update invoice paid amount and balance
            $stmt = $conn->prepare("
                UPDATE invoices 
                SET paid_amount = paid_amount + ?,
                    balance = balance - ?,
                    status = CASE 
                        WHEN (balance - ?) <= 0 THEN 'fully_paid'
                        ELSE 'partially_paid'
                    END
                WHERE id = ?
            ");
            $stmt->bind_param("dddi", $total_amount, $total_amount, $total_amount, $invoice_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to print receipt
            redirect("print.php?id=$payment_id");
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = 'Error recording payment: ' . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">Record New Payment</h1>
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="mt-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
            <form method="POST" id="paymentForm" class="space-y-6">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700">Student</label>
                        <select id="student_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['admission_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="invoice_id" class="block text-sm font-medium text-gray-700">Invoice</label>
                        <select id="invoice_id" name="invoice_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select Invoice</option>
                        </select>
                    </div>

                    <div>
                        <label for="payment_mode" class="block text-sm font-medium text-gray-700">Payment Mode</label>
                        <select id="payment_mode" name="payment_mode" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select Payment Mode</option>
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank">Bank</option>
                        </select>
                    </div>

                    <div>
                        <label for="reference_number" class="block text-sm font-medium text-gray-700">Reference Number</label>
                        <input type="text" name="reference_number" id="reference_number"
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="Transaction/Receipt Number">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                        <textarea name="remarks" id="remarks" rows="2"
                                  class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                  placeholder="Any additional notes"></textarea>
                    </div>
                </div>

                <!-- Fee Items Section -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900">Fee Items</h3>
                    <div id="feeItemsContainer" class="mt-4 space-y-4">
                        <!-- Fee items will be loaded here dynamically -->
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <div class="text-lg font-bold text-gray-900">
                            Total Amount: KES <span id="totalAmount">0.00</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('student_id').addEventListener('change', loadInvoices);
document.getElementById('invoice_id').addEventListener('change', loadFeeItems);

function loadInvoices() {
    const studentId = document.getElementById('student_id').value;
    if (!studentId) return;
    
    // Clear fee items
    document.getElementById('feeItemsContainer').innerHTML = '';
    document.getElementById('totalAmount').textContent = '0.00';
    
    // AJAX request to get student's invoices
    fetch(`get_invoices.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            const invoiceSelect = document.getElementById('invoice_id');
            invoiceSelect.innerHTML = '<option value="">Select Invoice</option>';
            
            data.forEach(invoice => {
                const option = document.createElement('option');
                option.value = invoice.id;
                option.textContent = `${invoice.invoice_number} - Balance: KES ${parseFloat(invoice.balance).toFixed(2)}`;
                invoiceSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}

function loadFeeItems() {
    const invoiceId = document.getElementById('invoice_id').value;
    if (!invoiceId) return;
    
    // AJAX request to get invoice items
    fetch(`get_fee_items.php?invoice_id=${invoiceId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('feeItemsContainer');
            container.innerHTML = '';
            
            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-3 border-b pb-4';
                div.innerHTML = `
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700">${item.fee_item}</label>
                        <input type="hidden" name="fee_items[][invoice_item_id]" value="${item.id}">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700">Balance: KES ${parseFloat(item.balance).toFixed(2)}</label>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700">Amount to Pay</label>
                        <input type="number" name="fee_items[][amount]" required min="0" max="${item.balance}" step="0.01"
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md fee-amount"
                               onchange="calculateTotal()" onkeyup="calculateTotal()">
                    </div>
                `;
                container.appendChild(div);
            });
        })
        .catch(error => console.error('Error:', error));
}

function calculateTotal() {
    const amounts = document.getElementsByClassName('fee-amount');
    let total = 0;
    
    for (let amount of amounts) {
        total += parseFloat(amount.value) || 0;
    }
    
    document.getElementById('totalAmount').textContent = total.toFixed(2);
}

// Form validation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const feeItems = document.getElementsByClassName('fee-amount');
    if (feeItems.length === 0) {
        e.preventDefault();
        alert('Please select an invoice to load fee items');
        return;
    }
    
    let total = 0;
    for (let item of feeItems) {
        if (!item.value || parseFloat(item.value) < 0 || parseFloat(item.value) > parseFloat(item.max)) {
            e.preventDefault();
            alert('Invalid payment amount. Amount must be between 0 and the remaining balance.');
            return;
        }
        total += parseFloat(item.value);
    }
    
    if (total <= 0) {
        e.preventDefault();
        alert('Total payment amount must be greater than 0');
        return;
    }
    
    const paymentMode = document.getElementById('payment_mode').value;
    const reference = document.getElementById('reference_number').value;
    
    if ((paymentMode === 'mpesa' || paymentMode === 'bank') && !reference) {
        e.preventDefault();
        alert('Reference number is required for M-Pesa and Bank payments');
        return;
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
