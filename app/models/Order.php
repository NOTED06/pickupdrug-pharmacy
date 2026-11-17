<?php

require_once __DIR__ . '/../models/BaseModel.php';

class Order extends BaseModel {
    
    
    const STATUS_PENDING = 0;
    const STATUS_PACKED = 1;
    const STATUS_READY_FOR_PICKUP = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_CANCELLED = 4;
    
    
    public function getById($orderId, $customerId = null) {
        $sql = "SELECT o.*, 
                       c.firstname, c.lastname, c.email, c.contact,
                       b.branch_name, b.branch_address,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                FROM order_list o
                LEFT JOIN customer_list c ON o.customer_id = c.id
                LEFT JOIN branch_list b ON o.branch_id = b.id
                WHERE o.id = ?";
        
        $params = [$orderId];
        
        if ($customerId !== null) {
            $sql .= " AND o.customer_id = ?";
            $params[] = $customerId;
        }
        
        $stmt = $this->db->query($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    
    public function getByCustomer($customerId, $limit = null) {
        $sql = "SELECT o.*, 
                       b.branch_name,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                FROM order_list o
                LEFT JOIN branch_list b ON o.branch_id = b.id
                WHERE o.customer_id = ?
                ORDER BY o.date_created DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            return $this->db->select($sql, [$customerId, $limit]);
        }
        
        return $this->db->select($sql, [$customerId]);
    }
    
    
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, 
                       p.name as product_name, 
                       p.brand,
                       p.image_path
                FROM order_items oi
                LEFT JOIN product_list p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                ORDER BY p.name";
        
        return $this->db->select($sql, [$orderId]);
    }
    
    
    public function createOrder($orderData, $items) {
        
        $code = $this->generateOrderCode();
        
        
        $sql = "INSERT INTO order_list 
                (code, customer_id, total_amount, branch_id, pickup_date, pickup_time, 
                 delivery_address, status, prescription_validation, prescription_details) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $code,
            $orderData['customer_id'],
            $orderData['total_amount'],
            $orderData['branch_id'],
            $orderData['pickup_date'],
            $orderData['pickup_time'],
            $orderData['delivery_address'] ?? null,
            self::STATUS_PENDING,
            $orderData['prescription_validation'] ?? 'pending',
            $orderData['prescription_details'] ?? null
        ];
        
        $orderId = $this->db->insert($sql, $params);
        
        if (!$orderId) {
            throw new Exception('Failed to create order');
        }
        
        
        $this->addOrderItems($orderId, $items);
        
        return $orderId;
    }
    
    
    private function addOrderItems($orderId, $items) {
        if (empty($items)) {
            throw new Exception('No items to add to order');
        }
        
        $values = [];
        $params = [];
        
        foreach ($items as $item) {
            $values[] = "(?, ?, ?, ?)";
            $params[] = $orderId;
            $params[] = $item['product_id'];
            $params[] = $item['quantity'];
            $params[] = $item['price'];
        }
        
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES " . implode(', ', $values);
        
        $stmt = $this->db->query($sql, $params);
        return $stmt !== false;
    }
    
    
    public function updateStatus($orderId, $status) {
        $sql = "UPDATE order_list SET status = ? WHERE id = ?";
        $stmt = $this->db->query($sql, [$status, $orderId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    
    public function cancel($orderId, $customerId, $reason = null) {
        $sql = "UPDATE order_list 
                SET status = ?, cancellation_reason = ?, date_cancelled = NOW() 
                WHERE id = ? AND customer_id = ? AND status = ?";
        
        $stmt = $this->db->query($sql, [
            self::STATUS_CANCELLED,
            $reason,
            $orderId,
            $customerId,
            self::STATUS_PENDING
        ]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    
    public function adminCancel($orderId, $reason, $notes = null) {
        $sql = "UPDATE order_list 
                SET status = ?, cancellation_reason = ?, cancellation_notes = ?, date_cancelled = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db->query($sql, [
            self::STATUS_CANCELLED,
            $reason,
            $notes,
            $orderId
        ]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    
    public function delete($orderId) {
        
        $sql = "DELETE FROM order_items WHERE order_id = ?";
        $this->db->query($sql, [$orderId]);
        
        
        $sql = "DELETE FROM order_list WHERE id = ?";
        $stmt = $this->db->query($sql, [$orderId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    
    public function canCancel($orderId, $customerId) {
        $order = $this->getById($orderId, $customerId);
        
        if (!$order) {
            return ['can_cancel' => false, 'reason' => 'Order not found or access denied.'];
        }
        
        if ($order['status'] != self::STATUS_PENDING) {
            return ['can_cancel' => false, 'reason' => 'Order cannot be cancelled: Status is ' . $this->getStatusText($order['status']) . '.'];
        }
        
        
        $orderTimestamp = strtotime($order['date_created']);
        $cutoffTimestamp = strtotime(date('Y-m-d', $orderTimestamp) . ' +1 day 00:00:00');
        
        if (time() >= $cutoffTimestamp) {
            return ['can_cancel' => false, 'reason' => 'The cancellation window (until the next day\'s 12:00 AM) has closed.'];
        }
        
        return ['can_cancel' => true, 'reason' => ''];
    }
    
    
    private function generateOrderCode() {
        $prefix = date("Ymd");
        $sequence = 1;
        $attempts = 0;
        
        while ($attempts < 100) {
            $code = $prefix . sprintf("%'.05d", $sequence);
            
            $sql = "SELECT COUNT(*) as count FROM order_list WHERE code = ?";
            $result = $this->db->selectOne($sql, [$code]);
            
            if ($result['count'] == 0) {
                return $code;
            }
            
            $sequence++;
            $attempts++;
        }
        
        
        return $prefix . time();
    }
    
    
    public function getCustomerStatistics($customerId) {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as packed_orders,
                    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as ready_orders,
                    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as cancelled_orders,
                    COALESCE(SUM(CASE WHEN status = 3 THEN total_amount ELSE 0 END), 0) as total_spent
                FROM order_list
                WHERE customer_id = ?";
        
        return $this->db->selectOne($sql, [$customerId]);
    }
    
    
    public function getStatusText($status) {
        switch ($status) {
            case self::STATUS_PENDING: return 'Pending';
            case self::STATUS_PACKED: return 'Packed';
            case self::STATUS_READY_FOR_PICKUP: return 'Ready for Pick Up';
            case self::STATUS_COMPLETED: return 'Completed';
            case self::STATUS_CANCELLED: return 'Cancelled';
            default: return 'Unknown';
        }
    }
    
    
    public function getStatusMessage($status) {
        switch ($status) {
            case self::STATUS_PENDING: 
                return 'Your order is now pending processing.';
            case self::STATUS_PACKED: 
                return 'Your order has been packed and is being prepared.';
            case self::STATUS_READY_FOR_PICKUP: 
                return 'Great news! Your order is ready for pickup.';
            case self::STATUS_COMPLETED: 
                return 'Your order has been completed. Thank you!';
            case self::STATUS_CANCELLED: 
                return 'Your order has been cancelled.';
            default: 
                return 'Order status updated.';
        }
    }
    
    
    public function getAll($filters = [], $limit = null, $offset = null) {
        $sql = "SELECT o.*, 
                       c.firstname, c.lastname, c.contact,
                       b.branch_name,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                FROM order_list o
                LEFT JOIN customer_list c ON o.customer_id = c.id
                LEFT JOIN branch_list b ON o.branch_id = b.id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['branch_id'])) {
            $sql .= " AND o.branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        
        if (isset($filters['date_from'])) {
            $sql .= " AND DATE(o.date_created) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $sql .= " AND DATE(o.date_created) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY o.date_created DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            
            if ($offset !== null) {
                $sql .= " OFFSET ?";
                $params[] = $offset;
            }
        }
        
        return $this->db->select($sql, $params);
    }
}
