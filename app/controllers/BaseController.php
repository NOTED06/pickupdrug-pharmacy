<?php

require_once(__DIR__ . '/../../config.php');

abstract class BaseController {
    
    
    protected $conn;
    
    
    protected $settings;
    
    
    public function __construct() {
        global $_settings;
        $this->settings = $_settings;
        
        
        require_once(__DIR__ . '/../../classes/DBConnection.php');
        $db = new DBConnection();
        $this->conn = $db->conn;
    }
    
    
    protected function jsonResponse($status, $message, $data = []) {
        $response = [
            'status' => $status,
            'msg' => $message
        ];
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        return json_encode($response);
    }
    
    
    protected function validateCsrf() {
        require_once(__DIR__ . '/../helpers/SecurityHelper.php');
        if (!SecurityHelper::validateCsrf(false)) { 
            return $this->jsonResponse('failed', 'Invalid CSRF token. Please refresh and try again.');
        }
        return null;
    }
    
    
    protected function getPost($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
    
    
    protected function getPostInt($key, $default = 0) {
        return isset($_POST[$key]) ? intval($_POST[$key]) : $default;
    }
    
    
    protected function getPostFloat($key, $default = 0.0) {
        return isset($_POST[$key]) ? floatval($_POST[$key]) : $default;
    }
    
    
    protected function getPostString($key, $default = '') {
        return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
    }
    
    
    protected function getQuery($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    
    protected function getQueryInt($key, $default = 0) {
        return isset($_GET[$key]) ? intval($_GET[$key]) : $default;
    }
    
    
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    
    protected function validateRequired($requiredFields) {
        foreach ($requiredFields as $field) {
            $value = $this->getPostString($field);
            if (empty($value)) {
                $fieldName = ucfirst(str_replace('_', ' ', $field));
                return $this->jsonResponse('failed', "{$fieldName} is required.");
            }
        }
        return null;
    }
    
    
    protected function captureError() {
        if (!$this->conn->error) {
            return false;
        }
        
        $resp['status'] = 'failed';
        $resp['error'] = $this->conn->error;
        return json_encode($resp);
    }
    
    
    protected function logError($message, $context = []) {
        $logMessage = date('Y-m-d H:i:s') . " - {$message}";
        if (!empty($context)) {
            $logMessage .= " - Context: " . json_encode($context);
        }
        error_log($logMessage . "\n", 3, __DIR__ . '/../../logs/controller_errors.log');
    }
    
    
    protected function isAuthenticated() {
        return isset($_SESSION['userdata']) && !empty($_SESSION['userdata']['id']);
    }
    
    
    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse('failed', 'Authentication required. Please log in.');
        }
        return null;
    }
    
    
    protected function getUserId() {
        return $_SESSION['userdata']['id'] ?? null;
    }
    
    
    protected function getUserType() {
        return $_SESSION['userdata']['type'] ?? null;
    }
    
    
    protected function isAdmin() {
        return $this->getUserType() === 1;
    }
    
    
    protected function requireAdmin() {
        $authError = $this->requireAuth();
        if ($authError) return $authError;
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse('failed', 'Admin access required.');
        }
        return null;
    }
    
    
    protected function sanitizeFilename($filename) {
        
        $filename = basename($filename);
        
        
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        return $filename;
    }
    
    
    protected function validateUpload($fileKey, $allowedTypes = [], $maxSize = 2097152) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            return null; 
        }
        
        $file = $_FILES[$fileKey];
        
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'failed', 'msg' => 'File upload failed. Please try again.'];
        }
        
        
        if ($file['size'] > $maxSize) {
            $maxSizeMB = $maxSize / 1048576;
            return ['status' => 'failed', 'msg' => "File size must not exceed {$maxSizeMB}MB."];
        }
        
        
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                return ['status' => 'failed', 'msg' => 'Invalid file type.'];
            }
        }
        
        return null;
    }
}
