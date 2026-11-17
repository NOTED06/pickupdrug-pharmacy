<?php

require_once __DIR__ . '/BaseModel.php';

class Branch extends BaseModel {
    
    
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*,
                       DATE_FORMAT(b.created_at, '%M %d, %Y at %h:%i %p') as formatted_created,
                       DATE_FORMAT(b.updated_at, '%M %d, %Y at %h:%i %p') as formatted_updated
                FROM branch_list b
                WHERE b.id = ?
            ");
            
            $stmt->execute([$id]);
            $branch = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($branch) {
                
                if (!empty($branch['operating_hours'])) {
                    $schedule = json_decode($branch['operating_hours'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($schedule)) {
                        $branch['schedule'] = $schedule;
                    }
                }
            }
            
            return $branch ?: null;
            
        } catch (PDOException $e) {
            error_log("Branch getById error: " . $e->getMessage());
            return null;
        }
    }
    
    
    public function getAll($activeOnly = false) {
        try {
            $sql = "
                SELECT b.*,
                       DATE_FORMAT(b.created_at, '%M %d, %Y at %h:%i %p') as formatted_created,
                       DATE_FORMAT(b.updated_at, '%M %d, %Y at %h:%i %p') as formatted_updated
                FROM branch_list b
            ";
            
            if ($activeOnly) {
                $sql .= " WHERE b.status = " . self::STATUS_ACTIVE;
            }
            
            $sql .= " ORDER BY b.status DESC, b.branch_name ASC";
            
            $stmt = $this->db->query($sql);
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            
            foreach ($branches as &$branch) {
                if (!empty($branch['operating_hours'])) {
                    $schedule = json_decode($branch['operating_hours'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($schedule)) {
                        $branch['schedule'] = $schedule;
                    }
                }
            }
            
            return $branches;
            
        } catch (PDOException $e) {
            error_log("Branch getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    
    public function getActive() {
        return $this->getAll(true);
    }
    
    
    public function create($data) {
        try {
            
            if (empty($data['branch_name']) || empty($data['branch_address'])) {
                throw new Exception('Branch name and address are required');
            }
            
            
            if ($this->isDuplicateName($data['branch_name'])) {
                throw new Exception('A branch with this name already exists');
            }
            
            
            if (!empty($data['branch_email']) && !filter_var($data['branch_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format');
            }
            
            
            $operating_hours = null;
            if (!empty($data['operating_hours'])) {
                $operating_hours = $this->validateOperatingHours($data['operating_hours']);
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO branch_list (
                    branch_name,
                    branch_address,
                    branch_phone,
                    branch_email,
                    manager_name,
                    operating_hours,
                    status,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $status = isset($data['status']) ? (int)$data['status'] : self::STATUS_ACTIVE;
            
            $stmt->execute([
                $data['branch_name'],
                $data['branch_address'],
                $data['branch_phone'] ?? null,
                $data['branch_email'] ?? null,
                $data['manager_name'] ?? null,
                $operating_hours,
                $status
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Branch create error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function update($id, $data) {
        try {
            
            $existing = $this->getById($id);
            if (!$existing) {
                throw new Exception('Branch not found');
            }
            
            
            if (empty($data['branch_name']) || empty($data['branch_address'])) {
                throw new Exception('Branch name and address are required');
            }
            
            
            if ($this->isDuplicateName($data['branch_name'], $id)) {
                throw new Exception('A branch with this name already exists');
            }
            
            
            if (!empty($data['branch_email']) && !filter_var($data['branch_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format');
            }
            
            
            $operating_hours = null;
            if (!empty($data['operating_hours'])) {
                $operating_hours = $this->validateOperatingHours($data['operating_hours']);
            }
            
            $stmt = $this->db->prepare("
                UPDATE branch_list SET
                    branch_name = ?,
                    branch_address = ?,
                    branch_phone = ?,
                    branch_email = ?,
                    manager_name = ?,
                    operating_hours = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $status = isset($data['status']) ? (int)$data['status'] : $existing['status'];
            
            return $stmt->execute([
                $data['branch_name'],
                $data['branch_address'],
                $data['branch_phone'] ?? null,
                $data['branch_email'] ?? null,
                $data['manager_name'] ?? null,
                $operating_hours,
                $status,
                $id
            ]);
            
        } catch (Exception $e) {
            error_log("Branch update error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function toggleStatus($id, $status) {
        try {
            
            $branch = $this->getById($id);
            if (!$branch) {
                throw new Exception('Branch not found');
            }
            
            
            if ($status !== self::STATUS_INACTIVE && $status !== self::STATUS_ACTIVE) {
                throw new Exception('Invalid status value');
            }
            
            
            if ($status == self::STATUS_INACTIVE) {
                $pendingCount = $this->getPendingOrdersCount($id);
                if ($pendingCount > 0) {
                    throw new Exception("Cannot deactivate branch. There are $pendingCount pending orders.");
                }
            }
            
            $stmt = $this->db->prepare("
                UPDATE branch_list 
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            return $stmt->execute([$status, $id]);
            
        } catch (Exception $e) {
            error_log("Branch toggleStatus error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function delete($id) {
        try {
            
            $branch = $this->getById($id);
            if (!$branch) {
                throw new Exception('Branch not found');
            }
            
            
            $totalOrders = $this->getTotalOrdersCount($id);
            if ($totalOrders > 0) {
                throw new Exception("Cannot delete branch. There are $totalOrders orders associated with this branch.");
            }
            
            
            $productCount = $this->getProductCount($id);
            if ($productCount > 0) {
                throw new Exception("Cannot delete branch. There are $productCount products assigned to this branch.");
            }
            
            $stmt = $this->db->prepare("DELETE FROM branch_list WHERE id = ?");
            return $stmt->execute([$id]);
            
        } catch (Exception $e) {
            error_log("Branch delete error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function getStatistics($id) {
        try {
            
            $branch = $this->getById($id);
            if (!$branch) {
                return null;
            }
            
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as packed_orders,
                    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as ready_orders,
                    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as cancelled_orders,
                    COALESCE(SUM(CASE WHEN status != 4 THEN total_amount ELSE 0 END), 0) as total_revenue,
                    COUNT(CASE WHEN date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as monthly_orders,
                    COUNT(CASE WHEN date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as weekly_orders,
                    COUNT(CASE WHEN DATE(date_created) = CURDATE() THEN 1 END) as today_orders
                FROM order_list
                WHERE branch_id = ?
            ");
            
            $stmt->execute([$id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            
            $stats['product_count'] = $this->getProductCount($id);
            
            
            $stats['branch_name'] = $branch['branch_name'];
            $stats['branch_status'] = $branch['status'];
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Branch getStatistics error: " . $e->getMessage());
            return null;
        }
    }
    
    
    public function canAcceptOrders($id) {
        try {
            $branch = $this->getById($id);
            
            if (!$branch) {
                return ['can_accept' => false, 'reason' => 'Branch not found'];
            }
            
            if ($branch['status'] != self::STATUS_ACTIVE) {
                return ['can_accept' => false, 'reason' => 'Branch is currently inactive'];
            }
            
            
            if (!empty($branch['schedule'])) {
                $isOpen = $this->isOpenNow($branch['schedule']);
                if (!$isOpen) {
                    return ['can_accept' => false, 'reason' => 'Branch is currently closed'];
                }
            }
            
            return ['can_accept' => true, 'reason' => ''];
            
        } catch (Exception $e) {
            error_log("Branch canAcceptOrders error: " . $e->getMessage());
            return ['can_accept' => false, 'reason' => 'Error checking branch availability'];
        }
    }
    
    
    private function isDuplicateName($name, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM branch_list WHERE branch_name = ?";
            $params = [$name];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Branch isDuplicateName error: " . $e->getMessage());
            return false;
        }
    }
    
    
    private function validateOperatingHours($hours) {
        if (is_array($hours)) {
            $hours = json_encode($hours);
        }
        
        if (is_string($hours)) {
            $decoded = json_decode($hours, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $hours;
            }
        }
        
        return null;
    }
    
    
    private function isOpenNow($schedule) {
        $currentDay = strtolower(date('l'));
        $currentTime = date('H:i');
        
        if (isset($schedule[$currentDay])) {
            $daySchedule = $schedule[$currentDay];
            
            if (isset($daySchedule['closed']) && $daySchedule['closed']) {
                return false;
            }
            
            if (isset($daySchedule['open']) && isset($daySchedule['close'])) {
                return ($currentTime >= $daySchedule['open'] && $currentTime <= $daySchedule['close']);
            }
        }
        
        return true; 
    }
    
    
    private function getPendingOrdersCount($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM order_list 
                WHERE branch_id = ? AND status IN (0, 1, 2)
            ");
            
            $stmt->execute([$id]);
            return (int)$stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Branch getPendingOrdersCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    
    private function getTotalOrdersCount($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM order_list 
                WHERE branch_id = ?
            ");
            
            $stmt->execute([$id]);
            return (int)$stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Branch getTotalOrdersCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    
    private function getProductCount($id) {
        try {
            
            $stmt = $this->db->query("SHOW TABLES LIKE 'product_branches'");
            if ($stmt->rowCount() > 0) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM product_branches 
                    WHERE branch_id = ?
                ");
                $stmt->execute([$id]);
                return (int)$stmt->fetchColumn();
            }
            
            return 0;
            
        } catch (PDOException $e) {
            error_log("Branch getProductCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    
    public function getStatusText($status) {
        return $status == self::STATUS_ACTIVE ? 'Active' : 'Inactive';
    }
}
