<?php

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once('../../config.php');

while (ob_get_level() > 1) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => true, 'customers' => []]);
    exit;
}

$search_param = "%{$query}%";
$stmt = $conn->prepare("
    SELECT 
        id,
        CONCAT(firstname, ' ', lastname) as name,
        firstname,
        lastname,
        contact,
        email
    FROM customer_list
    WHERE CONCAT(firstname, ' ', lastname) LIKE ?
       OR email LIKE ?
       OR contact LIKE ?
    ORDER BY firstname, lastname
    LIMIT 20
");

if (!$stmt) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("sss", $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();

$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'contact' => $row['contact'] ?? '',
        'email' => $row['email'] ?? ''
    ];
}

$stmt->close();

while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode([
    'success' => true,
    'customers' => $customers
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
