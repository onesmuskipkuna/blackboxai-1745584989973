<?php
$page_title = 'Add Fee Structure';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $education_level = sanitize($_POST['education_level']);
    $class = sanitize($_POST['class']);
    $term = (int)$_POST['term'];
    $academic_year = sanitize($_POST['academic_year']);
    $fee_items = $_POST['fee_items'];
    $amounts = $_POST['amounts'];
    
    if (empty($education_level) || empty($class) || empty($term) || empty($academic_year) || empty($fee_items) || empty($amounts)) {
        $error = 'All fields are required';
    } else {
        // Begin transaction
        $conn->exec('BEGIN');
        
        try {
            // Insert each fee item
            $stmt = $conn->prepare("INSERT INTO fee_structure (education_level, class, term, academic_year, fee_item, amount) VALUES (:education_level, :class, :term, :academic_year, :fee_item, :amount)");
            
            for ($i = 0; $i < count($fee_items); $i++) {
                $fee_item = sanitize($fee_items[$i]);
                $amount = (float)$amounts[$i];
                
                if (!empty($fee_item) && $amount > 0) {
                    $stmt->bindValue(':education_level', $education_level, SQLITE3_TEXT);
                    $stmt->bindValue(':class', $class, SQLITE3_TEXT);
                    $stmt->bindValue(':term', $term, SQLITE3_INTEGER);
                    $stmt->bindValue(':academic_year', $academic_year, SQLITE3_TEXT);
                    $stmt->bindValue(':fee_item', $fee_item, SQLITE3_TEXT);
                    $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
                    $stmt->execute();
                }
            }
            
            // Commit transaction
            $conn->exec('COMMIT');
            flashMessage('success', 'Fee structure added successfully');
            redirect('index.php');
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->exec('ROLLBACK');
            $error = 'Error adding fee structure: ' . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">Add Fee Structure</h1>
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
                            <option value="primary">Primary</option>
                            <option value="junior_secondary">Junior Secondary</option>
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
                </div>

                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900">Fee Items</h3>
                    <div class="mt-4 space-y-4" id="feeItems">
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fee Item</label>
                                <input type="text" name="fee_items[]" required
                                       class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                       placeholder="e.g., Tuition Fee">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Amount (KES)</label>
                                <input type="number" name="amounts[]" required min="0" step="0.01"
                                       class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="button" onclick="addFeeItem()" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i>Add Another Fee Item
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>Save Fee Structure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const primaryClasses = ['pg', 'pp1', 'pp2', 'grade1', 'grade2', 'grade3', 'grade4', 'grade5', 'grade6'];
const secondaryClasses = ['grade7', 'grade8', 'grade9', 'grade10'];

document.getElementById('education_level').addEventListener('change', function() {
    const classSelect = document.getElementById('class');
    const selectedLevel = this.value;
    
    // Clear existing options
    classSelect.innerHTML = '<option value="">Select Class</option>';
    
    if (selectedLevel === 'primary') {
        primaryClasses.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className.charAt(0).toUpperCase() + className.slice(1);
            classSelect.appendChild(option);
        });
    } else if (selectedLevel === 'junior_secondary') {
        secondaryClasses.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className.charAt(0).toUpperCase() + className.slice(1);
            classSelect.appendChild(option);
        });
    }
});

function addFeeItem() {
    const container = document.getElementById('feeItems');
    const newItem = document.createElement('div');
    newItem.className = 'grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2';
    newItem.innerHTML = `
        <div>
            <label class="block text-sm font-medium text-gray-700">Fee Item</label>
            <input type="text" name="fee_items[]" required
                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                   placeholder="e.g., Tuition Fee">
        </div>
        <div class="relative">
            <label class="block text-sm font-medium text-gray-700">Amount (KES)</label>
            <input type="number" name="amounts[]" required min="0" step="0.01"
                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                   placeholder="0.00">
            <button type="button" onclick="removeFeeItem(this)" 
                    class="absolute right-0 top-8 text-red-600 hover:text-red-900">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(newItem);
}

function removeFeeItem(button) {
    button.closest('.grid').remove();
}

// Form validation
document.getElementById('feeForm').addEventListener('submit', function(e) {
    const feeItems = document.getElementsByName('fee_items[]');
    const amounts = document.getElementsByName('amounts[]');
    let valid = true;
    
    for (let i = 0; i < feeItems.length; i++) {
        if (!feeItems[i].value || !amounts[i].value || amounts[i].value <= 0) {
            valid = false;
            break;
        }
    }
    
    if (!valid) {
        e.preventDefault();
        alert('Please fill in all fee items and ensure amounts are greater than 0');
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
