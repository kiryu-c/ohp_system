<?php
// app/models/Company.php
require_once __DIR__ . '/BaseModel.php';

class Company extends BaseModel {
    protected $table = 'companies';
    
    public function findActive() {
        return $this->findAll(['is_active' => 1]);
    }
    
    public function findWithUsers() {
        $sql = "SELECT c.*, COUNT(u.id) as user_count 
                FROM {$this->table} c 
                LEFT JOIN users u ON c.id = u.company_id 
                WHERE c.is_active = 1 
                GROUP BY c.id 
                ORDER BY c.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function findWithContracts($companyId) {
        $sql = "SELECT c.*, co.id as contract_id, co.regular_visit_hours, co.start_date, co.end_date, co.contract_status,
                       u.name as doctor_name
                FROM {$this->table} c
                LEFT JOIN contracts co ON c.id = co.company_id
                LEFT JOIN users u ON co.doctor_id = u.id
                WHERE c.id = :company_id AND c.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);
        
        return $stmt->fetchAll();
    }
}
?>