<?php
$page_title = 'Edit Student';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

$error = '';
$success = '';
$student = null;

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    flashMessage('error', 'Invalid student ID');
    redirect('index.php');
}

// Get student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = :id");
$stmt->bindValue(':id', $student_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$student = $result->fetchArray(SQLITE3_ASSOC);

if (!$student) {
    flashMessage('error', 'Student not found');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $guardian_name = sanitize($_POST['guardian_name']);
    $phone_number = sanitize($_POST['phone_number']);
    $education_level = sanitize($_POST['education_level']);
    $class = sanitize($_POST['class']);
    $status = sanitize($_POST['status']);
    
    if (empty($first_name) || empty($last_name) || empty($guardian_name) || empty($phone_number) || empty($education_level) || empty($class)) {
        $error = 'All fields are required';
    } else {
        // Update student
        $stmt = $conn->prepare("UPDATE students SET first_name = :first_name, last_name = :last_name, guardian_name = :guardian_name, phone_number = :phone_number, education_level = :education_level, class = :class, status = :status WHERE id = :id");
        $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
        $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
        $stmt->bindValue(':guardian_name', $guardian_name, SQLITE3_TEXT);
        $stmt->bindValue(':phone_number', $phone_number, SQLITE3_TEXT);
        $stmt->bindValue(':education_level', $education_level, SQLITE3_TEXT);
        $stmt->bindValue(':class', $class, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':id', $student_id, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            flashMessage('success', 'Student updated successfully');
            redirect('index.php');
        } else {
            $error = 'Error updating student: ' . $conn->lastErrorMsg();
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Student</h1>
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
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Admission Number</label>
                        <div class="mt-1">
                            <input type="text" value="<?php echo htmlspecialchars($student['admission_number']); ?>" disabled
                                   class="bg-gray-50 shadow-sm block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <div class="mt-1">
                            <select id="status" name="status" required
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="active" <?php echo $student['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $student['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="graduated" <?php echo $student['status'] === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                                <option value="transferred" <?php echo $student['status'] === 'transferred' ? 'selected' : ''; ?>>Transferred</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                        <div class="mt-1">
                            <input type="text" name="first_name" id="first_name" required
                                   value="<?php echo htmlspecialchars($student['first_name']); ?>"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <div class="mt-1">
                            <input type="text" name="last_name" id="last_name" required
                                   value="<?php echo htmlspecialchars($student['last_name']); ?>"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label for="guardian_name" class="block text-sm font-medium text-gray-700">Parent/Guardian Name</label>
                        <div class="mt-1">
                            <input type="text" name="guardian_name" id="guardian_name" required
                                   value="<?php echo htmlspecialchars($student['guardian_name']); ?>"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <div class="mt-1">
                            <input type="tel" name="phone_number" id="phone_number" required
                                   pattern="[0-9]{10,}"
                                   value="<?php echo htmlspecialchars($student['phone_number']); ?>"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div>
                        <label for="education_level" class="block text-sm font-medium text-gray-700">Education Level</label>
                        <div class="mt-1">
                            <select id="education_level" name="education_level" required
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="">Select Level</option>
                                <option value="primary" <?php echo $student['education_level'] === 'primary' ? 'selected' : ''; ?>>Primary</option>
                                <option value="junior_secondary" <?php echo $student['education_level'] === 'junior_secondary' ? 'selected' : ''; ?>>Junior Secondary</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="class" class="block text-sm font-medium text-gray-700">Class</label>
                        <div class="mt-1">
                            <select id="class" name="class" required
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="">Select Class</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>Update Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const primaryClasses = ['pg', 'pp1', 'pp2', 'grade1', 'grade2', 'grade3', 'grade4', 'grade5', 'grade6'];
const secondaryClasses = ['grade7', 'grade8', 'grade9', 'grade10'];
const currentClass = '<?php echo $student['class']; ?>';

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
populateClasses('<?php echo $student['education_level']; ?>');

// Phone number validation
document.getElementById('phone_number').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php require_once '../../includes/footer.php'; ?>
