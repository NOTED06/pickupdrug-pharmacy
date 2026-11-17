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

require_once '../../initialize.php';
require_once '../../classes/DBConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$credential = null;

if (isset($_POST['credential']) && !empty($_POST['credential'])) {
    $credential = $_POST['credential'];
} else {
    
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $input = json_decode($rawInput, true);
        if ($input && isset($input['credential'])) {
            $credential = $input['credential'];
        }
    }
}

if (!$credential) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Google credential required']);
    exit;
}

try {
    
    
    $tokenParts = explode('.', $credential);
    
    if (count($tokenParts) !== 3) {
        throw new Exception('Invalid token format');
    }
    
    
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
    
    if (!$payload) {
        throw new Exception('Failed to decode token payload');
    }
    
    
    $email = isset($payload['email']) ? $payload['email'] : null;
    $firstname = isset($payload['given_name']) ? $payload['given_name'] : '';
    $lastname = isset($payload['family_name']) ? $payload['family_name'] : '';
    $picture = isset($payload['picture']) ? $payload['picture'] : null;
    $googleId = isset($payload['sub']) ? $payload['sub'] : null;
    
    if (!$email || !$googleId) {
        throw new Exception('Invalid token: missing required fields');
    }
    
    
    if (!preg_match('/@gmail\.com$/i', $email)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Only Gmail accounts are allowed. Please sign in with a Gmail address.'
        ]);
        exit;
    }
    
    
    $db = new DBConnection();
    $conn = $db->conn;
    
    
    $email_escaped = $conn->real_escape_string($email);
    $result = $conn->query("
        SELECT id, email, firstname, middlename, lastname, contact, avatar
        FROM customer_list
        WHERE email = '{$email_escaped}'
        LIMIT 1
    ");
    
    $customer = null;
    if ($result && $result->num_rows > 0) {
        $customer = $result->fetch_assoc();
    }
    
    $isNewUser = false;
    
    if ($customer) {
        
        $customerId = $customer['id'];
        $actionMessage = 'Welcome back! Logging you in...';
        
        
        if ($picture && $picture !== $customer['avatar']) {
            $picture_escaped = $conn->real_escape_string($picture);
            $conn->query("UPDATE customer_list SET avatar = '{$picture_escaped}' WHERE id = {$customerId}");
            $customer['avatar'] = $picture;
        }
    } else {
        
        $isNewUser = true;
        $actionMessage = 'Creating your account...';
        
        
        $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 12]);
        
        $firstname_escaped = $conn->real_escape_string($firstname);
        $lastname_escaped = $conn->real_escape_string($lastname);
        $password_escaped = $conn->real_escape_string($randomPassword);
        $picture_escaped = $picture ? "'" . $conn->real_escape_string($picture) . "'" : "''";
        
        $conn->query("
            INSERT INTO customer_list (firstname, middlename, lastname, gender, age, email, password, contact, avatar, date_created)
            VALUES ('{$firstname_escaped}', '', '{$lastname_escaped}', 'Male', 0, '{$email_escaped}', '{$password_escaped}', '', {$picture_escaped}, NOW())
        ");
        
        $customerId = $conn->insert_id;
        
        
        $result = $conn->query("
            SELECT id, email, firstname, middlename, lastname, contact, avatar
            FROM customer_list
            WHERE id = {$customerId}
            LIMIT 1
        ");
        
        if ($result && $result->num_rows > 0) {
            $customer = $result->fetch_assoc();
        }
    }
    
    
    session_regenerate_id(true);
    $token = bin2hex(random_bytes(32));
    
    
    foreach($customer as $k => $v){
        $_SESSION['userdata'][$k] = $v;
    }
    $_SESSION['userdata']['login_type'] = 2; 
    $_SESSION['userdata']['google_id'] = $googleId;
    $_SESSION['userdata']['picture'] = $picture; 
    
    
    $_SESSION['customer_logged_in'] = true;
    $_SESSION['customer_id'] = $customer['id'];
    $_SESSION['customer_email'] = $customer['email'];
    $_SESSION['customer_firstname'] = $customer['firstname'];
    $_SESSION['customer_lastname'] = $customer['lastname'];
    $_SESSION['customer_picture'] = $picture;
    $_SESSION['customer_token'] = $token;
    $_SESSION['customer_google_id'] = $googleId;
    $_SESSION['login_type'] = 2;
    
    
    echo json_encode([
        'success' => true,
        'message' => $isNewUser ? 'Account created successfully! Welcome to PharmaCare!' : 'Welcome back!',
        'isNewUser' => $isNewUser,
        'redirect' => '/pickupdrug/',
        'user' => [
            'id' => $customer['id'],
            'email' => $customer['email'],
            'firstname' => $customer['firstname'],
            'lastname' => $customer['lastname'],
            'picture' => $picture
        ],
        'token' => $token
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('Google Login Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Authentication failed. Please try again.',
        'debug' => $e->getMessage()
    ]);
}
?>
