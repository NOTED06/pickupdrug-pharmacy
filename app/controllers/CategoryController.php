<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';

class CategoryController extends BaseController {
    
    private $categoryModel;
    
    public function __construct() {
        parent::__construct();
        $this->categoryModel = new Category();
    }
    
    
    public function store() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        
        $id = $this->getPostInt('id');
        $name = $this->getPostString('name');
        $description = $this->getPostString('description');
        $status = $this->getPostInt('status', 1);
        
        
        $requiredError = $this->validateRequired(['name']);
        if ($requiredError) return $requiredError;
        
        
        if ($this->categoryModel->isDuplicate($name, $id > 0 ? $id : null)) {
            return $this->jsonResponse('failed', "Category '{$name}' already exists");
        }
        
        
        $data = [
            'name' => $name,
            'description' => $description,
            'status' => $status
        ];
        
        
        try {
            if ($id > 0) {
                $result = $this->categoryModel->update($id, $data);
                $categoryId = $id;
                $message = 'Category updated successfully';
            } else {
                $categoryId = $this->categoryModel->create($data);
                $result = $categoryId > 0;
                $message = 'Category created successfully';
            }
            
            if (!$result) {
                return $this->jsonResponse('failed', 'Failed to save category');
            }
            
            return $this->jsonResponse('success', $message, ['id' => $categoryId]);
            
        } catch (Exception $e) {
            $this->logError('Category save failed', ['error' => $e->getMessage(), 'id' => $id]);
            return $this->jsonResponse('failed', 'An error occurred while saving the category');
        }
    }
    
    
    public function destroy() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        $id = $this->getPostInt('id');
        
        if ($id <= 0) {
            return $this->jsonResponse('failed', 'Invalid category ID');
        }
        
        try {
            
            $canDelete = $this->categoryModel->canDelete($id);
            
            if (!$canDelete['can_delete']) {
                return $this->jsonResponse('failed', $canDelete['message']);
            }
            
            if ($this->categoryModel->softDelete($id)) {
                return $this->jsonResponse('success', 'Category deleted successfully');
            }
            
            return $this->jsonResponse('failed', 'Failed to delete category');
            
        } catch (Exception $e) {
            $this->logError('Category delete failed', ['error' => $e->getMessage(), 'id' => $id]);
            return $this->jsonResponse('failed', 'An error occurred while deleting the category');
        }
    }
    
    
    public function show() {
        $id = $this->getPostInt('id');
        
        if ($id <= 0) {
            return $this->jsonResponse('failed', 'Invalid category ID');
        }
        
        try {
            $category = $this->categoryModel->getWithProductCount($id);
            
            if ($category) {
                return $this->jsonResponse('success', '', ['category' => $category]);
            }
            
            return $this->jsonResponse('failed', 'Category not found');
            
        } catch (Exception $e) {
            $this->logError('Category retrieval failed', ['error' => $e->getMessage(), 'id' => $id]);
            return $this->jsonResponse('failed', 'An error occurred while retrieving the category');
        }
    }
    
    
    public function index() {
        try {
            $categories = $this->categoryModel->getAllWithProductCounts();
            return $this->jsonResponse('success', '', ['categories' => $categories]);
            
        } catch (Exception $e) {
            $this->logError('Get categories failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse('failed', 'An error occurred while retrieving categories');
        }
    }
    
    
    public function getActive() {
        try {
            $categories = $this->categoryModel->getAllActive();
            return $this->jsonResponse('success', '', ['categories' => $categories]);
            
        } catch (Exception $e) {
            $this->logError('Get active categories failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse('failed', 'An error occurred while retrieving categories');
        }
    }
    
    
    public function toggleStatus() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        $id = $this->getPostInt('id');
        
        if ($id <= 0) {
            return $this->jsonResponse('failed', 'Invalid category ID');
        }
        
        try {
            if ($this->categoryModel->toggleStatus($id)) {
                return $this->jsonResponse('success', 'Category status updated successfully');
            }
            
            return $this->jsonResponse('failed', 'Failed to update category status');
            
        } catch (Exception $e) {
            $this->logError('Toggle category status failed', ['error' => $e->getMessage(), 'id' => $id]);
            return $this->jsonResponse('failed', 'An error occurred while updating the category status');
        }
    }
    
    
    public function getStatistics() {
        try {
            $stats = $this->categoryModel->getStatistics();
            return $this->jsonResponse('success', '', ['statistics' => $stats]);
            
        } catch (Exception $e) {
            $this->logError('Get category statistics failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse('failed', 'An error occurred while retrieving statistics');
        }
    }
}
