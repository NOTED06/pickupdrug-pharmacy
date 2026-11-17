<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json');

require_once(__DIR__ . '/../initialize.php');
require_once(__DIR__ . '/../classes/FacebookChatbot.php');

$chatbot = new FacebookChatbot();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hub_mode = $_GET['hub_mode'] ?? '';
    $hub_verify_token = $_GET['hub_verify_token'] ?? '';
    $hub_challenge = $_GET['hub_challenge'] ?? '';
    
    $result = $chatbot->verifyWebhook($hub_mode, $hub_verify_token, $hub_challenge);
    
    if ($result !== false) {
        
        header('Content-Type: text/plain');
        echo $result;
        exit;
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Verification failed']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    
    
    error_log("Facebook Webhook received: " . substr($input, 0, 500));
    
    $chatbot->processMessage($input);
    
    
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
