<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$conn = $db->getConnection();

// Get student ID
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if (!$student_id) {
    echo json_encode(['error' => 'Invalid student ID']);
    exit;
}

// Get student's unpaid/partially paid invoices
$stmt = $conn->prepare("
    SELECT id, invoice_number, total_amount, paid_amount, balance, term, academic_year
    FROM invoices 
    WHERE student_id = ? 
    AND status != 'fully_paid'
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$invoices = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($invoices);
?>
