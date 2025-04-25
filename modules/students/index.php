<?php
$page_title = 'Students Management';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle student deletion
if (isset($_POST['delete_student'])) {
    $student_id = (int)$_POST['student_id'];
    $stmt = $conn->prepare("DELETE FROM students WHERE id = :id");
    $stmt->bindValue(':id', $student_id, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        flashMessage('success', 'Student deleted successfully.');
    } else {
        flashMessage('error', 'Error deleting student.');
    }
    redirect($_SERVER['PHP_SELF']);
}

// Handle student promotion
if (isset($_POST['promote_student'])) {
    $student_id = (int)$_POST['student_id'];
    $new_class = $_POST['new_class'];
    
    $stmt = $conn->prepare("UPDATE students SET class = :class WHERE id = :id");
    $stmt->bindValue(':class', $new_class, SQLITE3_TEXT);
    $stmt->bindValue(':id', $student_id, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        flashMessage('success', 'Student promoted successfully.');
    } else {
        flashMessage('error', 'Error promoting student.');
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get filter parameters
$education_level = isset($_GET['education_level']) ? $_GET['education_level'] : '';
$class = isset($_GET['class']) ? $_GET['class'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT * FROM students WHERE 1=1";
if ($education_level) {
    $query .= " AND education_level = '" . $db->escape($education_level) . "'";
}
if ($class) {
    $query .= " AND class = '" . $db->escape($class) . "'";
}
if ($search) {
    $search = $db->escape($search);
    $query .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR admission_number LIKE '%$search%')";
}
$query .= " ORDER BY admission_number ASC";

$result = $conn->query($query);
$students = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $students[] = $row;
}

require_once '../../includes/header.php';
require_once '../../includes/navigation.php';
?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-gray-900">Students Management</h1>
            <a href="add.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>Add New Student
            </a>
        </div>

        <!-- Filters -->
        <div class="mt-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
            <form method="GET" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                <div>
                    <label for="education_level" class="block text-sm font-medium text-gray-700">Education Level</label>
                    <select id="education_level" name="education_level" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Levels</option>
                        <option value="primary" <?php echo $education_level === 'primary' ? 'selected' : ''; ?>>Primary</option>
                        <option value="junior_secondary" <?php echo $education_level === 'junior_secondary' ? 'selected' : ''; ?>>Junior Secondary</option>
                    </select>
                </div>

                <div>
                    <label for="class" class="block text-sm font-medium text-gray-700">Class</label>
                    <select id="class" name="class" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Classes</option>
                        <!-- Primary Classes -->
                        <optgroup label="Primary">
                            <option value="pg" <?php echo $class === 'pg' ? 'selected' : ''; ?>>PG</option>
                            <option value="pp1" <?php echo $class === 'pp1' ? 'selected' : ''; ?>>PP1</option>
                            <option value="pp2" <?php echo $class === 'pp2' ? 'selected' : ''; ?>>PP2</option>
                            <option value="grade1" <?php echo $class === 'grade1' ? 'selected' : ''; ?>>Grade 1</option>
                            <option value="grade2" <?php echo $class === 'grade2' ? 'selected' : ''; ?>>Grade 2</option>
                            <option value="grade3" <?php echo $class === 'grade3' ? 'selected' : ''; ?>>Grade 3</option>
                            <option value="grade4" <?php echo $class === 'grade4' ? 'selected' : ''; ?>>Grade 4</option>
                            <option value="grade5" <?php echo $class === 'grade5' ? 'selected' : ''; ?>>Grade 5</option>
                            <option value="grade6" <?php echo $class === 'grade6' ? 'selected' : ''; ?>>Grade 6</option>
                        </optgroup>
                        <!-- Junior Secondary Classes -->
                        <optgroup label="Junior Secondary">
                            <option value="grade7" <?php echo $class === 'grade7' ? 'selected' : ''; ?>>Grade 7</option>
                            <option value="grade8" <?php echo $class === 'grade8' ? 'selected' : ''; ?>>Grade 8</option>
                            <option value="grade9" <?php echo $class === 'grade9' ? 'selected' : ''; ?>>Grade 9</option>
                            <option value="grade10" <?php echo $class === 'grade10' ? 'selected' : ''; ?>>Grade 10</option>
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                           placeholder="Name or Admission Number">
                </div>

                <div class="mt-6 sm:mt-0">
                    <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Students Table -->
        <div class="mt-6 flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Admission No.
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Guardian
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Phone
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($student['admission_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($student['guardian_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($student['phone_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars(ucfirst($student['class'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $student['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="edit.php?id=<?php echo $student['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="openPromoteModal(<?php echo $student['id']; ?>, '<?php echo $student['class']; ?>')" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-level-up-alt"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $student['id']; ?>)" class="text-red-600 hover:text-red-900">
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

<!-- Promote Student Modal -->
<div id="promoteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="promoteForm" method="POST">
                <input type="hidden" name="promote_student" value="1">
                <input type="hidden" name="student_id" id="promoteStudentId">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Promote Student
                            </h3>
                            <div class="mt-4">
                                <label for="new_class" class="block text-sm font-medium text-gray-700">New Class</label>
                                <select id="new_class" name="new_class" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <!-- Options will be populated by JavaScript -->
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Promote
                    </button>
                    <button type="button" onclick="closePromoteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const primaryClasses = ['pg', 'pp1', 'pp2', 'grade1', 'grade2', 'grade3', 'grade4', 'grade5', 'grade6'];
const secondaryClasses = ['grade7', 'grade8', 'grade9', 'grade10'];

function getNextClass(currentClass) {
    if (primaryClasses.includes(currentClass)) {
        const currentIndex = primaryClasses.indexOf(currentClass);
        if (currentIndex < primaryClasses.length - 1) {
            return primaryClasses[currentIndex + 1];
        } else {
            return secondaryClasses[0]; // Move to first secondary class
        }
    } else if (secondaryClasses.includes(currentClass)) {
        const currentIndex = secondaryClasses.indexOf(currentClass);
        if (currentIndex < secondaryClasses.length - 1) {
            return secondaryClasses[currentIndex + 1];
        }
    }
    return null;
}

function openPromoteModal(studentId, currentClass) {
    const modal = document.getElementById('promoteModal');
    const selectElement = document.getElementById('new_class');
    const nextClass = getNextClass(currentClass);
    
    document.getElementById('promoteStudentId').value = studentId;
    
    // Clear existing options
    selectElement.innerHTML = '';
    
    if (nextClass) {
        const option = document.createElement('option');
        option.value = nextClass;
        option.textContent = nextClass.charAt(0).toUpperCase() + nextClass.slice(1);
        selectElement.appendChild(option);
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No higher class available';
        selectElement.appendChild(option);
    }
    
    modal.classList.remove('hidden');
}

function closePromoteModal() {
    document.getElementById('promoteModal').classList.add('hidden');
}

function confirmDelete(studentId) {
    if (confirm('Are you sure you want to delete this student?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_student" value="1">
            <input type="hidden" name="student_id" value="${studentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Update class options based on education level
document.getElementById('education_level').addEventListener('change', function() {
    const classSelect = document.getElementById('class');
    const selectedLevel = this.value;
    
    // Clear existing options
    classSelect.innerHTML = '<option value="">All Classes</option>';
    
    if (selectedLevel === 'primary') {
        const primaryGroup = document.createElement('optgroup');
        primaryGroup.label = 'Primary';
        primaryClasses.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className.charAt(0).toUpperCase() + className.slice(1);
            primaryGroup.appendChild(option);
        });
        classSelect.appendChild(primaryGroup);
    } else if (selectedLevel === 'junior_secondary') {
        const secondaryGroup = document.createElement('optgroup');
        secondaryGroup.label = 'Junior Secondary';
        secondaryClasses.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className.charAt(0).toUpperCase() + className.slice(1);
            secondaryGroup.appendChild(option);
        });
        classSelect.appendChild(secondaryGroup);
    } else {
        // Add both groups
        const primaryGroup = document.createElement('optgroup');
        primaryGroup.label = 'Primary';
        primaryClasses.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className.charAt(0).toUpperCase() + className.slice(1);
            primaryGroup.appendChild(option);
        });
        
        const secondaryGroup = document.createElement('optgroup');
        secondaryGroup.label = 'Junior Secondary';
        secondaryClasses.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className.charAt(0).toUpperCase() + className.slice(1);
            secondaryGroup.appendChild(option);
        });
        
        classSelect.appendChild(primaryGroup);
        classSelect.appendChild(secondaryGroup);
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
