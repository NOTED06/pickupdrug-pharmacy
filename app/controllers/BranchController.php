<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Branch.php';

class BranchController extends BaseController {
    
    private $branchModel;
    
    public function __construct() {
        parent::__construct();
        
        $db = new DBConnection();
        $this->branchModel = new Branch($db->conn);
    }
    
    
    public function store() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        try {
            
            $id = $this->getPostInt('id');
            $branchName = $this->getPostString('branch_name');
            $branchAddress = $this->getPostString('branch_address');
            $branchPhone = $this->getPostString('branch_phone');
            $branchEmail = $this->getPostString('branch_email');
            $managerName = $this->getPostString('manager_name');
            $operatingHours = $_POST['operating_hours'] ?? null;
            $status = $this->getPostInt('status', 1);
            
            
            if (empty($branchName) || empty($branchAddress)) {
                return $this->jsonResponse('failed', 'Branch name and address are required fields');
            }
            
            
            $data = [
                'branch_name' => $branchName,
                'branch_address' => $branchAddress,
                'branch_phone' => $branchPhone,
                'branch_email' => $branchEmail,
                'manager_name' => $managerName,
                'operating_hours' => $operatingHours,
                'status' => $status
            ];
            
            
            if ($id > 0) {
                
                $result = $this->branchModel->update($id, $data);
                
                if ($result) {
                    return $this->jsonResponse('success', 'Branch successfully updated!', [
                        'branch_id' => $id
                    ]);
                } else {
                    return $this->jsonResponse('failed', 'Failed to update branch. Please check your input and try again.');
                }
                
            } else {
                
                $branchId = $this->branchModel->create($data);
                
                if ($branchId) {
                    return $this->jsonResponse('success', 'Branch successfully created!', [
                        'branch_id' => $branchId
                    ]);
                } else {
                    return $this->jsonResponse('failed', 'Failed to create branch. Please check your input and try again.');
                }
            }
            
        } catch (Exception $e) {
            $this->logError('BranchController::store', $e);
            return $this->jsonResponse('failed', 'An unexpected error occurred while saving the branch.');
        }
    }
    
    
    public function show() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        try {
            $id = $this->getPostInt('id');
            
            if (empty($id)) {
                return $this->jsonResponse('failed', 'Branch ID is required');
            }
            
            $branch = $this->branchModel->getById($id);
            
            if ($branch) {
                return $this->jsonResponse('success', 'Branch retrieved successfully', $branch);
            } else {
                return $this->jsonResponse('failed', 'Branch not found');
            }
            
        } catch (Exception $e) {
            $this->logError('BranchController::show', $e);
            return $this->jsonResponse('failed', 'Error retrieving branch information');
        }
    }
    
    
    public function index() {
        try {
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] == '1';
            
            $branches = $this->branchModel->getAll($activeOnly);
            
            return $this->jsonResponse('success', 'Branches retrieved successfully', [
                'data' => $branches,
                'count' => count($branches)
            ]);
            
        } catch (Exception $e) {
            $this->logError('BranchController::index', $e);
            return $this->jsonResponse('failed', 'Error retrieving branches list');
        }
    }
    
    
    public function active() {
        try {
            $branches = $this->branchModel->getActive();
            
            return $this->jsonResponse('success', 'Active branches retrieved successfully', [
                'data' => $branches,
                'count' => count($branches)
            ]);
            
        } catch (Exception $e) {
            $this->logError('BranchController::active', $e);
            return $this->jsonResponse('failed', 'Error retrieving active branches');
        }
    }
    
    
    public function toggle() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        try {
            $id = $this->getPostInt('id');
            $status = $this->getPostInt('status');
            
            if (empty($id) || !isset($_POST['status'])) {
                return $this->jsonResponse('failed', 'Branch ID and status are required');
            }
            
            
            if ($status !== Branch::STATUS_INACTIVE && $status !== Branch::STATUS_ACTIVE) {
                return $this->jsonResponse('failed', 'Invalid status value provided');
            }
            
            
            $branch = $this->branchModel->getById($id);
            if (!$branch) {
                return $this->jsonResponse('failed', 'Branch not found');
            }
            
            $result = $this->branchModel->toggleStatus($id, $status);
            
            if ($result) {
                $action = $status == Branch::STATUS_ACTIVE ? 'activated' : 'deactivated';
                return $this->jsonResponse('success', "Branch '{$branch['branch_name']}' has been successfully $action!");
            } else {
                return $this->jsonResponse('failed', 'Failed to update branch status');
            }
            
        } catch (Exception $e) {
            $this->logError('BranchController::toggle', $e);
            return $this->jsonResponse('failed', 'An error occurred while updating branch status');
        }
    }
    
    
    public function destroy() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        try {
            $id = $this->getPostInt('id');
            
            if (empty($id)) {
                return $this->jsonResponse('failed', 'Branch ID is required');
            }
            
            
            $branch = $this->branchModel->getById($id);
            if (!$branch) {
                return $this->jsonResponse('failed', 'Branch not found');
            }
            
            $result = $this->branchModel->delete($id);
            
            if ($result) {
                return $this->jsonResponse('success', "Branch '{$branch['branch_name']}' has been successfully deleted!");
            } else {
                return $this->jsonResponse('failed', 'Failed to delete branch. Please ensure no orders or products are associated with this branch.');
            }
            
        } catch (Exception $e) {
            $this->logError('BranchController::destroy', $e);
            return $this->jsonResponse('failed', 'An error occurred while deleting the branch');
        }
    }
    
    
    public function statistics() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        try {
            $branchId = $this->getPostInt('branch_id');
            
            if (empty($branchId)) {
                return $this->jsonResponse('failed', 'Branch ID is required');
            }
            
            $stats = $this->branchModel->getStatistics($branchId);
            
            if ($stats !== null) {
                return $this->jsonResponse('success', 'Branch statistics retrieved successfully', $stats);
            } else {
                return $this->jsonResponse('failed', 'Branch not found or error retrieving statistics');
            }
            
        } catch (Exception $e) {
            $this->logError('BranchController::statistics', $e);
            return $this->jsonResponse('failed', 'Error retrieving branch statistics');
        }
    }
    
    
    public function canAcceptOrders() {
        try {
            $branchId = $this->getPostInt('branch_id');
            
            if (empty($branchId)) {
                return $this->jsonResponse('failed', 'Branch ID is required');
            }
            
            $result = $this->branchModel->canAcceptOrders($branchId);
            
            if ($result['can_accept']) {
                return $this->jsonResponse('success', 'Branch can accept orders', $result);
            } else {
                return $this->jsonResponse('failed', $result['reason'], $result);
            }
            
        } catch (Exception $e) {
            $this->logError('BranchController::canAcceptOrders', $e);
            return $this->jsonResponse('failed', 'Error checking branch availability');
        }
    }
}
