<?php
$page_title = 'Fee Structure Management';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle deletion
if (isset($_POST['delete_fee'])) {
    $fee_id = (int)$_POST['fee_id'];
    $stmt = $conn->prepare("DELETE FROM fee_structure WHERE id = ?");
    $stmt->bind_param("i", $fee_id);
    
    if ($stmt->execute()) {
        flashMessage('success', 'Fee structure deleted successfully.');
    } else {
        flashMessage('error', 'Error deleting fee structure.');
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get filter parameters
$education_level = isset($_GET['education_level']) ? $_GET['education_level'] : '';
$class = isset($_GET['class']) ? $_GET['class'] : '';
$term = isset($_GET['term']) ? (int)$_GET['term'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : date('Y');

// Build query
$query = "SELECT * FROM fee_structure WHERE 1=1";
if ($education_level) {
    $query .= " AND education_level = '" . $db->escape($education_level) . "'";
}
if ($class) {
    $query .= " AND class = '" . $db->escape($class) . "'";
}
if ($term) {
    $query .= " AND term = " . $term;
}
if ($academic_year) {
    $query .= " AND academic_year = '" . $db->escape($academic_year) . "'";
}
$query .= " ORDER BY education_level, class, term, fee_item";

$result = $conn->query($query);
$fee_structures = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $fee_structures[] = $row;
}

// Get unique academic years for filter
$years_result = $conn->query("SELECT DISTINCT academic_year FROM fee_structure ORDER BY academic_year DESC");
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
            <h1 class="text-2xl font-semibold text-gray-900">Fee Structure Management</h1>
            <a href="add.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>Add New Fee Structure
            </a>
        </div>

        <!-- Filters -->
        <div class="mt-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
            <form method="GET" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                <div>
                    <label for="education_level" class="block text-sm font-medium text-gray-700">Education Level</label>
                    <select id="education_level" name="education_level" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Levels</option>
                        <option value="primary" <?php echo $education_level === 'primary' ? 'selected' : ''; ?>>Primary</option>
                        <option value="junior_secondary" <?php echo $education_level === 'junior_secondary' ? 'selected' : ''; ?>>Junior Secondary</option>
                    </select>
                </div>

                <div>
                    <label for="class" class="block text-sm font-medium text-gray-700">Class</label>
                    <select id="class" name="class" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Classes</option>
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

        <!-- Fee Structure Table -->
        <div class="mt-6 flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Education Level
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Term
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fee Item
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Academic Year
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($fee_structures as $fee): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo ucfirst(str_replace('_', ' ', $fee['education_level'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo ucfirst($fee['class']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Term <?php echo $fee['term']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($fee['fee_item']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        KES <?php echo number_format($fee['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $fee['academic_year']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="edit.php?id=<?php echo $fee['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $fee['id']; ?>)" class="text-red-600 hover:text-red-900">
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
const primaryClasses = ['pg', 'pp1', 'pp2', 'grade1', 'grade2', 'grade3', 'grade4', 'grade5', 'grade6'];
const secondaryClasses = ['grade7', 'grade8', 'grade9', 'grade10'];
const currentClass = '<?php echo $class; ?>';

function populateClasses(selectedLevel) {
    const classSelect = document.getElementById('class');
    
    // Clear existing options
    classSelect.innerHTML = '<option value="">All Classes</option>';
    
    if (selectedLevel === 'primary') {
        primaryClasses.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className.charAt(0).toUpperCase() + className.slice(1);
            if (className === currentClass) {
                option.selected = true;
            }
            classSelect.appendChild(option);
        });
    } else if (selectedLevel === 'junior_secondary') {
        secondaryClasses.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className.charAt(0).toUpperCase() + className.slice(1);
            if (className === currentClass) {
                option.selected = true;
            }
            classSelect.appendChild(option);
        });
    }
}

document.getElementById('education_level').addEventListener('change', function() {
    populateClasses(this.value);
});

// Initialize class options
populateClasses('<?php echo $education_level; ?>');

function confirmDelete(feeId) {
    if (confirm('Are you sure you want to delete this fee structure?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_fee" value="1">
            <input type="hidden" name="fee_id" value="${feeId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
