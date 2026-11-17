<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController extends BaseController {
    private $cartModel;
    private $customerId;
    
    public function __construct() {
        parent::__construct();
        
        $db = new DBConnection();
        $this->cartModel = new Cart($db->conn);
        
        
        $this->customerId = $this->settings->userdata('id');
    }

    private function refreshCustomerId() {
        $this->customerId = $this->settings->userdata('id');
    }
    
    
    public function add() {
        try {
            $this->refreshCustomerId();
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login first.');
            }
            
            
            $productId = $this->getPostInt('product_id');
            $quantity = $this->getPostInt('quantity', 1);
            $branchId = $this->getPostInt('branch_id', null);
            
            
            if (!$productId) {
                return $this->jsonResponse('failed', 'Product ID is required.');
            }
            
            if ($quantity <= 0) {
                return $this->jsonResponse('failed', 'Quantity must be greater than 0.');
            }
            
            
            $stockValidation = $this->cartModel->validateStock($productId, $quantity);
            
            if (!$stockValidation['valid']) {
                return $this->jsonResponse('failed', $stockValidation['message']);
            }
            
            
            $cartId = $this->cartModel->addOrUpdate(
                $this->customerId, 
                $productId, 
                $quantity, 
                $branchId
            );
            
            if ($cartId) {
                return $this->jsonResponse('success', 'Item added to cart successfully.', [
                    'cart_id' => $cartId
                ]);
            } else {
                return $this->jsonResponse('failed', 'Failed to add item to cart.');
            }
            
        } catch (Exception $e) {
            $this->logError('CartController::add', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while adding item to cart.');
        }
    }
    
    
    public function update() {
        try {
            $this->refreshCustomerId();

            
            $csrfError = $this->validateCsrf();
            if ($csrfError) return $csrfError;
            
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login first.');
            }
            
            
            $cartId = $this->getPostInt('cart_id');
            $quantity = $this->getPostInt('qty');
            
            
            if (!$cartId) {
                return $this->jsonResponse('failed', 'Cart ID is required.');
            }
            
            if ($quantity <= 0) {
                return $this->jsonResponse('failed', 'Quantity must be greater than 0.');
            }
            
            
            $cartItem = $this->cartModel->getById($cartId, $this->customerId);
            
            if (!$cartItem) {
                return $this->jsonResponse('failed', 'Cart item not found.');
            }
            
            
            $stockValidation = $this->cartModel->validateStock($cartItem['product_id'], $quantity);
            
            if (!$stockValidation['valid']) {
                return $this->jsonResponse('failed', $stockValidation['message']);
            }
            
            
            $success = $this->cartModel->updateQuantity($cartId, $quantity, $this->customerId);
            
            if ($success) {
                $this->settings->set_flashdata('success', 'Cart item has been updated.');
                return $this->jsonResponse('success', 'Cart item updated successfully.');
            } else {
                return $this->jsonResponse('failed', 'Failed to update cart item.');
            }
            
        } catch (Exception $e) {
            $this->logError('CartController::update', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while updating cart item.');
        }
    }
    
    
    public function remove() {
        try {
            $this->refreshCustomerId();

            
            $csrfError = $this->validateCsrf();
            if ($csrfError) return $csrfError;
            
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login first.');
            }
            
            
            $cartId = $this->getPostInt('id');
            
            if (!$cartId) {
                return $this->jsonResponse('failed', 'Invalid cart ID.');
            }
            
            
            $success = $this->cartModel->remove($cartId, $this->customerId);
            
            if ($success) {
                $this->settings->set_flashdata('success', 'Cart item has been deleted.');
                return $this->jsonResponse('success', 'Cart item removed successfully.');
            } else {
                return $this->jsonResponse('failed', 'Failed to remove cart item.');
            }
            
        } catch (Exception $e) {
            $this->logError('CartController::remove', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while removing cart item.');
        }
    }
    
    
    public function clear() {
        $this->refreshCustomerId();

        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json');
        }
        
        try {
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Customer ID not found.');
            }
            
            
            $success = $this->cartModel->clearCart($this->customerId);
            
            if ($success) {
                return $this->jsonResponse('success', 'Cart cleared successfully.');
            } else {
                return $this->jsonResponse('failed', 'Failed to clear cart.');
            }
            
        } catch (Exception $e) {
            $this->logError('CartController::clear', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while clearing cart.');
        }
    }
    
    
    public function clearAndAdd() {
        try {
            $this->refreshCustomerId();
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login first.');
            }
            
            
            $productId = $this->getPostInt('product_id');
            $quantity = $this->getPostInt('quantity', 1);
            $branchId = $this->getPostInt('branch_id');
            
            
            if (!$productId || !$branchId) {
                return $this->jsonResponse('failed', 'Product ID and Branch ID are required.');
            }
            
            if ($quantity <= 0) {
                return $this->jsonResponse('failed', 'Quantity must be greater than 0.');
            }
            
            
            $stockValidation = $this->cartModel->validateStock($productId, $quantity);
            
            if (!$stockValidation['valid']) {
                return $this->jsonResponse('failed', $stockValidation['message']);
            }
            
            
            $cartId = $this->cartModel->clearAndAdd(
                $this->customerId, 
                $productId, 
                $quantity, 
                $branchId
            );
            
            if ($cartId) {
                return $this->jsonResponse('success', 'Cart cleared and item added.', [
                    'cart_id' => $cartId
                ]);
            } else {
                return $this->jsonResponse('failed', 'Failed to add item.');
            }
            
        } catch (Exception $e) {
            $this->logError('CartController::clearAndAdd', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred.');
        }
    }
    
    
    public function index() {
        try {
            $this->refreshCustomerId();
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login first.', []);
            }
            
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
            
            
            $items = $this->cartModel->getByCustomer($this->customerId, $limit);

            return $this->jsonResponse('success', 'Cart items retrieved.', ['items' => $items]);
            
        } catch (Exception $e) {
            $this->logError('CartController::index', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred while retrieving cart items.', []);
        }
    }
    
    
    public function summary() {
        try {
            $this->refreshCustomerId();
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('success', 'No cart data.', [
                    'count' => 0,
                    'total' => 0.0
                ]);
            }
            
            
            $summary = $this->cartModel->getCartSummary($this->customerId);
            
            return $this->jsonResponse('success', 'Cart summary retrieved.', $summary);
            
        } catch (Exception $e) {
            $this->logError('CartController::summary', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred.');
        }
    }
    
    
    public function validateStock() {
        try {
            $this->refreshCustomerId();
            
            if (empty($this->customerId)) {
                return $this->jsonResponse('failed', 'Please login first.');
            }
            
            
            $validation = $this->cartModel->validateCartStock($this->customerId);
            
            if ($validation['valid']) {
                return $this->jsonResponse('success', 'All items are in stock.', $validation);
            } else {
                return $this->jsonResponse('warning', 'Some items are out of stock.', $validation);
            }
            
        } catch (Exception $e) {
            $this->logError('CartController::validateStock', $e->getMessage());
            return $this->jsonResponse('failed', 'An error occurred.');
        }
    }
}
