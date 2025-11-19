<?php
// app/models/ServiceRecordHistory.php
require_once __DIR__ . '/BaseModel.php';

class ServiceRecordHistory extends BaseModel {
    protected $table = 'service_record_history';
    
    /**
     * 履歴を記録
     * 
     * @param int $serviceRecordId 役務記録ID
     * @param string $actionType アクション種別
     * @param int $actionBy 実行者ID
     * @param string|null $statusFrom 変更前ステータス
     * @param string|null $statusTo 変更後ステータス
     * @param string|null $comment コメント
     * @param array $metadata メタデータ
     * @return int|bool 作成されたID or false
     */
    public function recordAction($serviceRecordId, $actionType, $actionBy, $statusFrom = null, $statusTo = null, $comment = null, $metadata = []) {
        $data = [
            'service_record_id' => $serviceRecordId,
            'action_type' => $actionType,
            'action_by' => $actionBy,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'comment' => $comment,
            'action_at' => date('Y-m-d H:i:s'),
            'metadata' => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null
        ];
        
        return $this->create($data);
    }
    
    /**
     * 特定の役務記録の履歴を取得
     * 
     * @param int $serviceRecordId 役務記録ID
     * @return array 履歴一覧
     */
    public function getHistoryByServiceRecord($serviceRecordId) {
        $sql = "SELECT srh.*, 
                    u.name as action_by_name,
                    u.user_type as action_by_type
                FROM {$this->table} srh
                LEFT JOIN users u ON srh.action_by = u.id
                WHERE srh.service_record_id = :service_record_id
                ORDER BY srh.action_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['service_record_id' => $serviceRecordId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 履歴の統計情報を取得
     * 
     * @param int $serviceRecordId 役務記録ID
     * @return array 統計情報
     */
    public function getHistoryStats($serviceRecordId) {
        $sql = "SELECT 
                    COUNT(*) as total_actions,
                    COUNT(CASE WHEN action_type = 'approved' THEN 1 END) as approval_count,
                    COUNT(CASE WHEN action_type = 'rejected' THEN 1 END) as rejection_count,
                    COUNT(CASE WHEN action_type = 'resubmitted' THEN 1 END) as resubmit_count,
                    MIN(action_at) as first_action,
                    MAX(action_at) as last_action
                FROM {$this->table}
                WHERE service_record_id = :service_record_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['service_record_id' => $serviceRecordId]);
        
        return $stmt->fetch();
    }
    
    /**
     * 現在のワークフローステータスを取得
     * 
     * @param int $serviceRecordId 役務記録ID
     * @return array ワークフロー情報
     */
    public function getWorkflowStatus($serviceRecordId) {
        $history = $this->getHistoryByServiceRecord($serviceRecordId);
        $stats = $this->getHistoryStats($serviceRecordId);
        
        if (empty($history)) {
            return [
                'current_status' => 'created',
                'workflow_stage' => 1,
                'total_stages' => count($history) + 1,
                'has_been_rejected' => false,
                'rejection_count' => 0,
                'approval_count' => 0,
                'last_action' => null
            ];
        }
        
        $lastAction = end($history);
        $hasRejection = !empty(array_filter($history, function($h) { return $h['action_type'] === 'rejected'; }));
        
        return [
            'current_status' => $lastAction['status_to'] ?? 'pending',
            'workflow_stage' => count($history) + 1,
            'total_stages' => count($history) + 1,
            'has_been_rejected' => $hasRejection,
            'rejection_count' => $stats['rejection_count'] ?? 0,
            'approval_count' => $stats['approval_count'] ?? 0,
            'resubmit_count' => $stats['resubmit_count'] ?? 0,
            'last_action' => $lastAction
        ];
    }
    
    /**
     * アクション種別のラベルを取得
     * 
     * @param string $actionType アクション種別
     * @return string ラベル
     */
    public function getActionTypeLabel($actionType) {
        $labels = [
            'created' => '新規作成',
            'updated' => '修正',
            'approved' => '承認',
            'rejected' => '差戻し',
            'resubmitted' => '再提出',
            'deleted' => '削除',
            'unapproved' => '承認取消',
            'travel_expense_added' => '交通費追加',
            'travel_expense_updated' => '交通費変更',
            'travel_expense_deleted' => '交通費削除'
        ];
        
        return $labels[$actionType] ?? $actionType;
    }
    
    /**
     * アクション種別のアイコンを取得
     * 
     * @param string $actionType アクション種別
     * @return string アイコンクラス
     */
    public function getActionTypeIcon($actionType) {
        $icons = [
            'created' => 'fas fa-plus-circle text-primary',
            'updated' => 'fas fa-edit text-warning',
            'approved' => 'fas fa-check-circle text-success',
            'rejected' => 'fas fa-times-circle text-danger',
            'resubmitted' => 'fas fa-redo text-info',
            'deleted' => 'fas fa-trash-alt text-danger',
            'unapproved' => 'fas fa-undo text-warning',
            'travel_expense_added' => 'fas fa-plus-square text-success',
            'travel_expense_updated' => 'fas fa-edit text-info',
            'travel_expense_deleted' => 'fas fa-minus-square text-danger'
        ];
        
        return $icons[$actionType] ?? 'fas fa-question-circle text-muted';
    }
    
    /**
     * ユーザータイプのラベルを取得
     * 
     * @param string $userType ユーザータイプ
     * @return string ラベル
     */
    public function getUserTypeLabel($userType) {
        $labels = [
            'doctor' => '産業医',
            'company' => '企業',
            'admin' => '管理者'
        ];
        
        return $labels[$userType] ?? $userType;
    }
    
    /**
     * 期間内の履歴を取得（統計用）
     * 
     * @param string $startDate 開始日
     * @param string $endDate 終了日
     * @param int|null $userId ユーザーID（指定時はそのユーザーの履歴のみ）
     * @return array 履歴一覧
     */
    public function getHistoryByPeriod($startDate, $endDate, $userId = null) {
        $sql = "SELECT srh.*, 
                    u.name as action_by_name,
                    u.user_type as action_by_type,
                    sr.service_date,
                    comp.name as company_name,
                    b.name as branch_name,
                    dr.name as doctor_name
                FROM {$this->table} srh
                LEFT JOIN users u ON srh.action_by = u.id
                LEFT JOIN service_records sr ON srh.service_record_id = sr.id
                LEFT JOIN contracts co ON sr.contract_id = co.id
                LEFT JOIN companies comp ON co.company_id = comp.id
                LEFT JOIN branches b ON co.branch_id = b.id
                LEFT JOIN users dr ON sr.doctor_id = dr.id
                WHERE srh.action_at BETWEEN :start_date AND :end_date";
        
        $params = [
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59'
        ];
        
        if ($userId) {
            $sql .= " AND srh.action_by = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " ORDER BY srh.action_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 履歴の詳細情報をフォーマット
     * 
     * @param array $history 履歴データ
     * @return array フォーマット済み履歴
     */
    public function formatHistoryForDisplay($history) {
        if (empty($history)) {
            return [];
        }
        
        return array_map(function($item) {
            $metadata = !empty($item['metadata']) ? json_decode($item['metadata'], true) : [];
            
            return [
                'id' => $item['id'],
                'action_type' => $item['action_type'],
                'action_type_label' => $this->getActionTypeLabel($item['action_type']),
                'action_type_icon' => $this->getActionTypeIcon($item['action_type']),
                'status_from' => $item['status_from'],
                'status_to' => $item['status_to'],
                'comment' => $item['comment'],
                'action_by_name' => $item['action_by_name'] ?? '不明',
                'action_by_type' => $item['action_by_type'],
                'action_by_type_label' => $this->getUserTypeLabel($item['action_by_type'] ?? ''),
                'action_at' => $item['action_at'],
                'action_at_formatted' => date('Y年m月d日 H:i', strtotime($item['action_at'])),
                'metadata' => $metadata,
                'has_comment' => !empty($item['comment']),
                'is_status_change' => !empty($item['status_from']) && !empty($item['status_to']) && $item['status_from'] !== $item['status_to']
            ];
        }, $history);
    }
    
    /**
     * 削除された記録の履歴を取得
     * 
     * @param int|null $contractId 契約ID（指定時はその契約の削除履歴のみ）
     * @param string|null $startDate 開始日（指定時はその期間の削除履歴）
     * @param string|null $endDate 終了日
     * @return array 削除履歴一覧
     */
    public function getDeletedRecordsHistory($contractId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT srh.*, 
                    u.name as action_by_name,
                    u.user_type as action_by_type
                FROM {$this->table} srh
                LEFT JOIN users u ON srh.action_by = u.id
                WHERE srh.service_record_id IS NULL 
                AND srh.action_type = 'deleted'";
        
        $params = [];
        
        // 契約IDで絞り込み（metadataから取得）
        if ($contractId) {
            $sql .= " AND JSON_EXTRACT(srh.metadata, '$.deleted_record.contract_id') = :contract_id";
            $params['contract_id'] = $contractId;
        }
        
        // 日付範囲で絞り込み
        if ($startDate && $endDate) {
            $sql .= " AND srh.action_at BETWEEN :start_date AND :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " ORDER BY srh.action_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}
?>