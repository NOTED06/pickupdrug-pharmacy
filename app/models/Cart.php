<?php

require_once __DIR__ . '/../models/BaseModel.php';

class Cart extends BaseModel {
    
    
    public function getByCustomer($customerId, $limit = null) {
        $sql = "SELECT c.*, 
                       p.name as product_name, 
                       p.price, 
                       p.image_path,
                       CONCAT('uploads/', p.image_path) as image,
                       p.brand,
                       (c.quantity * p.price) as total_price
                FROM cart_list c 
                LEFT JOIN product_list p ON c.product_id = p.id
                WHERE c.customer_id = ? AND p.delete_flag = 0
                ORDER BY c.id DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            return $this->db->select($sql, [$customerId, $limit]);
        }
        
        return $this->db->select($sql, [$customerId]);
    }
    
    
    public function getById($cartId, $customerId) {
        $sql = "SELECT c.*, p.name as product_name, p.price 
                FROM cart_list c
                LEFT JOIN product_list p ON c.product_id = p.id
                WHERE c.id = ? AND c.customer_id = ?";
        
        return $this->db->selectOne($sql, [$cartId, $customerId]);
    }
    
    
    public function findExistingItem($customerId, $productId) {
        $sql = "SELECT * FROM cart_list 
                WHERE customer_id = ? AND product_id = ?";
        
        return $this->db->selectOne($sql, [$customerId, $productId]);
    }
    
    
    public function addOrUpdate($customerId, $productId, $quantity, $branchId = null) {
        $existing = $this->findExistingItem($customerId, $productId);
        
        if ($existing) {
            
            $newQuantity = $existing['quantity'] + $quantity;
            $sql = "UPDATE cart_list SET quantity = ? WHERE id = ?";
            $this->db->execute($sql, [$newQuantity, $existing['id']]);
            return $existing['id'];
        } else {
            
            if ($branchId !== null) {
                $sql = "INSERT INTO cart_list (customer_id, product_id, quantity, branch_id) 
                        VALUES (?, ?, ?, ?)";
                return $this->db->insert($sql, [$customerId, $productId, $quantity, $branchId]);
            } else {
                $sql = "INSERT INTO cart_list (customer_id, product_id, quantity) 
                        VALUES (?, ?, ?)";
                return $this->db->insert($sql, [$customerId, $productId, $quantity]);
            }
        }
    }
    
    
    public function updateQuantity($cartId, $quantity, $customerId) {
        $sql = "UPDATE cart_list SET quantity = ? 
                WHERE id = ? AND customer_id = ?";
        
        return $this->db->execute($sql, [$quantity, $cartId, $customerId]);
    }
    
    
    public function remove($cartId, $customerId) {
        $sql = "DELETE FROM cart_list WHERE id = ? AND customer_id = ?";
        return $this->db->execute($sql, [$cartId, $customerId]);
    }
    
    
    public function clearCart($customerId) {
        $sql = "DELETE FROM cart_list WHERE customer_id = ?";
        return $this->db->execute($sql, [$customerId]);
    }
    
    
    public function clearAndAdd($customerId, $productId, $quantity, $branchId) {
        
        $this->clearCart($customerId);
        
        
        $sql = "INSERT INTO cart_list (customer_id, product_id, quantity, branch_id) 
                VALUES (?, ?, ?, ?)";
        
        return $this->db->insert($sql, [$customerId, $productId, $quantity, $branchId]);
    }
    
    
    public function getAvailableStock($productId) {
        $sql = "SELECT 
                (COALESCE((SELECT SUM(quantity) FROM stock_list 
                          WHERE product_id = ? 
                          AND (expiration IS NULL OR DATE(expiration) > CURDATE())), 0) 
                - COALESCE((SELECT SUM(quantity) FROM order_items 
                           WHERE product_id = ?), 0)) as available 
                FROM product_list 
                WHERE id = ? AND delete_flag = 0";
        
        $result = $this->db->selectOne($sql, [$productId, $productId, $productId]);
        
        return $result ? (int)$result['available'] : 0;
    }
    
    
    public function validateStock($productId, $requestedQty) {
        $available = $this->getAvailableStock($productId);
        
        if ($requestedQty > $available) {
            return [
                'valid' => false,
                'available' => $available,
                'message' => "Requested quantity ({$requestedQty}) exceeds available stock ({$available})."
            ];
        }
        
        return [
            'valid' => true,
            'available' => $available,
            'message' => 'Stock is available.'
        ];
    }
    
    
    public function getCartCount($customerId) {
        $sql = "SELECT COUNT(*) as count FROM cart_list WHERE customer_id = ?";
        $result = $this->db->selectOne($sql, [$customerId]);
        
        return $result ? (int)$result['count'] : 0;
    }
    
    
    public function getCartTotal($customerId) {
        $sql = "SELECT SUM(c.quantity * p.price) as total 
                FROM cart_list c
                LEFT JOIN product_list p ON c.product_id = p.id
                WHERE c.customer_id = ? AND p.delete_flag = 0";
        
        $result = $this->db->selectOne($sql, [$customerId]);
        
        return $result && $result['total'] ? (float)$result['total'] : 0.0;
    }
    
    
    public function getCartSummary($customerId) {
        return [
            'count' => $this->getCartCount($customerId),
            'total' => $this->getCartTotal($customerId)
        ];
    }
    
    
    public function validateCartStock($customerId) {
        $items = $this->getByCustomer($customerId);
        $errors = [];
        
        foreach ($items as $item) {
            $validation = $this->validateStock($item['product_id'], $item['quantity']);
            
            if (!$validation['valid']) {
                $errors[] = [
                    'cart_id' => $item['id'],
                    'product_name' => $item['product_name'],
                    'message' => $validation['message'],
                    'requested' => $item['quantity'],
                    'available' => $validation['available']
                ];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
