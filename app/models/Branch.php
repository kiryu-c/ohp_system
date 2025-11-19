<?php
// app/models/Branch.php
require_once __DIR__ . '/BaseModel.php';

class Branch extends BaseModel {
    protected $table = 'branches';
    
    public function findActive() {
        return $this->findAll(['is_active' => 1]);
    }
    
    public function findByCompany($companyId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE company_id = :company_id AND is_active = 1
                ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);
        
        $result = $stmt->fetchAll();
        
        return $result;
    }
    
    public function findWithCompany($branchId) {
        $sql = "SELECT b.*, c.name as company_name
                FROM {$this->table} b
                JOIN companies c ON b.company_id = c.id
                WHERE b.id = :branch_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['branch_id' => $branchId]);
        
        return $stmt->fetch();
    }
    
    public function findAllWithCompany() {
        $sql = "SELECT b.*, c.name as company_name
                FROM {$this->table} b
                JOIN companies c ON b.company_id = c.id
                WHERE b.is_active = 1
                ORDER BY c.name ASC, b.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * 拠点に紐づくユーザー一覧を取得
     */
    public function getBranchUsers($branchId) {
        $sql = "SELECT u.id, u.name, u.email, u.login_id, ubm.created_at as mapped_at
                FROM user_branch_mappings ubm
                INNER JOIN users u ON ubm.user_id = u.id
                WHERE ubm.branch_id = :branch_id 
                AND ubm.is_active = 1 
                AND u.is_active = 1
                ORDER BY u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['branch_id' => $branchId]);
        
        return $stmt->fetchAll();
    }

    /**
     * 拠点詳細情報を企業情報と共に取得
     */
    public function findByIdWithCompany($id) {
        $sql = "SELECT b.*, c.name as company_name 
                FROM {$this->table} b
                INNER JOIN companies c ON b.company_id = c.id
                WHERE b.id = :id AND b.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }

}
?>