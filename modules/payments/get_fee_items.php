<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Require login
requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$conn = $db->getConnection();

// Get invoice ID
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

if (!$invoice_id) {
    echo json_encode(['error' => 'Invalid invoice ID']);
    exit;
}

// Get invoice items with their balances
$stmt = $conn->prepare("
    SELECT 
        ii.id,
        fs.fee_item,
        ii.amount as original_amount,
        COALESCE(SUM(pi.amount), 0) as paid_amount,
        (ii.amount - COALESCE(SUM(pi.amount), 0)) as balance
    FROM invoice_items ii
    JOIN fee_structure fs ON ii.fee_structure_id = fs.id
    LEFT JOIN payment_items pi ON ii.id = pi.invoice_item_id
    WHERE ii.invoice_id = ?
    GROUP BY ii.id
    HAVING balance > 0
    ORDER BY fs.fee_item
");

$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$fee_items = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($fee_items);
?>
