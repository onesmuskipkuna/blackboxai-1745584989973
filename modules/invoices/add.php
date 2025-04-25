<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = 'Create New Invoice';
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
$students_result = $conn->query("SELECT id, admission_number, first_name, last_name, class, education_level FROM students WHERE status = 'active' ORDER BY admission_number");
$students = [];
while ($row = $students_result->fetchArray(SQLITE3_ASSOC)) {
    $students[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $student_id = (int)$_POST['student_id'];
    $term = (int)$_POST['term'];
    $academic_year = sanitize($_POST['academic_year']);
    $due_date = sanitize($_POST['due_date']);
    $fee_items = isset($_POST['fee_items']) ? $_POST['fee_items'] : [];
    
    if (!$student_id || !$term || !$academic_year || !$due_date || empty($fee_items)) {
        $error = 'All fields are required';
    } else {
        // Begin transaction
        $conn->exec('BEGIN');
        
        try {
            // Generate invoice number (INV/YEAR/SERIAL)
            $year = date('Y');
            $result = $conn->query("SELECT MAX(CAST(substr(invoice_number, instr(invoice_number, '/') + 1) AS INTEGER)) as max_serial FROM invoices WHERE invoice_number LIKE 'INV/$year/%'");
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $next_serial = ($row['max_serial'] ?? 0) + 1;
            $invoice_number = "INV/$year/" . str_pad($next_serial, 4, '0', STR_PAD_LEFT);
            
            // Calculate totals
            $total_amount = 0;
            foreach ($fee_items as $item) {
                $total_amount += (float)$item['amount'];
            }
            
            // Create invoice
            $stmt = $conn->prepare("INSERT INTO invoices (student_id, invoice_number, total_amount, balance, term, academic_year, due_date) VALUES (:student_id, :invoice_number, :total_amount, :balance, :term, :academic_year, :due_date)");
            $stmt->bindValue(':student_id', $student_id, SQLITE3_INTEGER);
            $stmt->bindValue(':invoice_number', $invoice_number, SQLITE3_TEXT);
            $stmt->bindValue(':total_amount', $total_amount, SQLITE3_FLOAT);
            $stmt->bindValue(':balance', $total_amount, SQLITE3_FLOAT);
            $stmt->bindValue(':term', $term, SQLITE3_INTEGER);
            $stmt->bindValue(':academic_year', $academic_year, SQLITE3_TEXT);
            $stmt->bindValue(':due_date', $due_date, SQLITE3_TEXT);
            $stmt->execute();
            
            $invoice_id = $conn->lastInsertRowID();
            
            // Add invoice items
            $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, fee_structure_id, amount) VALUES (:invoice_id, :fee_structure_id, :amount)");
            foreach ($fee_items as $item) {
                $fee_id = (int)$item['fee_id'];
                $amount = (float)$item['amount'];
                $stmt->bindValue(':invoice_id', $invoice_id, SQLITE3_INTEGER);
                $stmt->bindValue(':fee_structure_id', $fee_id, SQLITE3_INTEGER);
                $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->exec('COMMIT');
            flashMessage('success', 'Invoice created successfully');
            redirect('index.php');
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->exec('ROLLBACK');
            $error = 'Error creating invoice: ' . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">Create New Invoice</h1>
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
            <form method="POST" id="invoiceForm" class="space-y-6">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700">Student</label>
                        <select id="student_id" name="student_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>" 
                                        data-class="<?php echo htmlspecialchars($student['class']); ?>"
                                        data-level="<?php echo htmlspecialchars($student['education_level']); ?>">
                                    <?php echo htmlspecialchars($student['admission_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="term" class="block text-sm font-medium text-gray-700">Term</label>
                        <select id="term" name="term" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select Term</option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>

                    <div>
                        <label for="academic_year" class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <input type="number" name="academic_year" id="academic_year" required
                               min="2000" max="2099" step="1" value="<?php echo date('Y'); ?>"
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>

                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" name="due_date" id="due_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
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
                        <i class="fas fa-save mr-2"></i>Create Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('student_id').addEventListener('change', loadFeeStructure);
document.getElementById('term').addEventListener('change', loadFeeStructure);
document.getElementById('academic_year').addEventListener('change', loadFeeStructure);

function loadFeeStructure() {
    const studentId = document.getElementById('student_id').value;
    const term = document.getElementById('term').value;
    const academicYear = document.getElementById('academic_year').value;
    
    if (!studentId || !term || !academicYear) {
        return;
    }
    
    const selectedOption = document.getElementById('student_id').selectedOptions[0];
    const studentClass = selectedOption.dataset.class;
    const educationLevel = selectedOption.dataset.level;
    
    // AJAX request to get fee structure
    fetch(`get_fee_structure.php?class=${studentClass}&education_level=${educationLevel}&term=${term}&academic_year=${academicYear}`)
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
                        <input type="hidden" name="fee_items[][fee_id]" value="${item.id}">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-gray-700">Amount (KES)</label>
                        <input type="number" name="fee_items[][amount]" value="${item.amount}" required min="0" step="0.01"
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md fee-amount"
                               onchange="calculateTotal()">
                    </div>
                `;
                container.appendChild(div);
            });
            
            calculateTotal();
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
document.getElementById('invoiceForm').addEventListener('submit', function(e) {
    const feeItems = document.getElementsByClassName('fee-amount');
    if (feeItems.length === 0) {
        e.preventDefault();
        alert('Please select a student and term to load fee items');
        return;
    }
    
    let total = 0;
    for (let item of feeItems) {
        if (!item.value || parseFloat(item.value) <= 0) {
            e.preventDefault();
            alert('All fee amounts must be greater than 0');
            return;
        }
        total += parseFloat(item.value);
    }
    
    if (total <= 0) {
        e.preventDefault();
        alert('Total amount must be greater than 0');
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
