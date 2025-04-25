<?php
$page_title = 'Dashboard';
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

$total_students = 0;
$fees_this_month = 0;
$expenses_this_month = 0;
$outstanding_fees = 0;
$recent_payments = [];
$recent_expenses = [];

$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $total_students = $row['total'];
}

$result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE strftime('%m', created_at) = strftime('%m', 'now') AND strftime('%Y', created_at) = strftime('%Y', 'now')");
if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $fees_this_month = $row['total'] ?? 0;
}

$result = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE strftime('%m', date) = strftime('%m', 'now') AND strftime('%Y', date) = strftime('%Y', 'now')");
if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $expenses_this_month = $row['total'] ?? 0;
}

$result = $conn->query("SELECT SUM(balance) as total FROM invoices WHERE status != 'fully_paid'");
if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $outstanding_fees = $row['total'] ?? 0;
}

$result = $conn->query("
    SELECT p.*, s.first_name, s.last_name, s.admission_number 
    FROM payments p 
    JOIN invoices i ON p.invoice_id = i.id 
    JOIN students s ON i.student_id = s.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $recent_payments[] = $row;
}

$result = $conn->query("
    SELECT e.*, ec.name as category_name 
    FROM expenses e 
    JOIN expense_categories ec ON e.category_id = ec.id 
    ORDER BY e.date DESC 
    LIMIT 5
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $recent_expenses[] = $row;
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
        
        <!-- Stats -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Students -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-graduate text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?php echo number_format($total_students); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fees This Month -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Fees Collected (This Month)</dt>
                                <dd class="text-lg font-semibold text-gray-900">KES <?php echo number_format($fees_this_month, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenses This Month -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-receipt text-2xl text-red-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Expenses (This Month)</dt>
                                <dd class="text-lg font-semibold text-gray-900">KES <?php echo number_format($expenses_this_month, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outstanding Fees -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-2xl text-yellow-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Outstanding Fees</dt>
                                <dd class="text-lg font-semibold text-gray-900">KES <?php echo number_format($outstanding_fees, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Payments -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Payments</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($recent_payments as $payment): ?>
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-600 truncate">
                                    <?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?> (<?php echo $payment['admission_number']; ?>)
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Receipt: <?php echo $payment['payment_number']; ?>
                                </p>
                            </div>
                            <div class="ml-2">
                                <p class="text-sm font-semibold text-gray-900">KES <?php echo number_format($payment['amount'], 2); ?></p>
                                <p class="mt-1 text-xs text-gray-500">
                                    <?php echo date('M j, Y', strtotime($payment['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Expenses -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Expenses</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($recent_expenses as $expense): ?>
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo $expense['description']; ?>
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Category: <?php echo $expense['category_name']; ?>
                                </p>
                            </div>
                            <div class="ml-2">
                                <p class="text-sm font-semibold text-red-600">KES <?php echo number_format($expense['amount'], 2); ?></p>
                                <p class="mt-1 text-xs text-gray-500">
                                    <?php echo date('M j, Y', strtotime($expense['date'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
