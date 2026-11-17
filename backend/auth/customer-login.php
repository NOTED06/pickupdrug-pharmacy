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

if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$password = $input['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    
    $db = new Database();
    $conn = $db->getConnection();

    
    $stmt = $conn->prepare("
        SELECT id, email, password, firstname, middlename, lastname, contact, avatar
        FROM customer_list
        WHERE email = :email
        LIMIT 1
    ");

    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    
    if (!$customer) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }

    
    $passwordValid = false;
    $storedPassword = $customer['password'];
    
    if (strlen($storedPassword) === 32 && ctype_xdigit($storedPassword)) {
        
        $passwordValid = (md5($password) === $storedPassword);
        
        
        if ($passwordValid) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $updateStmt = $conn->prepare("UPDATE customer_list SET password = :password WHERE id = :id");
            $updateStmt->bindParam(':password', $newHash);
            $updateStmt->bindParam(':id', $customer['id']);
            $updateStmt->execute();
        }
    } else {
        
        $passwordValid = password_verify($password, $storedPassword);
    }

    if (!$passwordValid) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }

    
    session_regenerate_id(true);
    $token = bin2hex(random_bytes(32));

    
    
    foreach($customer as $k => $v){
        if($k !== 'password'){
            $_SESSION['userdata'][$k] = $v;
        }
    }
    $_SESSION['userdata']['login_type'] = 2; 
    
    
    $_SESSION['customer_logged_in'] = true;
    $_SESSION['customer_id'] = $customer['id'];
    $_SESSION['customer_email'] = $customer['email'];
    $_SESSION['customer_firstname'] = $customer['firstname'];
    $_SESSION['customer_lastname'] = $customer['lastname'];
    $_SESSION['customer_picture'] = $customer['avatar'];
    $_SESSION['customer_token'] = $token;
    $_SESSION['login_type'] = 2;

    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => '/pickupdrug/',
        'user' => [
            'id' => $customer['id'],
            'email' => $customer['email'],
            'firstname' => $customer['firstname'],
            'lastname' => $customer['lastname'],
            'picture' => $customer['avatar']
        ],
        'token' => $token
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?>
