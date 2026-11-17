<?php

require_once __DIR__ . '/../../config/database.php';

abstract class BaseModel {
    
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];
    
    
    private $selectColumns = ['*'];
    private $whereConditions = [];
    private $whereParams = [];
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->selectOne($sql, ['id' => $id]);
    }
    
    
    public function findBy($column, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1";
        return $this->db->selectOne($sql, ['value' => $value]);
    }
    
    
    public function all() {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->select($sql);
    }
    
    
    public function select($columns) {
        $this->selectColumns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    
    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $paramName = 'param' . count($this->whereParams);
        $this->whereConditions[] = "{$column} {$operator} :{$paramName}";
        $this->whereParams[$paramName] = $value;
        
        return $this;
    }
    
    
    public function whereIn($column, $values) {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $paramName = 'param' . count($this->whereParams);
            $placeholders[] = ":{$paramName}";
            $this->whereParams[$paramName] = $value;
        }
        
        $this->whereConditions[] = "{$column} IN (" . implode(',', $placeholders) . ")";
        return $this;
    }
    
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }
    
    
    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }
    
    
    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }
    
    
    public function get() {
        $sql = $this->buildQuery();
        $results = $this->db->select($sql, $this->whereParams);
        $this->resetQuery();
        return $results;
    }
    
    
    public function first() {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? false;
    }
    
    
    public function count() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if (!empty($this->whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->whereConditions);
        }
        
        $result = $this->db->selectOne($sql, $this->whereParams);
        $this->resetQuery();
        
        return (int) ($result['count'] ?? 0);
    }
    
    
    public function create($data) {
        $data = $this->filterFillable($data);
        return $this->db->insert($this->table, $data);
    }
    
    
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        return $this->db->update(
            $this->table, 
            $data, 
            "{$this->primaryKey} = :id", 
            ['id' => $id]
        );
    }
    
    
    public function updateWhere($data) {
        if (empty($this->whereConditions)) {
            throw new Exception("Cannot update without WHERE conditions");
        }
        
        $data = $this->filterFillable($data);
        $where = implode(' AND ', $this->whereConditions);
        $result = $this->db->update($this->table, $data, $where, $this->whereParams);
        $this->resetQuery();
        
        return $result;
    }
    
    
    public function delete($id) {
        return $this->db->delete(
            $this->table, 
            "{$this->primaryKey} = :id", 
            ['id' => $id]
        );
    }
    
    
    public function deleteWhere() {
        if (empty($this->whereConditions)) {
            throw new Exception("Cannot delete without WHERE conditions");
        }
        
        $where = implode(' AND ', $this->whereConditions);
        $result = $this->db->delete($this->table, $where, $this->whereParams);
        $this->resetQuery();
        
        return $result;
    }
    
    
    public function raw($sql, $params = []) {
        return $this->db->select($sql, $params);
    }
    
    
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    
    public function commit() {
        return $this->db->commit();
    }
    
    
    public function rollback() {
        return $this->db->rollback();
    }
    
    
    private function buildQuery() {
        $columns = implode(', ', $this->selectColumns);
        $sql = "SELECT {$columns} FROM {$this->table}";
        
        if (!empty($this->whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->whereConditions);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }
    
    
    private function resetQuery() {
        $this->selectColumns = ['*'];
        $this->whereConditions = [];
        $this->whereParams = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
    }
    
    
    private function filterFillable($data) {
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        
        if (!empty($this->guarded)) {
            $data = array_diff_key($data, array_flip($this->guarded));
        }
        
        return $data;
    }
}
