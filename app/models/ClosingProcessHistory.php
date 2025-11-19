<?php
// app/models/ClosingProcessHistory.php
require_once __DIR__ . '/../core/Database.php';

class ClosingProcessHistory
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * 履歴レコードを作成
     */
    public function create($data)
    {
        $sql = "INSERT INTO closing_process_history (
                    monthly_closing_record_id, action_type, action_by, 
                    comment, before_data, after_data, action_at
                ) VALUES (
                    :monthly_closing_record_id, :action_type, :action_by,
                    :comment, :before_data, :after_data, NOW()
                )";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    /**
     * 月次締め記録IDで履歴を取得
     */
    public function findByClosingRecordId($closingRecordId)
    {
        $sql = "SELECT cph.*, u.name as action_by_name
                FROM closing_process_history cph
                JOIN users u ON cph.action_by = u.id
                WHERE cph.monthly_closing_record_id = :closing_record_id
                ORDER BY cph.action_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['closing_record_id' => $closingRecordId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 契約の締め処理履歴を取得
     */
    public function findByContractId($contractId, $limit = null)
    {
        $sql = "SELECT cph.*, u.name as action_by_name, mcr.closing_period,
                       comp.name as company_name, b.name as branch_name
                FROM closing_process_history cph
                JOIN users u ON cph.action_by = u.id
                JOIN monthly_closing_records mcr ON cph.monthly_closing_record_id = mcr.id
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                WHERE mcr.contract_id = :contract_id
                ORDER BY cph.action_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 産業医の締め処理履歴を取得
     */
    public function findByDoctorId($doctorId, $limit = null)
    {
        $sql = "SELECT cph.*, u.name as action_by_name, mcr.closing_period,
                       comp.name as company_name, b.name as branch_name
                FROM closing_process_history cph
                JOIN users u ON cph.action_by = u.id
                JOIN monthly_closing_records mcr ON cph.monthly_closing_record_id = mcr.id
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                WHERE mcr.doctor_id = :doctor_id
                ORDER BY cph.action_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 企業の締め処理履歴を取得
     */
    public function findByCompanyId($companyId, $limit = null)
    {
        $sql = "SELECT cph.*, u.name as action_by_name, mcr.closing_period,
                       comp.name as company_name, b.name as branch_name, doc.name as doctor_name
                FROM closing_process_history cph
                JOIN users u ON cph.action_by = u.id
                JOIN monthly_closing_records mcr ON cph.monthly_closing_record_id = mcr.id
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                JOIN users doc ON mcr.doctor_id = doc.id
                WHERE comp.id = :company_id
                ORDER BY cph.action_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 特定期間の履歴を取得
     */
    public function findByDateRange($startDate, $endDate, $limit = null)
    {
        $sql = "SELECT cph.*, u.name as action_by_name, mcr.closing_period,
                       comp.name as company_name, b.name as branch_name, doc.name as doctor_name
                FROM closing_process_history cph
                JOIN users u ON cph.action_by = u.id
                JOIN monthly_closing_records mcr ON cph.monthly_closing_record_id = mcr.id
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                JOIN users doc ON mcr.doctor_id = doc.id
                WHERE DATE(cph.action_at) BETWEEN :start_date AND :end_date
                ORDER BY cph.action_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * アクション種別の統計を取得
     */
    public function getActionTypeStats($contractId = null, $doctorId = null, $companyId = null)
    {
        $sql = "SELECT cph.action_type, COUNT(*) as count
                FROM closing_process_history cph
                JOIN monthly_closing_records mcr ON cph.monthly_closing_record_id = mcr.id";
        
        $where = [];
        $params = [];
        
        if ($contractId) {
            $where[] = "mcr.contract_id = :contract_id";
            $params['contract_id'] = $contractId;
        }
        
        if ($doctorId) {
            $where[] = "mcr.doctor_id = :doctor_id";
            $params['doctor_id'] = $doctorId;
        }
        
        if ($companyId) {
            $sql .= " JOIN contracts c ON mcr.contract_id = c.id";
            $where[] = "c.company_id = :company_id";
            $params['company_id'] = $companyId;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " GROUP BY cph.action_type ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 最新の履歴を取得
     */
    public function findLatest($limit = 10)
    {
        $sql = "SELECT cph.*, u.name as action_by_name, mcr.closing_period,
                       comp.name as company_name, b.name as branch_name, doc.name as doctor_name
                FROM closing_process_history cph
                JOIN users u ON cph.action_by = u.id
                JOIN monthly_closing_records mcr ON cph.monthly_closing_record_id = mcr.id
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                JOIN users doc ON mcr.doctor_id = doc.id
                ORDER BY cph.action_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * アクション種別のラベルを取得
     */
    public function getActionTypeLabel($actionType)
    {
        $labels = [
            'simulate' => 'シミュレーション実行',
            'finalize' => '締め処理確定',
            'reopen' => '締め処理取り消し'
        ];
        
        return $labels[$actionType] ?? $actionType;
    }
    
    /**
     * 履歴データをフォーマット
     */
    public function formatHistoryData($history)
    {
        $formatted = [];
        
        foreach ($history as $record) {
            $formatted[] = [
                'id' => $record['id'],
                'action_type' => $record['action_type'],
                'action_type_label' => $this->getActionTypeLabel($record['action_type']),
                'action_by_name' => $record['action_by_name'],
                'comment' => $record['comment'],
                'action_at' => $record['action_at'],
                'action_at_formatted' => date('Y年m月d日 H:i', strtotime($record['action_at'])),
                'closing_period' => $record['closing_period'] ?? '',
                'closing_period_formatted' => isset($record['closing_period']) ? 
                    date('Y年m月', strtotime($record['closing_period'] . '-01')) : '',
                'company_name' => $record['company_name'] ?? '',
                'branch_name' => $record['branch_name'] ?? '',
                'doctor_name' => $record['doctor_name'] ?? '',
                'has_before_data' => !empty($record['before_data']),
                'has_after_data' => !empty($record['after_data'])
            ];
        }
        
        return $formatted;
    }
    
    /**
     * 履歴レコードを削除（物理削除）
     */
    public function delete($id)
    {
        $sql = "DELETE FROM closing_process_history WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * 月次締め記録に関連する履歴を全て削除
     */
    public function deleteByClosingRecordId($closingRecordId)
    {
        $sql = "DELETE FROM closing_process_history WHERE monthly_closing_record_id = :closing_record_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['closing_record_id' => $closingRecordId]);
    }
}
?>