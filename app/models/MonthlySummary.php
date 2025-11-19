<?php
// app/models/MonthlySummary.php
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Contract.php';
require_once __DIR__ . '/ServiceRecord.php';

class MonthlySummary extends BaseModel {
    protected $table = 'monthly_service_summary';
    
    public function updateSummary($contractId, $year, $month) {
        // 契約情報を取得
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract) {
            return false;
        }
        
        // 役務記録を集計（役務種別ごと）
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // まず、テーブル構造を確認
        $tableInfo = $this->getTableColumns();
        
        // 役務種別・ステータス別の時間を集計
        $sql = "SELECT 
                    COALESCE(service_type, 'regular') as service_type,
                    status,
                    SUM(service_hours) as hours
                FROM service_records 
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                GROUP BY COALESCE(service_type, 'regular'), status";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        $results = $stmt->fetchAll();
        
        // 結果を整理
        $statistics = [
            'regular' => ['approved' => 0, 'pending' => 0, 'rejected' => 0],
            'emergency' => ['approved' => 0, 'pending' => 0, 'rejected' => 0],
            'extension' => ['approved' => 0, 'pending' => 0, 'rejected' => 0]
        ];
        
        foreach ($results as $result) {
            $serviceType = $result['service_type'] ?? 'regular';
            $status = $result['status'];
            $hours = $result['hours'];
            
            if (isset($statistics[$serviceType][$status])) {
                $statistics[$serviceType][$status] = $hours;
            }
        }
        
        // 各種別の合計時間を計算
        $regularHours = array_sum($statistics['regular']);
        $emergencyHours = array_sum($statistics['emergency']);
        $extensionHours = array_sum($statistics['extension']);
        $totalHours = $regularHours + $emergencyHours + $extensionHours;
        
        // ステータス別の合計時間を計算
        $approvedHours = $statistics['regular']['approved'] + 
                        $statistics['emergency']['approved'] + 
                        $statistics['extension']['approved'];
        
        $pendingHours = $statistics['regular']['pending'] + 
                       $statistics['emergency']['pending'] + 
                       $statistics['extension']['pending'];
        
        $rejectedHours = $statistics['regular']['rejected'] + 
                        $statistics['extension']['rejected'] + 
                        $statistics['extension']['rejected'];
        
        // 定期訪問の延長時間を計算
        $regularOvertimeHours = max(0, $regularHours - $contract['regular_visit_hours']);
        
        // 既存のサマリーを確認
        $existingSummary = $this->findAll([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        // 基本データ
        $summaryData = [
            'contract_id' => $contractId,
            'doctor_id' => $contract['doctor_id'],
            'year' => $year,
            'month' => $month,
            'total_hours' => $totalHours,
            'contract_hours' => $contract['regular_visit_hours'],
            'overtime_hours' => $regularOvertimeHours, // 従来の互換性のため
            'approved_hours' => $approvedHours,
            'pending_hours' => $pendingHours,
            'rejected_hours' => $rejectedHours,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // 新しいカラムが存在する場合のみ追加
        if (in_array('regular_hours', $tableInfo)) {
            $summaryData['regular_hours'] = $regularHours;
        }
        if (in_array('emergency_hours', $tableInfo)) {
            $summaryData['emergency_hours'] = $emergencyHours;
        }
        if (in_array('extension_hours', $tableInfo)) {
            $summaryData['extension_hours'] = $extensionHours;
        }
        if (in_array('regular_overtime_hours', $tableInfo)) {
            $summaryData['regular_overtime_hours'] = $regularOvertimeHours;
        }
        
        if (empty($existingSummary)) {
            $summaryData['created_at'] = date('Y-m-d H:i:s');
            return $this->create($summaryData);
        } else {
            return $this->update($existingSummary[0]['id'], $summaryData);
        }
    }
    
    /**
     * テーブルのカラム情報を取得
     */
    private function getTableColumns() {
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $sql = "SHOW COLUMNS FROM {$this->table}";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $columns = $stmt->fetchAll();
            
            return array_column($columns, 'Field');
        } catch (Exception $e) {
            error_log('Error getting table columns: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 役務種別ごとの月次統計を取得（下位互換性あり）
     */
    public function getServiceTypeStatistics($contractId, $year, $month) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT 
                    COALESCE(service_type, 'regular') as service_type,
                    status,
                    COUNT(*) as record_count,
                    SUM(service_hours) as total_hours,
                    AVG(service_hours) as avg_hours,
                    MIN(service_hours) as min_hours,
                    MAX(service_hours) as max_hours
                FROM service_records 
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                GROUP BY COALESCE(service_type, 'regular'), status
                ORDER BY service_type, status";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 簡易版updateSummary（デバッグ機能付き）
     */
    public function updateSummaryBasic($contractId, $year, $month) {
        
        // 契約情報を取得
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract) {
            error_log("Contract not found: $contractId");
            return false;
        }
                
        // 従来形式での統計取得
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // ステータス別の時間を取得
        $sql = "SELECT 
                    status,
                    SUM(service_hours) as total_hours,
                    COUNT(*) as record_count
                FROM service_records 
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                GROUP BY status";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        $results = $stmt->fetchAll();
       
        $hoursByStatus = [
            'approved' => 0,
            'pending' => 0,
            'rejected' => 0
        ];
        
        foreach ($results as $result) {
            if (isset($hoursByStatus[$result['status']])) {
                $hoursByStatus[$result['status']] = (float)$result['total_hours'];
            }
        }
        
        $totalHours = array_sum($hoursByStatus);
        $overtimeHours = max(0, $totalHours - $contract['regular_visit_hours']);
        
        // 既存のサマリーを確認
        $existingSummary = $this->findAll([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        $summaryData = [
            'contract_id' => (int)$contractId,
            'doctor_id' => (int)$contract['doctor_id'],
            'year' => (int)$year,
            'month' => (int)$month,
            'total_hours' => (float)$totalHours,
            'contract_hours' => (float)$contract['regular_visit_hours'],
            'overtime_hours' => (float)$overtimeHours,
            'approved_hours' => (float)$hoursByStatus['approved'],
            'pending_hours' => (float)$hoursByStatus['pending'],
            'rejected_hours' => (float)$hoursByStatus['rejected'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            if (empty($existingSummary)) {
                $summaryData['created_at'] = date('Y-m-d H:i:s');
                $result = $this->create($summaryData);
                return $result;
            } else {
                $result = $this->update($existingSummary[0]['id'], $summaryData);
                return $result;
            }
        } catch (Exception $e) {
            error_log("Exception in updateSummaryBasic: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    public function findByDoctor($doctorId, $year = null) {
        $conditions = ['doctor_id' => $doctorId];
        
        if ($year) {
            $conditions['year'] = $year;
        }
        
        return $this->findAll($conditions, 'year DESC, month DESC');
    }
    
    public function findByContract($contractId, $year = null) {
        $conditions = ['contract_id' => $contractId];
        
        if ($year) {
            $conditions['year'] = $year;
        }
        
        return $this->findAll($conditions, 'year DESC, month DESC');
    }
}
?>