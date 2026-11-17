<?php

session_start();

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$required = ['firstname', 'lastname', 'email', 'password'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

$firstname = trim($input['firstname']);
$lastname = trim($input['lastname']);
$email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);
$password = $input['password'];
$contact = isset($input['contact']) ? trim($input['contact']) : '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

try {
    
    $db = new Database();
    $conn = $db->getConnection();

    
    $checkStmt = $conn->prepare("SELECT id FROM customer_list WHERE email = :email");
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered. Please login instead.']);
        exit;
    }

    
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    
    
    
    $stmt = $conn->prepare("
        INSERT INTO customer_list (firstname, middlename, lastname, gender, age, email, password, contact, date_created)
        VALUES (:firstname, '', :lastname, 'Male', 0, :email, :password, :contact, NOW())
    ");

    $stmt->bindParam(':firstname', $firstname);
    $stmt->bindParam(':lastname', $lastname);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':contact', $contact);

    $stmt->execute();
    $customerId = $conn->lastInsertId();

    
    session_regenerate_id(true);
    $token = bin2hex(random_bytes(32));

    
    $_SESSION['userdata']['id'] = $customerId;
    $_SESSION['userdata']['firstname'] = $firstname;
    $_SESSION['userdata']['lastname'] = $lastname;
    $_SESSION['userdata']['email'] = $email;
    $_SESSION['userdata']['contact'] = $contact;
    $_SESSION['userdata']['avatar'] = null;
    $_SESSION['userdata']['login_type'] = 2; 
    
    
    $_SESSION['customer_logged_in'] = true;
    $_SESSION['customer_id'] = $customerId;
    $_SESSION['customer_email'] = $email;
    $_SESSION['customer_firstname'] = $firstname;
    $_SESSION['customer_lastname'] = $lastname;
    $_SESSION['customer_picture'] = null;
    $_SESSION['customer_token'] = $token;
    $_SESSION['login_type'] = 2;

    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'redirect' => '/pickupdrug/',
        'user' => [
            'id' => $customerId,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname
        ],
        'token' => $token
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Signup Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed. Please try again later.',
        'debug' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Signup General Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred.',
        'debug' => $e->getMessage()
    ]);
}
?>
