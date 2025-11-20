<?php
// app/models/SubsidiarySubject.php
require_once __DIR__ . '/BaseModel.php';

class SubsidiarySubject extends BaseModel {
    protected $table = 'subsidiary_subjects';
    
    /**
     * 有効な補助科目を全て取得（補助科目番号順）
     */
    public function findActive() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY number ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * 補助科目番号順で全件取得
     */
    public function findAllOrdered() {
        $sql = "SELECT ss.*, 
                       u1.name as created_by_name, 
                       u2.name as updated_by_name
                FROM {$this->table} ss
                LEFT JOIN users u1 ON ss.created_by = u1.id
                LEFT JOIN users u2 ON ss.updated_by = u2.id
                ORDER BY ss.number ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * 番号の重複チェック
     */
    public function isNumberExists($number, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE number = :number";
        $params = ['number' => (int)$number];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * 補助科目を作成
     */
    public function createSubsidiarySubject($data) {
        // 必須フィールドのチェック
        if (empty($data['number']) || empty($data['name'])) {
            return false;
        }
        
        // 番号の重複チェック
        if ($this->isNumberExists($data['number'])) {
            return false;
        }
        
        return $this->create($data);
    }
    
    /**
     * 補助科目を更新
     */
    public function updateSubsidiarySubject($id, $data) {
        // 番号の重複チェック（自分以外）
        if (isset($data['number']) && $this->isNumberExists($data['number'], $id)) {
            return false;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * 補助科目を削除（論理削除）
     */
    public function deleteSubsidiarySubject($id) {
        return $this->update($id, ['is_active' => 0]);
    }
}