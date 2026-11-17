<?php

require_once(__DIR__ . '/../../config.php');

header('Content-Type: application/json');

if(!isset($_SESSION['userdata']) || !in_array($_SESSION['userdata']['type'], [1, 5])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if(!isset($_POST['prescription_id'])) {
    echo json_encode(['success' => false, 'message' => 'Prescription ID required']);
    exit;
}

$prescription_id = intval($_POST['prescription_id']);

$stmt = $conn->prepare("SELECT image_path FROM customer_prescriptions WHERE id = ?");
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$result = $stmt->get_result();
$prescription = $result->fetch_assoc();
$stmt->close();

if(!$prescription) {
    echo json_encode(['success' => false, 'message' => 'Prescription not found']);
    exit;
}

$image_path = $prescription['image_path'];
$full_path = __DIR__ . '/../../' . $image_path;

if(!file_exists($full_path)) {
    echo json_encode(['success' => false, 'message' => 'Prescription image file not found']);
    exit;
}

$mime_type = mime_content_type($full_path);
if(!in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/png'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Cannot analyze PDF files. Please add medications manually.',
        'medications' => []
    ]);
    exit;
}

try {
    require_once(__DIR__ . '/../../Chatbot/GeminiAI.php');
    
    
    $imageData = base64_encode(file_get_contents($full_path));
    
    
    $gemini = new GeminiAI($conn, []);
    
    
    $result = $gemini->extractMedicationSchedule($imageData, $mime_type);
    
    if($result['success']) {
        echo json_encode([
            'success' => true,
            'medications' => $result['medications'],
            'count' => count($result['medications'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Could not extract medication details',
            'medications' => []
        ]);
    }
    
} catch(Exception $e) {
    error_log('Medication extraction error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error processing prescription',
        'medications' => []
    ]);
}
?>
