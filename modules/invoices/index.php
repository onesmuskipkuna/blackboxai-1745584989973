<?php
$page_title = 'Invoices Management';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle invoice deletion
if (isset($_POST['delete_invoice'])) {
    $invoice_id = (int)$_POST['invoice_id'];
    
    // Begin transaction
    $conn->exec('BEGIN');
    
    try {
        // Delete invoice items first
        $stmt = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = :invoice_id");
        $stmt->bindValue(':invoice_id', $invoice_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Then delete the invoice
        $stmt = $conn->prepare("DELETE FROM invoices WHERE id = :id");
        $stmt->bindValue(':id', $invoice_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Commit transaction
        $conn->exec('COMMIT');
        flashMessage('success', 'Invoice deleted successfully.');
    } catch (Exception $e) {
        // Rollback on error
        $conn->exec('ROLLBACK');
        flashMessage('error', 'Error deleting invoice: ' . $e->getMessage());
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get filter parameters
$student = isset($_GET['student']) ? sanitize($_GET['student']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$term = isset($_GET['term']) ? (int)$_GET['term'] : '';
$academic_year = isset($_GET['academic_year']) ? sanitize($_GET['academic_year']) : date('Y');

// Build query
$query = "SELECT i.*, 
          s.first_name, s.last_name, s.admission_number,
          (SELECT SUM(amount) FROM payments WHERE invoice_id = i.id) as total_paid
          FROM invoices i 
          JOIN students s ON i.student_id = s.id 
          WHERE 1=1";

if ($student) {
    $query .= " AND (s.first_name LIKE '%$student%' OR s.last_name LIKE '%$student%' OR s.admission_number LIKE '%$student%')";
}
if ($status) {
    $query .= " AND i.status = '$status'";
}
if ($term) {
    $query .= " AND i.term = $term";
}
if ($academic_year) {
    $query .= " AND i.academic_year = '$academic_year'";
}
$query .= " ORDER BY i.created_at DESC";

$result = $conn->query($query);
$invoices = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $invoices[] = $row;
}

// Get unique academic years for filter
$years_result = $conn->query("SELECT DISTINCT academic_year FROM invoices ORDER BY academic_year DESC");
$academic_years = [];
while ($row = $years_result->fetchArray(SQLITE3_ASSOC)) {
    $academic_years[] = $row;
}

require_once '../../includes/header.php';
require_once '../../includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">Invoices Management</h1>
            <a href="add.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>Create New Invoice
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
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Status</option>
                        <option value="due" <?php echo $status === 'due' ? 'selected' : ''; ?>>Due</option>
                        <option value="partially_paid" <?php echo $status === 'partially_paid' ? 'selected' : ''; ?>>Partially Paid</option>
                        <option value="fully_paid" <?php echo $status === 'fully_paid' ? 'selected' : ''; ?>>Fully Paid</option>
                    </select>
                </div>

                <div>
                    <label for="term" class="block text-sm font-medium text-gray-700">Term</label>
                    <select id="term" name="term" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Terms</option>
                        <option value="1" <?php echo $term === 1 ? 'selected' : ''; ?>>Term 1</option>
                        <option value="2" <?php echo $term === 2 ? 'selected' : ''; ?>>Term 2</option>
                        <option value="3" <?php echo $term === 3 ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </div>

                <div>
                    <label for="academic_year" class="block text-sm font-medium text-gray-700">Academic Year</label>
                    <select id="academic_year" name="academic_year" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?php echo $year['academic_year']; ?>" 
                                    <?php echo $academic_year === $year['academic_year'] ? 'selected' : ''; ?>>
                                <?php echo $year['academic_year']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-6 sm:mt-0">
                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Invoices Table -->
        <div class="mt-6 flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Invoice Number
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Term/Year
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Amount
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Paid Amount
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Balance
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Due Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?>
                                        <br>
                                        <span class="text-gray-500"><?php echo htmlspecialchars($invoice['admission_number']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Term <?php echo $invoice['term']; ?> / <?php echo $invoice['academic_year']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        KES <?php echo number_format($invoice['total_amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        KES <?php echo number_format($invoice['paid_amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        KES <?php echo number_format($invoice['balance'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            echo match($invoice['status']) {
                                                'fully_paid' => 'bg-green-100 text-green-800',
                                                'partially_paid' => 'bg-yellow-100 text-yellow-800',
                                                default => 'bg-red-100 text-red-800'
                                            };
                                            ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $invoice['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="view.php?id=<?php echo $invoice['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="print.php?id=<?php echo $invoice['id']; ?>" class="text-green-600 hover:text-green-900" title="Print Invoice">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if ($invoice['status'] === 'due'): ?>
                                            <a href="edit.php?id=<?php echo $invoice['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $invoice['id']; ?>)" class="text-red-600 hover:text-red-900" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
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
function confirmDelete(invoiceId) {
    if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_invoice" value="1">
            <input type="hidden" name="invoice_id" value="${invoiceId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
