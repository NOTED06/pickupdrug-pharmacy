<?php
require_once('../config.php');

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT image_path FROM customer_prescriptions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result && $row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'image_path' => $row['image_path']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Prescription not found']);
}
$stmt->close();
?>
