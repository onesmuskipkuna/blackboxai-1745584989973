<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$conn = $db->getConnection();

// Get parameters
$class = isset($_GET['class']) ? sanitize($_GET['class']) : '';
$education_level = isset($_GET['education_level']) ? sanitize($_GET['education_level']) : '';
$term = isset($_GET['term']) ? (int)$_GET['term'] : 0;
$academic_year = isset($_GET['academic_year']) ? sanitize($_GET['academic_year']) : '';

if (!$class || !$education_level || !$term || !$academic_year) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Get fee structure
$stmt = $conn->prepare("
    SELECT id, fee_item, amount 
    FROM fee_structure 
    WHERE class = :class 
    AND education_level = :education_level 
    AND term = :term 
    AND academic_year = :academic_year
    ORDER BY fee_item
");

$stmt->bindValue(':class', $class, SQLITE3_TEXT);
$stmt->bindValue(':education_level', $education_level, SQLITE3_TEXT);
$stmt->bindValue(':term', $term, SQLITE3_INTEGER);
$stmt->bindValue(':academic_year', $academic_year, SQLITE3_TEXT);
$result = $stmt->execute();

$fee_items = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $fee_items[] = $row;
}

echo json_encode($fee_items);
?>
