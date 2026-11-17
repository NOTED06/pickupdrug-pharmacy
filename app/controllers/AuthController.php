<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AuthController {
    
    private $userModel;
    private $customerModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->customerModel = new Customer();
    }
    
    
    public function login() {
        if (!SecurityHelper::isPost()) {
            return $this->jsonResponse('error', 'Invalid request method');
        }
        
        
        try {
            SecurityHelper::validateCsrf();
        } catch (Exception $e) {
            return $this->jsonResponse('error', 'CSRF validation failed');
        }
        
        
        $username = SecurityHelper::sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            return $this->jsonResponse('incorrect', 'Username and Password are required');
        }
        
        
        $result = $this->userModel->authenticate($username, $password);
        
        if ($result['success']) {
            
            AuthMiddleware::login($result['user'], 1);  
            return $this->jsonResponse('success', 'Login successful');
        } else {
            return $this->jsonResponse('incorrect', $result['message']);
        }
    }
    
    
    public function loginCustomer() {
        if (!SecurityHelper::isPost()) {
            return $this->jsonResponse('error', 'Invalid request method');
        }
        
        
        try {
            SecurityHelper::validateCsrf();
        } catch (Exception $e) {
            return $this->jsonResponse('failed', 'CSRF validation failed');
        }
        
        
        $email = SecurityHelper::sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            return $this->jsonResponse('failed', 'Email and Password are required');
        }
        
        if (!ValidationHelper::email($email)) {
            return $this->jsonResponse('failed', 'Invalid email format');
        }
        
        
        $result = $this->customerModel->authenticate($email, $password);
        
        if ($result['success']) {
            
            AuthMiddleware::login($result['customer'], 2);  
            return $this->jsonResponse('success', 'Login successful');
        } else {
            return $this->jsonResponse('failed', $result['message']);
        }
    }
    
    
    public function logout() {
        $isAdmin = AuthMiddleware::isAdmin();
        AuthMiddleware::logout();
        
        
        if ($isAdmin) {
            redirect('admin/login.php');
        } else {
            redirect('index.php');
        }
    }
    
    
    public function register() {
        if (!SecurityHelper::isPost()) {
            return $this->jsonResponse('failed', 'Invalid request method');
        }
        
        
        try {
            SecurityHelper::validateCsrf();
        } catch (Exception $e) {
            return $this->jsonResponse('failed', 'CSRF validation failed');
        }
        
        
        $rules = [
            'firstname' => 'required|min:2|max:50',
            'lastname' => 'required|min:2|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'contact' => 'required|phone',
            'birthdate' => 'required|date'
        ];
        
        $errors = ValidationHelper::validate($_POST, $rules);
        
        if (ValidationHelper::hasErrors($errors)) {
            return $this->jsonResponse('failed', 'Validation failed', ['errors' => $errors]);
        }
        
        
        if ($this->customerModel->emailExists($_POST['email'])) {
            return $this->jsonResponse('failed', 'Email address already registered');
        }
        
        
        $passwordCheck = ValidationHelper::passwordStrength($_POST['password']);
        if (!$passwordCheck['valid']) {
            return $this->jsonResponse('failed', implode(', ', $passwordCheck['errors']));
        }
        
        
        if (!ValidationHelper::age($_POST['birthdate'], 18)) {
            return $this->jsonResponse('failed', 'You must be at least 18 years old to register');
        }
        
        
        $data = [
            'firstname' => SecurityHelper::sanitize($_POST['firstname']),
            'middlename' => SecurityHelper::sanitize($_POST['middlename'] ?? ''),
            'lastname' => SecurityHelper::sanitize($_POST['lastname']),
            'gender' => in_array($_POST['gender'], ['Male', 'Female']) ? $_POST['gender'] : 'Male',
            'contact' => SecurityHelper::sanitize($_POST['contact']),
            'birthdate' => $_POST['birthdate'],
            'email' => SecurityHelper::sanitizeEmail($_POST['email']),
            'password' => $_POST['password'],  
        ];
        
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleAvatarUpload($_FILES['avatar']);
            if ($uploadResult['success']) {
                $data['avatar'] = $uploadResult['path'];
            }
        }
        
        
        $customerId = $this->customerModel->createCustomer($data);
        
        if ($customerId) {
            
            $customer = $this->customerModel->getSafeCustomer($customerId);
            AuthMiddleware::login($customer, 2);
            
            return $this->jsonResponse('success', 'Registration successful');
        } else {
            return $this->jsonResponse('failed', 'Registration failed. Please try again.');
        }
    }
    
    
    public function changePassword() {
        if (!SecurityHelper::isPost()) {
            return $this->jsonResponse('failed', 'Invalid request method');
        }
        
        
        if (!AuthMiddleware::isAuthenticated()) {
            return $this->jsonResponse('failed', 'Not authenticated');
        }
        
        
        try {
            SecurityHelper::validateCsrf();
        } catch (Exception $e) {
            return $this->jsonResponse('failed', 'CSRF validation failed');
        }
        
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            return $this->jsonResponse('failed', 'All fields are required');
        }
        
        if ($newPassword !== $confirmPassword) {
            return $this->jsonResponse('failed', 'New passwords do not match');
        }
        
        
        $passwordCheck = ValidationHelper::passwordStrength($newPassword);
        if (!$passwordCheck['valid']) {
            return $this->jsonResponse('failed', implode(', ', $passwordCheck['errors']));
        }
        
        
        $userId = AuthMiddleware::getUserId();
        $isAdmin = AuthMiddleware::isAdmin();
        
        if ($isAdmin) {
            $user = $this->userModel->find($userId);
            $validOldPassword = SecurityHelper::verifyPassword($oldPassword, $user['password']);
            
            if (!$validOldPassword) {
                return $this->jsonResponse('failed', 'Current password is incorrect');
            }
            
            $this->userModel->updatePassword($userId, $newPassword);
        } else {
            $customer = $this->customerModel->find($userId);
            $validOldPassword = SecurityHelper::verifyPassword($oldPassword, $customer['password']);
            
            if (!$validOldPassword) {
                return $this->jsonResponse('failed', 'Current password is incorrect');
            }
            
            $this->customerModel->updatePassword($userId, $newPassword);
        }
        
        return $this->jsonResponse('success', 'Password changed successfully');
    }
    
    
    private function handleAvatarUpload($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024;  
        
        $validation = SecurityHelper::validateUpload($file, [
            'allowed_types' => $allowedTypes,
            'max_size' => $maxSize,
            'max_width' => 1000,
            'max_height' => 1000
        ]);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . uniqid() . '.' . $extension;
        $uploadDir = base_app . 'uploads/avatars/';
        
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'path' => 'uploads/avatars/' . $filename];
        }
        
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    
    private function jsonResponse($status, $message, $data = []) {
        $response = array_merge([
            'status' => $status,
            'msg' => $message
        ], $data);
        
        return json_encode($response);
    }
}

if (isset($_GET['f']) && SecurityHelper::isAjax()) {
    $auth = new AuthController();
    $function = $_GET['f'];
    
    if (method_exists($auth, $function)) {
        echo $auth->$function();
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid function']);
    }
}
