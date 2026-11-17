<?php

require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel {
    
    protected $table = 'product_list';
    protected $primaryKey = 'id';
    protected $fillable = [
        'brand', 'name', 'dose', 'category_id', 'description', 
        'price', 'unit', 'form_id', 'is_prescription', 'fda_approved',
        'image_path', 'branch_id'
    ];
    
    
    public function getWithCategory($id) {
        $sql = "SELECT p.*, c.name as category_name, c.description as category_description
                FROM {$this->table} p 
                LEFT JOIN category_list c ON p.category_id = c.id 
                WHERE p.id = ? AND p.delete_flag = 0";
        return $this->db->selectOne($sql, [$id]);
    }
    
    
    public function getAllWithCategory($filters = []) {
        $sql = "SELECT p.*, c.name as category_name
                FROM {$this->table} p 
                LEFT JOIN category_list c ON p.category_id = c.id 
                WHERE p.delete_flag = 0";
        
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (isset($filters['is_prescription'])) {
            $sql .= " AND p.is_prescription = ?";
            $params[] = $filters['is_prescription'];
        }
        
        if (isset($filters['fda_approved'])) {
            $sql .= " AND p.fda_approved = ?";
            $params[] = $filters['fda_approved'];
        }
        
        $sql .= " ORDER BY p.brand ASC, p.name ASC";
        
        return $this->db->select($sql, $params);
    }
    
    
    public function getAvailableStock($productId) {
        $sql = "SELECT COALESCE(
                    (SELECT SUM(quantity) FROM stock_list 
                     WHERE product_id = ? 
                     AND (expiration IS NULL OR date(expiration) > CURDATE())), 0
                ) - COALESCE(
                    (SELECT SUM(quantity) FROM order_items 
                     WHERE product_id = ?), 0
                ) as available";
        
        $result = $this->db->selectOne($sql, [$productId, $productId]);
        return $result ? intval($result['available']) : 0;
    }
    
    
    public function isDuplicate($brand, $name, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE brand = ? AND name = ? AND delete_flag = 0";
        $params = [$brand, $name];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->db->selectOne($sql, $params) !== null;
    }
    
    
    public function softDelete($id) {
        $sql = "UPDATE {$this->table} SET delete_flag = 1 WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    
    public function getByBranch($branchId) {
        
        
        $sql = "SELECT DISTINCT p.*, c.name as category_name
                FROM {$this->table} p
                LEFT JOIN category_list c ON p.category_id = c.id
                LEFT JOIN product_branches pb ON p.id = pb.product_id
                WHERE p.delete_flag = 0
                AND (pb.branch_id = ? OR pb.branch_id IS NULL)
                ORDER BY p.brand ASC, p.name ASC";
        
        return $this->db->select($sql, [$branchId]);
    }
    
    
    public function getBranchIds($productId) {
        $sql = "SELECT branch_id FROM product_branches WHERE product_id = ?";
        $results = $this->db->select($sql, [$productId]);
        
        return array_map(function($row) {
            return $row['branch_id'];
        }, $results);
    }
    
    
    public function setBranches($productId, $branchIds) {
        
        $deleteSql = "DELETE FROM product_branches WHERE product_id = ?";
        $this->db->execute($deleteSql, [$productId]);
        
        
        if (empty($branchIds)) {
            return true; 
        }
        
        $values = [];
        $params = [];
        foreach ($branchIds as $branchId) {
            $values[] = "(?, ?)";
            $params[] = $productId;
            $params[] = $branchId;
        }
        
        if (!empty($values)) {
            $insertSql = "INSERT INTO product_branches (product_id, branch_id) VALUES " . implode(', ', $values);
            return $this->db->execute($insertSql, $params);
        }
        
        return true;
    }
    
    
    public function isAvailableAtBranch($productId, $branchId) {
        
        $sql = "SELECT COUNT(*) as count FROM product_branches WHERE product_id = ?";
        $result = $this->db->selectOne($sql, [$productId]);
        
        
        if ($result['count'] == 0) {
            return true;
        }
        
        
        $sql = "SELECT COUNT(*) as count FROM product_branches 
                WHERE product_id = ? AND branch_id = ?";
        $result = $this->db->selectOne($sql, [$productId, $branchId]);
        
        return $result['count'] > 0;
    }
    
    
    public function getForms() {
        $sql = "SELECT id, name, description FROM form_list WHERE delete_flag = 0 ORDER BY name ASC";
        return $this->db->select($sql);
    }
    
    
    public function categoryExists($categoryId) {
        $sql = "SELECT COUNT(*) as count FROM category_list WHERE id = ? AND delete_flag = 0";
        $result = $this->db->selectOne($sql, [$categoryId]);
        return $result && $result['count'] > 0;
    }
    
    
    public function formExists($formId) {
        $sql = "SELECT COUNT(*) as count FROM form_list WHERE id = ? AND delete_flag = 0";
        $result = $this->db->selectOne($sql, [$formId]);
        return $result && $result['count'] > 0;
    }
    
    
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_products,
                COUNT(DISTINCT category_id) as total_categories,
                SUM(CASE WHEN is_prescription = 1 THEN 1 ELSE 0 END) as prescription_count,
                SUM(CASE WHEN fda_approved = 1 THEN 1 ELSE 0 END) as fda_approved_count
                FROM {$this->table} 
                WHERE delete_flag = 0";
        
        return $this->db->selectOne($sql);
    }
    
    
    public function search($searchTerm, $filters = []) {
        $sql = "SELECT p.*, c.name as category_name
                FROM {$this->table} p 
                LEFT JOIN category_list c ON p.category_id = c.id 
                WHERE p.delete_flag = 0
                AND (p.brand LIKE ? OR p.name LIKE ? OR p.description LIKE ?)";
        
        $searchParam = "%{$searchTerm}%";
        $params = [$searchParam, $searchParam, $searchParam];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        $sql .= " ORDER BY p.brand ASC, p.name ASC";
        
        return $this->db->select($sql, $params);
    }
}
