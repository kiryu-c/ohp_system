<?php
// app/models/BaseModel.php
require_once __DIR__ . '/../core/Database.php';

class BaseModel {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function findAll($conditions = [], $orderBy = 'id DESC', $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', array_map(function($key) {
                return "{$key} = :{$key}";
            }, array_keys($conditions)));
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($conditions);
        
        return $stmt->fetchAll();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }
    
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":{$field}";
        }, $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $fields = array_keys($data);
        $setClause = implode(', ', array_map(function($field) {
            return "{$field} = :{$field}";
        }, $fields));
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
        $data['id'] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
?>