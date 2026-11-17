<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class UserController extends BaseController {
    
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        
        $db = new DBConnection();
        $this->userModel = new User($db->conn);
    }
    
    
    public function store() {
        
        if (!$this->validateCsrf()) {
            return $this->jsonResponse('failed', 'Invalid security token. Please refresh and try again.');
        }
        
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse('failed', 'Unauthorized access');
        }
        
        try {
            $id = $this->getPostInt('id');
            $username = $this->getPostString('username');
            $password = $this->getPostString('password');
            $firstname = $this->getPostString('firstname');
            $lastname = $this->getPostString('lastname');
            $type = $this->getPostInt('type');
            
            
            if (empty($username) || empty($firstname) || empty($lastname)) {
                return $this->jsonResponse('failed', 'Username, first name, and last name are required');
            }
            
            
            $data = [
                'username' => $username,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'type' => $type
            ];
            
            if (!empty($password)) {
                $data['password'] = $password;
            }
            
            
            if (!empty($_FILES['img']['tmp_name'])) {
                $avatarPath = $this->handleAvatarUpload($id ?: null);
                if ($avatarPath) {
                    $data['avatar'] = $avatarPath;
                }
            }
            
            
            if ($id > 0) {
                $result = $this->userModel->update($id, $data);
                
                if ($result) {
                    
                    if (isset($_SESSION['userdata']['id']) && $_SESSION['userdata']['id'] == $id) {
                        $_SESSION['userdata']['username'] = $username;
                        $_SESSION['userdata']['firstname'] = $firstname;
                        $_SESSION['userdata']['lastname'] = $lastname;
                    }
                    
                    return $this->jsonResponse('success', 'User successfully updated!');
                } else {
                    return $this->jsonResponse('failed', 'Failed to update user');
                }
            } else {
                $userId = $this->userModel->create($data);
                
                if ($userId) {
                    return $this->jsonResponse('success', 'User successfully created!', ['user_id' => $userId]);
                } else {
                    return $this->jsonResponse('failed', 'Failed to create user');
                }
            }
            
        } catch (Exception $e) {
            $this->logError('UserController::store', $e);
            return $this->jsonResponse('failed', 'An error occurred while saving the user');
        }
    }
    
    
    public function destroy() {
        
        if (!$this->validateCsrf()) {
            return $this->jsonResponse('failed', 'Invalid security token');
        }
        
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse('failed', 'Unauthorized access');
        }
        
        try {
            $id = $this->getPostInt('id');
            
            if (empty($id)) {
                return $this->jsonResponse('failed', 'User ID is required');
            }
            
            
            if (isset($_SESSION['userdata']['id']) && $_SESSION['userdata']['id'] == $id) {
                return $this->jsonResponse('failed', 'Cannot delete your own account');
            }
            
            $result = $this->userModel->delete($id);
            
            if ($result) {
                return $this->jsonResponse('success', 'User successfully deleted');
            } else {
                return $this->jsonResponse('failed', 'Failed to delete user');
            }
            
        } catch (Exception $e) {
            $this->logError('UserController::destroy', $e);
            return $this->jsonResponse('failed', 'An error occurred while deleting the user');
        }
    }
    
    
    public function customers() {
        try {
            $status = isset($_GET['status']) ? (int)$_GET['status'] : null;
            $customers = $this->userModel->getAllCustomers($status);
            
            return $this->jsonResponse('success', 'Customers retrieved successfully', [
                'data' => $customers,
                'count' => count($customers)
            ]);
            
        } catch (Exception $e) {
            $this->logError('UserController::customers', $e);
            return $this->jsonResponse('failed', 'Error retrieving customers');
        }
    }
    
    
    public function customerDetails() {
        
        if (!$this->validateCsrf()) {
            return $this->jsonResponse('failed', 'Invalid security token');
        }
        
        try {
            $id = $this->getPostInt('customer_id');
            
            if (empty($id)) {
                return $this->jsonResponse('failed', 'Customer ID is required');
            }
            
            $customer = $this->userModel->getCustomerById($id);
            
            if ($customer) {
                return $this->jsonResponse('success', 'Customer retrieved successfully', $customer);
            } else {
                return $this->jsonResponse('failed', 'Customer not found');
            }
            
        } catch (Exception $e) {
            $this->logError('UserController::customerDetails', $e);
            return $this->jsonResponse('failed', 'Error retrieving customer details');
        }
    }
    
    
    public function deleteCustomer() {
        
        if (!$this->validateCsrf()) {
            return $this->jsonResponse('failed', 'Invalid security token');
        }
        
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse('failed', 'Unauthorized access');
        }
        
        try {
            $id = $this->getPostInt('id');
            
            if (empty($id)) {
                return $this->jsonResponse('failed', 'Customer ID is required');
            }
            
            $result = $this->userModel->deleteCustomer($id);
            
            if ($result) {
                return $this->jsonResponse('success', 'Customer successfully deleted');
            } else {
                return $this->jsonResponse('failed', 'Failed to delete customer');
            }
            
        } catch (Exception $e) {
            $this->logError('UserController::deleteCustomer', $e);
            return $this->jsonResponse('failed', $e->getMessage());
        }
    }
    
    
    public function deactivateCustomer() {
        
        if (!$this->validateCsrf()) {
            return $this->jsonResponse('failed', 'Invalid security token');
        }
        
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse('failed', 'Unauthorized access');
        }
        
        try {
            $id = $this->getPostInt('id');
            
            if (empty($id)) {
                return $this->jsonResponse('failed', 'Customer ID is required');
            }
            
            $result = $this->userModel->toggleCustomerStatus($id, User::STATUS_INACTIVE);
            
            if ($result) {
                return $this->jsonResponse('success', 'Customer deactivated successfully');
            } else {
                return $this->jsonResponse('failed', 'Failed to deactivate customer');
            }
            
        } catch (Exception $e) {
            $this->logError('UserController::deactivateCustomer', $e);
            return $this->jsonResponse('failed', 'Error deactivating customer');
        }
    }
    
    
    public function reactivateCustomer() {
        
        if (!$this->validateCsrf()) {
            return $this->jsonResponse('failed', 'Invalid security token');
        }
        
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse('failed', 'Unauthorized access');
        }
        
        try {
            $id = $this->getPostInt('id');
            
            if (empty($id)) {
                return $this->jsonResponse('failed', 'Customer ID is required');
            }
            
            $result = $this->userModel->toggleCustomerStatus($id, User::STATUS_ACTIVE);
            
            if ($result) {
                return $this->jsonResponse('success', 'Customer reactivated successfully');
            } else {
                return $this->jsonResponse('failed', 'Failed to reactivate customer');
            }
            
        } catch (Exception $e) {
            $this->logError('UserController::reactivateCustomer', $e);
            return $this->jsonResponse('failed', 'Error reactivating customer');
        }
    }
    
    
    private function handleAvatarUpload($userId = null) {
        try {
            $uploadDir = base_app . 'uploads/avatars/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $_FILES['img']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                return null;
            }
            
            $fileName = ($userId ?: uniqid()) . '.png';
            $filePath = $uploadDir . $fileName;
            
            
            if ($userId && file_exists($uploadDir . $userId . '.*')) {
                foreach (glob($uploadDir . $userId . '.*') as $oldFile) {
                    unlink($oldFile);
                }
            }
            
            if (move_uploaded_file($_FILES['img']['tmp_name'], $filePath)) {
                return 'uploads/avatars/' . $fileName . '?v=' . time();
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Avatar upload error: " . $e->getMessage());
            return null;
        }
    }
}
