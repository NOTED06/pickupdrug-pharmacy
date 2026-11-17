<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';

class ProductController extends BaseController {
    
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }
    
    
    public function store() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        
        $id = $this->getPostInt('id');
        $brand = $this->getPostString('brand');
        $name = $this->getPostString('name');
        $dose = $this->getPostString('dose');
        $categoryId = $this->getPostInt('category_id');
        $description = $this->getPostString('description');
        $price = $this->getPostFloat('price');
        $unit = $this->getPostString('unit');
        $formId = $this->getPostInt('form_id');
        $isPrescription = $this->getPostInt('is_prescription', 0);
        $fdaApproved = $this->getPostInt('fda_approved', 1);
        
        
        $requiredError = $this->validateRequired(['brand', 'name', 'dose', 'category_id', 'description', 'unit']);
        if ($requiredError) return $requiredError;
        
        
        if ($price <= 0) {
            return $this->jsonResponse('failed', 'Price must be greater than 0');
        }
        
        
        if (!$this->productModel->categoryExists($categoryId)) {
            return $this->jsonResponse('failed', 'Selected category does not exist');
        }
        
        
        if ($formId > 0 && !$this->productModel->formExists($formId)) {
            return $this->jsonResponse('failed', 'Selected form does not exist or is not available');
        }
        
        
        if ($this->productModel->isDuplicate($brand, $name, $id > 0 ? $id : null)) {
            return $this->jsonResponse('failed', "Product '{$brand} - {$name}' already exists");
        }
        
        
        $branchIds = [];
        if (isset($_POST['branch_ids']) && is_array($_POST['branch_ids'])) {
            $branchIds = array_map('intval', $_POST['branch_ids']);
            $branchIds = array_filter($branchIds); 
            
            
            if (!empty($branchIds)) {
                $placeholders = implode(',', array_fill(0, count($branchIds), '?'));
                $sql = "SELECT COUNT(*) as count FROM branch_list WHERE id IN ({$placeholders}) AND status = 1";
                $result = $this->productModel->db->selectOne($sql, $branchIds);
                
                if (!$result || $result['count'] != count($branchIds)) {
                    return $this->jsonResponse('failed', 'One or more selected branches are not valid or not active');
                }
            }
        }
        
        
        $data = [
            'brand' => $brand,
            'name' => $name,
            'dose' => $dose,
            'category_id' => $categoryId,
            'description' => $description,
            'price' => $price,
            'unit' => $unit,
            'form_id' => $formId > 0 ? $formId : null,
            'is_prescription' => $isPrescription,
            'fda_approved' => $fdaApproved
        ];
        
        
        try {
            if ($id > 0) {
                $result = $this->productModel->update($id, $data);
                $productId = $id;
                $isNew = false;
            } else {
                $productId = $this->productModel->create($data);
                $result = $productId > 0;
                $isNew = true;
            }
            
            if (!$result) {
                return $this->jsonResponse('failed', 'Failed to save product');
            }
            
            
            $this->productModel->setBranches($productId, $branchIds);
            
            
            $imageMessage = '';
            if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleImageUpload($productId, $id > 0);
                if ($uploadResult['success']) {
                    $imageMessage = ' Image uploaded successfully.';
                } else {
                    $imageMessage = ' ' . $uploadResult['message'];
                }
            }
            
            
            $branchInfo = empty($branchIds) 
                ? '(available at all branches)' 
                : '(available at ' . count($branchIds) . ' branch' . (count($branchIds) > 1 ? 'es' : '') . ')';
            
            $message = $isNew 
                ? "Product has been added successfully {$branchInfo}{$imageMessage}"
                : "Product has been updated successfully {$branchInfo}{$imageMessage}";
            
            return $this->jsonResponse('success', $message, ['pid' => $productId]);
            
        } catch (Exception $e) {
            $this->logError('Product save failed', ['error' => $e->getMessage(), 'id' => $id]);
            return $this->jsonResponse('failed', 'An error occurred while saving the product');
        }
    }
    
    
    public function destroy() {
        
        $csrfError = $this->validateCsrf();
        if ($csrfError) return $csrfError;
        
        $id = $this->getPostInt('id');
        
        if ($id <= 0) {
            return $this->jsonResponse('failed', 'Invalid product ID');
        }
        
        try {
            if ($this->productModel->softDelete($id)) {
                return $this->jsonResponse('success', 'Product deleted successfully');
            }
            
            return $this->jsonResponse('failed', 'Failed to delete product');
            
        } catch (Exception $e) {
            $this->logError('Product delete failed', ['error' => $e->getMessage(), 'id' => $id]);
            return $this->jsonResponse('failed', 'An error occurred while deleting the product');
        }
    }
    
    
    public function show() {
        $id = $this->getPostInt('id');
        
        if ($id <= 0) {
            return $this->jsonResponse('failed', 'Invalid product ID');
        }
        
        try {
            $product = $this->productModel->getWithCategory($id);
            
            if ($product) {
                
                $branchIds = $this->productModel->getBranchIds($id);
                $product['branch_ids'] = $branchIds;
                
                return $this->jsonResponse('success', '', ['product' => $product]);
            }
            
            return $this->jsonResponse('failed', 'Product not found');
            
        } catch (Exception $e) {
            $this->logError('Product retrieval failed', ['error' => $e->getMessage(), 'id' => $id]);
            return $this->jsonResponse('failed', 'An error occurred while retrieving the product');
        }
    }
    
    
    public function getByBranch() {
        $branchId = $this->getPostInt('branch_id');
        
        if ($branchId <= 0) {
            return $this->jsonResponse('failed', 'Invalid branch ID');
        }
        
        try {
            $products = $this->productModel->getByBranch($branchId);
            return $this->jsonResponse('success', '', ['products' => $products]);
            
        } catch (Exception $e) {
            $this->logError('Get products by branch failed', ['error' => $e->getMessage(), 'branch_id' => $branchId]);
            return $this->jsonResponse('failed', 'An error occurred while retrieving products');
        }
    }
    
    
    public function checkAvailability() {
        $productId = $this->getPostInt('product_id');
        $branchId = $this->getPostInt('branch_id');
        
        if ($productId <= 0) {
            return $this->jsonResponse('failed', 'Invalid product ID');
        }
        
        try {
            $stock = $this->productModel->getAvailableStock($productId);
            $availableAtBranch = $branchId > 0 
                ? $this->productModel->isAvailableAtBranch($productId, $branchId)
                : true;
            
            return $this->jsonResponse('success', '', [
                'available_stock' => $stock,
                'available_at_branch' => $availableAtBranch,
                'in_stock' => $stock > 0 && $availableAtBranch
            ]);
            
        } catch (Exception $e) {
            $this->logError('Check availability failed', ['error' => $e->getMessage(), 'product_id' => $productId]);
            return $this->jsonResponse('failed', 'An error occurred while checking availability');
        }
    }
    
    
    private function handleImageUpload($productId, $isUpdate = false) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; 
        
        
        $uploadError = $this->validateUpload('img', $allowedTypes, $maxSize);
        if ($uploadError) {
            return ['success' => false, 'message' => $uploadError['msg']];
        }
        
        $uploadDir = __DIR__ . '/../../uploads/products';
        
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['img']['name']);
        $fileExt = strtolower($fileInfo['extension']);
        $newFilename = 'product_' . $productId . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . '/' . $newFilename;
        
        
        if ($isUpdate) {
            $product = $this->productModel->find($productId);
            if ($product && !empty($product['image_path'])) {
                $oldImagePath = __DIR__ . '/../../' . preg_replace('/\?v=\d+$/', '', $product['image_path']);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
        }
        
        
        if (move_uploaded_file($_FILES['img']['tmp_name'], $uploadPath)) {
            
            $imagePath = 'uploads/products/' . $newFilename;
            $this->productModel->update($productId, ['image_path' => $imagePath]);
            
            return ['success' => true, 'message' => 'Image uploaded successfully'];
        }
        
        return ['success' => false, 'message' => '(Image upload failed)'];
    }
}
