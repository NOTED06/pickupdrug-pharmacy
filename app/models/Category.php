<?php

require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel {
    
    protected $table = 'category_list';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'description', 'status'
    ];
    
    
    public function getAllActive() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE delete_flag = 0 AND status = 1 
                ORDER BY name ASC";
        return $this->db->select($sql);
    }
    
    
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE delete_flag = 0 
                ORDER BY name ASC";
        return $this->db->select($sql);
    }
    
    
    public function getWithProductCount($id) {
        $sql = "SELECT c.*, 
                COUNT(p.id) as product_count
                FROM {$this->table} c
                LEFT JOIN product_list p ON c.id = p.category_id AND p.delete_flag = 0
                WHERE c.id = ? AND c.delete_flag = 0
                GROUP BY c.id";
        return $this->db->selectOne($sql, [$id]);
    }
    
    
    public function getAllWithProductCounts() {
        $sql = "SELECT c.*, 
                COUNT(p.id) as product_count
                FROM {$this->table} c
                LEFT JOIN product_list p ON c.id = p.category_id AND p.delete_flag = 0
                WHERE c.delete_flag = 0
                GROUP BY c.id
                ORDER BY c.name ASC";
        return $this->db->select($sql);
    }
    
    
    public function isDuplicate($name, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE name = ? AND delete_flag = 0";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->selectOne($sql, $params);
        return ($result !== null && $result !== false);
    }
    
    
    public function softDelete($id) {
        
        $sql = "SELECT COUNT(*) as count FROM product_list 
                WHERE category_id = ? AND delete_flag = 0";
        $result = $this->db->selectOne($sql, [$id]);
        
        if ($result && $result['count'] > 0) {
            return false; 
        }
        
        $sql = "UPDATE {$this->table} SET delete_flag = 1 WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    
    public function toggleStatus($id) {
        $sql = "UPDATE {$this->table} 
                SET status = IF(status = 1, 0, 1) 
                WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_categories,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_categories,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_categories
                FROM {$this->table} 
                WHERE delete_flag = 0";
        
        return $this->db->selectOne($sql);
    }
    
    
    public function canDelete($id) {
        $sql = "SELECT COUNT(*) as count FROM product_list 
                WHERE category_id = ? AND delete_flag = 0";
        $result = $this->db->selectOne($sql, [$id]);
        
        if ($result && $result['count'] > 0) {
            return [
                'can_delete' => false,
                'message' => "Cannot delete category. {$result['count']} product(s) are assigned to this category.",
                'product_count' => $result['count']
            ];
        }
        
        return [
            'can_delete' => true,
            'message' => 'Category can be safely deleted.',
            'product_count' => 0
        ];
    }
}
