<?php
$page_title = 'Edit Fee Structure';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

$error = '';
$success = '';
$fee = null;

// Get fee ID from URL
$fee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$fee_id) {
    flashMessage('error', 'Invalid fee structure ID');
    redirect('index.php');
}

// Get fee structure details
$stmt = $conn->prepare("SELECT * FROM fee_structure WHERE id = ?");
$stmt->bind_param("i", $fee_id);
$stmt->execute();
$result = $stmt->get_result();
$fee = $result->fetch_assoc();

if (!$fee) {
    flashMessage('error', 'Fee structure not found');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $education_level = sanitize($_POST['education_level']);
    $class = sanitize($_POST['class']);
    $term = (int)$_POST['term'];
    $academic_year = sanitize($_POST['academic_year']);
    $fee_item = sanitize($_POST['fee_item']);
    $amount = (float)$_POST['amount'];
    
    if (empty($education_level) || empty($class) || empty($term) || empty($academic_year) || empty($fee_item) || $amount <= 0) {
        $error = 'All fields are required and amount must be greater than 0';
    } else {
        // Update fee structure
        $stmt = $conn->prepare("UPDATE fee_structure SET education_level = ?, class = ?, term = ?, academic_year = ?, fee_item = ?, amount = ? WHERE id = ?");
        $stmt->bind_param("ssissdi", $education_level, $class, $term, $academic_year, $fee_item, $amount, $fee_id);
        
        if ($stmt->execute()) {
            flashMessage('success', 'Fee structure updated successfully');
            redirect('index.php');
        } else {
            $error = 'Error updating fee structure: ' . $conn->error;
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Fee Structure</h1>
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
            <form method="POST" class="space-y-6" id="feeForm">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div>
                        <label for="education_level" class="block text-sm font-medium text-gray-700">Education Level</label>
                        <select id="education_level" name="education_level" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select Level</option>
                            <option value="primary" <?php echo $fee['education_level'] === 'primary' ? 'selected' : ''; ?>>Primary</option>
                            <option value="junior_secondary" <?php echo $fee['education_level'] === 'junior_secondary' ? 'selected' : ''; ?>>Junior Secondary</option>
                        </select>
                    </div>

                    <div>
                        <label for="class" class="block text-sm font-medium text-gray-700">Class</label>
                        <select id="class" name="class" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select Class</option>
                        </select>
                    </div>

                    <div>
                        <label for="term" class="block text-sm font-medium text-gray-700">Term</label>
                        <select id="term" name="term" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select Term</option>
                            <option value="1" <?php echo $fee['term'] === 1 ? 'selected' : ''; ?>>Term 1</option>
                            <option value="2" <?php echo $fee['term'] === 2 ? 'selected' : ''; ?>>Term 2</option>
                            <option value="3" <?php echo $fee['term'] === 3 ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>

                    <div>
                        <label for="academic_year" class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <input type="number" name="academic_year" id="academic_year" required
                               min="2000" max="2099" step="1" value="<?php echo htmlspecialchars($fee['academic_year']); ?>"
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>

                    <div>
                        <label for="fee_item" class="block text-sm font-medium text-gray-700">Fee Item</label>
                        <input type="text" name="fee_item" id="fee_item" required
                               value="<?php echo htmlspecialchars($fee['fee_item']); ?>"
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="e.g., Tuition Fee">
                    </div>

                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (KES)</label>
                        <input type="number" name="amount" id="amount" required
                               min="0" step="0.01" value="<?php echo htmlspecialchars($fee['amount']); ?>"
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="0.00">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>Update Fee Structure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const primaryClasses = ['pg', 'pp1', 'pp2', 'grade1', 'grade2', 'grade3', 'grade4', 'grade5', 'grade6'];
const secondaryClasses = ['grade7', 'grade8', 'grade9', 'grade10'];
const currentClass = '<?php echo $fee['class']; ?>';

function populateClasses(selectedLevel) {
    const classSelect = document.getElementById('class');
    
    // Clear existing options
    classSelect.innerHTML = '<option value="">Select Class</option>';
    
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
populateClasses('<?php echo $fee['education_level']; ?>');

// Form validation
document.getElementById('feeForm').addEventListener('submit', function(e) {
    const amount = document.getElementById('amount').value;
    if (amount <= 0) {
        e.preventDefault();
        alert('Amount must be greater than 0');
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
