<?php
// app/models/ServiceRecord.php
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../core/helpers.php';

class ServiceRecord extends BaseModel {
    protected $table = 'service_records';
    
    public function findByDoctor($doctorId, $month = null, $year = null) {
        $sql = "SELECT sr.*, co.regular_visit_hours, c.name as company_name, b.name as branch_name,
                CONCAT(c.name, ' - ', b.name) as company_branch_name
                FROM {$this->table} sr
                JOIN contracts co ON sr.contract_id = co.id
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                WHERE sr.doctor_id = :doctor_id";
        
        $params = ['doctor_id' => $doctorId];
        
        if ($month && $year) {
            $sql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
            $params['year'] = $year;
            $params['month'] = $month;
        }
        
        $sql .= " ORDER BY sr.service_date DESC, sr.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function findByCompany($companyId, $month = null, $year = null) {
        $sql = "SELECT sr.*, u.name as doctor_name, co.regular_visit_hours, b.name as branch_name
                FROM {$this->table} sr
                JOIN contracts co ON sr.contract_id = co.id
                JOIN branches b ON co.branch_id = b.id
                JOIN users u ON sr.doctor_id = u.id
                WHERE co.company_id = :company_id";
        
        $params = ['company_id' => $companyId];
        
        if ($month && $year) {
            $sql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
            $params['year'] = $year;
            $params['month'] = $month;
        }
        
        $sql .= " ORDER BY sr.service_date DESC, sr.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function findPendingByCompany($companyId) {
        $sql = "SELECT sr.*, u.name as doctor_name, co.regular_visit_hours, b.name as branch_name
                FROM {$this->table} sr
                JOIN contracts co ON sr.contract_id = co.id
                JOIN branches b ON co.branch_id = b.id
                JOIN users u ON sr.doctor_id = u.id
                WHERE co.company_id = :company_id AND sr.status = 'pending'
                ORDER BY sr.service_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 役務記録作成（新役務種別対応版）
     */
    public function createServiceRecord($data) {
        // 役務種別のデフォルト値設定
        if (!isset($data['service_type'])) {
            $data['service_type'] = 'regular';
        }
        
        // 訪問種別のデフォルト値設定
        if (!isset($data['visit_type'])) {
            // 訪問種別が必要な役務種別の場合はデフォルトで'visit'
            if (requires_visit_type_selection($data['service_type'])) {
                $data['visit_type'] = 'visit';
            } else {
                // 書面作成・遠隔相談はnull
                $data['visit_type'] = null;
            }
        }
        
        // 時間入力が必要な役務種別の場合のみ役務時間を計算
        if ($this->requiresTimeInput($data['service_type'])) {
            if (!empty($data['start_time']) && !empty($data['end_time'])) {
                $data['service_hours'] = calculate_hours($data['start_time'], $data['end_time']);
            } else {
                throw new Exception('開始時刻と終了時刻が必要です。');
            }
        } else {
            // 書面作成・遠隔相談の場合は時間を0に設定
            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['service_hours'] = 0;
            $data['visit_type'] = null; // 訪問種別も不要
        }
        
        return $this->create($data);
    }
    
    /**
     * 役務記録更新（新役務種別対応版）
     */
    public function updateServiceRecord($id, $data) {
        // 役務種別のチェック
        $serviceType = $data['service_type'] ?? 'regular';
        
        // 訪問種別の設定
        if (requires_visit_type_selection($serviceType)) {
            // 訪問種別が必要な場合でデータがない場合はデフォルト値を設定
            if (!isset($data['visit_type'])) {
                $data['visit_type'] = 'visit';
            }
        } else {
            // 訪問種別が不要な場合はnullに設定
            $data['visit_type'] = null;
        }
        
        // 時間入力が必要な役務種別の場合のみ役務時間を再計算
        if ($this->requiresTimeInput($serviceType)) {
            if (isset($data['start_time']) && isset($data['end_time']) && 
                !empty($data['start_time']) && !empty($data['end_time'])) {
                $data['service_hours'] = calculate_hours($data['start_time'], $data['end_time']);
            }
        } else {
            // 書面作成・遠隔相談の場合は時間をnullまたは0に設定
            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['service_hours'] = 0;
            $data['visit_type'] = null;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * 時間入力が必要な役務種別かどうかを判定
     */
    private function requiresTimeInput($serviceType) {
        $timeRequiredTypes = ['regular', 'emergency', 'extension', 'spot'];
        return in_array($serviceType, $timeRequiredTypes);
    }
    
    /**
     * 月間定期訪問時間の対象となる役務種別かどうかを判定
     */
    private function countsTowardRegularHours($serviceType) {
        return $serviceType === 'regular';
    }
    
    public function approveRecord($id, $comment = null) {
        $data = [
            'status' => 'approved',
            'approved_at' => date('Y-m-d H:i:s')
        ];
        
        if ($comment) {
            $data['company_comment'] = $comment;
        }
        
        return $this->update($id, $data);
    }
    
    public function rejectRecord($id, $comment) {
        return $this->update($id, [
            'status' => 'rejected',
            'rejected_at' => date('Y-m-d H:i:s'),
            'company_comment' => $comment
        ]);
    }
    
    /**
     * 月間総役務時間を取得（時間ありの役務のみ）
     */
    public function getMonthlyHours($contractId, $year, $month) {
        $sql = "SELECT SUM(service_hours) as total_hours
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                AND status IN ('pending', 'approved')
                AND service_type IN ('regular', 'emergency', 'extension', 'spot')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        $result = $stmt->fetch();
        return $result ? (float)$result['total_hours'] : 0;
    }
    
    /**
     * 月間定期訪問時間を取得
     */
    public function getMonthlyRegularHours($contractId, $year, $month) {
        $sql = "SELECT SUM(service_hours) as total_hours
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                AND service_type = 'regular'
                AND status IN ('pending', 'approved')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        $result = $stmt->fetch();
        return $result ? (float)$result['total_hours'] : 0;
    }
    
    /**
     * 役務種別ごとの月間時間を取得（新役務種別対応版）
     */
    public function getMonthlyHoursByServiceType($contractId, $year, $month) {
        $sql = "SELECT 
                    COALESCE(service_type, 'regular') as service_type, 
                    SUM(service_hours) as total_hours, 
                    COUNT(*) as record_count
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                AND status IN ('pending', 'approved')
                GROUP BY COALESCE(service_type, 'regular')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        $results = $stmt->fetchAll();
        
        // 新しい役務種別も含めた初期化
        $summary = [
            'regular' => ['hours' => 0, 'count' => 0],
            'emergency' => ['hours' => 0, 'count' => 0],
            'extension' => ['hours' => 0, 'count' => 0],
            'document' => ['hours' => 0, 'count' => 0],
            'remote_consultation' => ['hours' => 0, 'count' => 0],
            'spot' => ['hours' => 0, 'count' => 0]
        ];
        
        foreach ($results as $result) {
            $serviceType = $result['service_type'] ?? 'regular';
            if (isset($summary[$serviceType])) {
                $summary[$serviceType]['hours'] = (float)$result['total_hours'];
                $summary[$serviceType]['count'] = (int)$result['record_count'];
            }
        }
        
        return $summary;
    }
    
    /**
     * ステータス別の月間時間を取得
     */
    public function getMonthlyHoursByStatus($contractId, $year, $month) {
        $sql = "SELECT status, SUM(service_hours) as total_hours
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                GROUP BY status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        $results = $stmt->fetchAll();
        $summary = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];
        
        foreach ($results as $result) {
            $summary[$result['status']] = (float)$result['total_hours'];
        }
        
        return $summary;
    }
    
    /**
     * 役務種別とステータス別の詳細統計を取得（新役務種別対応版）
     */
    public function getDetailedMonthlyStatistics($contractId, $year, $month) {
        $sql = "SELECT 
                    service_type,
                    status,
                    SUM(service_hours) as total_hours,
                    COUNT(*) as record_count,
                    AVG(service_hours) as avg_hours,
                    MIN(service_hours) as min_hours,
                    MAX(service_hours) as max_hours
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                GROUP BY service_type, status
                ORDER BY 
                    CASE service_type
                        WHEN 'regular' THEN 1
                        WHEN 'extension' THEN 2
                        WHEN 'emergency' THEN 3
                        WHEN 'document' THEN 4
                        WHEN 'remote_consultation' THEN 5
                        WHEN 'spot' THEN 6
                        ELSE 6
                    END, status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 進行中の役務を取得（時間管理が必要な役務のみ）
     */
    public function getActiveService($doctorId) {
        $sql = "SELECT sr.*, co.regular_visit_hours, c.name as company_name, b.name as branch_name
                FROM {$this->table} sr
                JOIN contracts co ON sr.contract_id = co.id
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                WHERE sr.doctor_id = :doctor_id 
                AND sr.service_hours = 0 
                AND sr.start_time = sr.end_time
                AND sr.service_date = CURDATE()
                AND sr.service_type IN ('regular', 'emergency', 'extension', 'spot')
                ORDER BY sr.created_at DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        return $stmt->fetch();
    }
    
    /**
     * 定期訪問の残り時間を計算
     */
    public function getRemainingRegularHours($contractId, $year, $month) {
        // 契約の定期訪問時間を取得
        require_once __DIR__ . '/Contract.php';
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract) {
            return 0;
        }
        
        // 使用済み定期訪問時間を取得
        $usedHours = $this->getMonthlyRegularHours($contractId, $year, $month);
        
        return max(0, $contract['regular_visit_hours'] - $usedHours);
    }
    
    /**
     * 定期訪問の使用率を計算
     */
    public function getRegularHoursUsageRate($contractId, $year, $month) {
        require_once __DIR__ . '/Contract.php';
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract || $contract['regular_visit_hours'] <= 0) {
            return 0;
        }
        
        $usedHours = $this->getMonthlyRegularHours($contractId, $year, $month);
        
        return ($usedHours / $contract['regular_visit_hours']) * 100;
    }
    
    /**
     * 年間統計を取得（新役務種別対応版）
     */
    public function getAnnualStatistics($contractId, $year) {
        $sql = "SELECT 
                    MONTH(service_date) as month,
                    service_type,
                    status,
                    SUM(service_hours) as total_hours,
                    COUNT(*) as record_count
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year
                GROUP BY MONTH(service_date), service_type, status
                ORDER BY month, 
                    CASE service_type
                        WHEN 'regular' THEN 1
                        WHEN 'extension' THEN 2
                        WHEN 'emergency' THEN 3
                        WHEN 'document' THEN 4
                        WHEN 'remote_consultation' THEN 5
                        WHEN 'spot' THEN 6
                        ELSE 7
                    END, status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 延長時間の自動分割処理
     */
    public function splitOvertimeToExtension($recordId, $regularHours, $extensionHours, $overtimeReason = '', $originalVisitType = 'visit') {
        // 元の記録を取得
        $originalRecord = $this->findById($recordId);
        if (!$originalRecord) {
            return false;
        }
        
        // 元の記録を定期訪問分に更新
        $regularData = [
            'service_hours' => $regularHours,
            'overtime_reason' => $overtimeReason
        ];
        
        if (!$this->update($recordId, $regularData)) {
            return false;
        }
        
        // 定期延長分を新規作成
        if ($extensionHours > 0) {
            $extensionData = [
                'contract_id' => $originalRecord['contract_id'],
                'doctor_id' => $originalRecord['doctor_id'],
                'service_date' => $originalRecord['service_date'],
                'start_time' => $originalRecord['end_time'],
                'end_time' => $originalRecord['end_time'],
                'service_hours' => $extensionHours,
                'service_type' => 'extension',
                'visit_type' => $originalVisitType, // 元の訪問種別を引き継ぐ
                'status' => 'pending',
                'description' => '定期訪問の延長分',
                'overtime_reason' => $overtimeReason,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->create($extensionData);
        }
        
        return true;
    }

    /**
     * 役務種別・訪問種別ごとの月間時間を取得
     */
    public function getMonthlyHoursByServiceAndVisitType($contractId, $year, $month) {
        $sql = "SELECT 
                    COALESCE(service_type, 'regular') as service_type,
                    COALESCE(visit_type, 'visit') as visit_type,
                    SUM(service_hours) as total_hours, 
                    COUNT(*) as record_count
                FROM {$this->table}
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month
                AND status IN ('pending', 'approved')
                GROUP BY COALESCE(service_type, 'regular'), COALESCE(visit_type, 'visit')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        $results = $stmt->fetchAll();
        
        // 結果を整理
        $summary = [
            'regular' => [
                'visit' => ['hours' => 0, 'count' => 0],
                'online' => ['hours' => 0, 'count' => 0]
            ],
            'emergency' => [
                'visit' => ['hours' => 0, 'count' => 0],
                'online' => ['hours' => 0, 'count' => 0]
            ],
            'extension' => [
                'visit' => ['hours' => 0, 'count' => 0],
                'online' => ['hours' => 0, 'count' => 0]
            ],
            'spot' => [
                'visit' => ['hours' => 0, 'count' => 0],
                'online' => ['hours' => 0, 'count' => 0]
            ],
            'document' => ['hours' => 0, 'count' => 0],
            'remote_consultation' => ['hours' => 0, 'count' => 0]
        ];
        
        foreach ($results as $result) {
            $serviceType = $result['service_type'] ?? 'regular';
            $visitType = $result['visit_type'] ?? 'visit';
            
            if (requires_visit_type_selection($serviceType)) {
                if (isset($summary[$serviceType][$visitType])) {
                    $summary[$serviceType][$visitType]['hours'] = (float)$result['total_hours'];
                    $summary[$serviceType][$visitType]['count'] = (int)$result['record_count'];
                }
            } else {
                if (isset($summary[$serviceType])) {
                    $summary[$serviceType]['hours'] = (float)$result['total_hours'];
                    $summary[$serviceType]['count'] = (int)$result['record_count'];
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * 訪問種別の統計情報を取得
     */
    public function getVisitTypeStatistics($doctorId = null, $year = null, $month = null) {
        $sql = "SELECT 
                    service_type,
                    visit_type,
                    COUNT(*) as record_count,
                    SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as total_hours
                FROM {$this->table}
                WHERE visit_type IS NOT NULL";
        
        $params = [];
        
        if ($doctorId) {
            $sql .= " AND doctor_id = :doctor_id";
            $params['doctor_id'] = $doctorId;
        }
        
        if ($year) {
            $sql .= " AND YEAR(service_date) = :year";
            $params['year'] = $year;
        }
        
        if ($month) {
            $sql .= " AND MONTH(service_date) = :month";
            $params['month'] = $month;
        }
        
        $sql .= " GROUP BY service_type, visit_type
                ORDER BY service_type, visit_type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 役務記録の削除（関連データも含む）
     */
    public function deleteWithRelated($id) {
        // トランザクション処理が必要な場合は実装
        return $this->delete($id);
    }

    /**
     * 月間記録取得（新役務種別対応版）
     */
    public function getMonthlyRecords($contractId, $year, $month) {
        $sql = "SELECT sr.*, 
                    c.company_name, c.branch_name, c.regular_visit_hours,
                    u.name as doctor_name
                FROM service_records sr
                LEFT JOIN contracts c ON sr.contract_id = c.id
                LEFT JOIN users u ON sr.doctor_id = u.id
                WHERE sr.contract_id = :contract_id 
                AND YEAR(sr.service_date) = :year 
                AND MONTH(sr.service_date) = :month
                ORDER BY sr.service_date DESC, 
                    CASE sr.service_type
                        WHEN 'regular' THEN 1
                        WHEN 'extension' THEN 2
                        WHEN 'emergency' THEN 3
                        WHEN 'document' THEN 4
                        WHEN 'remote_consultation' THEN 5
                        WHEN 'spot' THEN 6
                        ELSE 7
                    END,
                    sr.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 月間統計取得（新役務種別対応版）
     */
    public function getMonthlyStats($contractId, $year, $month) {
        $sql = "SELECT 
                    SUM(CASE WHEN service_type = 'regular' THEN service_hours ELSE 0 END) as regular_hours,
                    SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                    SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                    SUM(CASE WHEN service_type = 'spot' THEN service_hours ELSE 0 END) as spot_hours,
                    COUNT(CASE WHEN service_type = 'regular' THEN 1 END) as regular_count,
                    COUNT(CASE WHEN service_type = 'emergency' THEN 1 END) as emergency_count,
                    COUNT(CASE WHEN service_type = 'extension' THEN 1 END) as extension_count,
                    COUNT(CASE WHEN service_type = 'spot' THEN 1 END) as spot_count,
                    COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                    COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                    SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as total_hours,
                    COUNT(*) as total_count
                FROM service_records 
                WHERE contract_id = :contract_id 
                AND YEAR(service_date) = :year 
                AND MONTH(service_date) = :month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 記録検索（新役務種別対応版）
     */
    public function findById($id) {
        $sql = "SELECT sr.*, 
                    c.regular_visit_hours,
                    comp.name as company_name,
                    b.name as branch_name,
                    u.name as doctor_name
                FROM service_records sr
                LEFT JOIN contracts c ON sr.contract_id = c.id
                LEFT JOIN companies comp ON c.company_id = comp.id
                LEFT JOIN branches b ON c.branch_id = b.id
                LEFT JOIN users u ON sr.doctor_id = u.id
                WHERE sr.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 訪問種別がnullの場合は既存データとして'visit'を設定
        if ($record && is_null($record['visit_type']) && requires_visit_type_selection($record['service_type'] ?? 'regular')) {
            $record['visit_type'] = 'visit';
        }
        
        return $record;
    }

    /**
     * 役務記録の削除
     * 
     * @param int $id 記録ID
     * @return bool 削除成功の場合true
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM service_records WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['id' => $id]);
            
            if ($result) {
                error_log("Service record deleted: ID {$id}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Service record deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 履歴付きで役務記録を削除
     * 
     * 注意: この機能を使用する前に、以下のマイグレーションを実行してください：
     * - service_record_idカラムをNULL許容に変更
     * - 外部キー制約をON DELETE SET NULLに変更
     * 
     * @param int $id 記録ID
     * @param int|null $deletedBy 削除者のユーザーID
     * @return bool 削除成功の場合true
     */
    public function deleteWithHistory($id, $deletedBy = null) {
        // 削除前に現在の記録を取得
        $record = $this->findById($id);
        if (!$record) {
            return false;
        }
        
        // 削除履歴を記録
        if ($deletedBy) {
            require_once __DIR__ . '/ServiceRecordHistory.php';
            $historyModel = new ServiceRecordHistory();
            
            // 削除前の状態をメタデータとして保存
            $metadata = [
                'deleted_record' => [
                    'id' => $record['id'],
                    'service_date' => $record['service_date'],
                    'service_type' => $record['service_type'],
                    'service_hours' => $record['service_hours'],
                    'start_time' => $record['start_time'],
                    'end_time' => $record['end_time'],
                    'visit_type' => $record['visit_type'],
                    'description' => $record['description'],
                    'contract_id' => $record['contract_id'],
                    'status' => $record['status']
                ]
            ];
            
            $historyModel->recordAction(
                $id,
                'deleted',
                $deletedBy,
                $record['status'],
                'deleted',
                '役務記録を削除',
                $metadata
            );
        }
        
        // 実際の削除を実行
        // マイグレーション実行後は、外部キー制約がON DELETE SET NULLになっているため、
        // service_record_historyのservice_record_idは自動的にNULLになり、履歴は残る
        return $this->delete($id);
    }

    /**
     * 論理削除（is_deletedフラグを使用する場合）
     * 
     * @param int $id 記録ID
     * @param array $deleteData 削除関連データ
     * @return bool 削除成功の場合true
     */
    public function softDelete($id, $deleteData = []) {
        try {
            $defaultData = [
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ];
            
            $data = array_merge($defaultData, $deleteData);
            
            return $this->update($id, $data);
            
        } catch (Exception $e) {
            error_log('Service record soft deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 削除前の関連データチェック（新役務種別対応版）
     * 
     * @param int $id 記録ID
     * @return array チェック結果
     */
    public function checkBeforeDelete($id) {
        try {
            $record = $this->findById($id);
            if (!$record) {
                return ['can_delete' => false, 'reason' => '記録が見つかりません'];
            }
            
            // 進行中チェック（時間管理が必要な役務のみ）
            if ($this->requiresTimeInput($record['service_type'] ?? 'regular') && $record['service_hours'] == 0) {
                return ['can_delete' => false, 'reason' => '進行中の記録は削除できません'];
            }
            
            // 承認済みチェック
            if ($record['status'] === 'approved') {
                return ['can_delete' => false, 'reason' => '承認済みの記録は削除できません'];
            }
            
            // 自動分割記録のチェック
            $isAutoSplit = ($record['is_auto_split'] ?? false) || 
                        strpos($record['description'] ?? '', '自動分割') !== false;
            
            if ($isAutoSplit) {
                // 関連記録の確認
                $relatedCount = $this->countRelatedAutoSplitRecords($record);
                if ($relatedCount > 0) {
                    return [
                        'can_delete' => true, 
                        'reason' => '',
                        'warning' => "この記録は自動分割記録です。関連する{$relatedCount}件の記録も影響を受ける可能性があります。"
                    ];
                }
            }
            
            return ['can_delete' => true, 'reason' => ''];
            
        } catch (Exception $e) {
            error_log('Delete check failed: ' . $e->getMessage());
            return ['can_delete' => false, 'reason' => 'システムエラーが発生しました'];
        }
    }

    /**
     * 関連する自動分割記録の件数を取得
     * 
     * @param array $record 記録データ
     * @return int 関連記録数
     */
    private function countRelatedAutoSplitRecords($record) {
        try {
            $sql = "SELECT COUNT(*) 
                    FROM service_records 
                    WHERE contract_id = :contract_id 
                    AND service_date = :service_date 
                    AND doctor_id = :doctor_id 
                    AND id != :exclude_id
                    AND (is_auto_split = 1 OR description LIKE '%自動分割%' OR description LIKE '%元の時間:%')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'contract_id' => $record['contract_id'],
                'service_date' => $record['service_date'],
                'doctor_id' => $record['doctor_id'],
                'exclude_id' => $record['id']
            ]);
            
            return (int)$stmt->fetchColumn();
            
        } catch (Exception $e) {
            error_log('Count related records failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 削除可能な記録の一覧を取得（産業医用）（新役務種別対応版）
     * 
     * @param int $doctorId 産業医ID
     * @return array 削除可能な記録一覧
     */
    public function getDeletableRecords($doctorId) {
        try {
            $sql = "SELECT sr.*, c.name as company_name, b.name as branch_name
                    FROM service_records sr
                    JOIN contracts co ON sr.contract_id = co.id
                    JOIN companies c ON co.company_id = c.id
                    JOIN branches b ON co.branch_id = b.id
                    WHERE sr.doctor_id = :doctor_id 
                    AND (
                        (sr.service_type IN ('regular', 'emergency', 'extension', 'spot') AND sr.service_hours > 0)
                        OR sr.service_type IN ('document', 'remote_consultation')
                    )
                    AND sr.status IN ('pending', 'rejected')
                    ORDER BY sr.service_date DESC, sr.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['doctor_id' => $doctorId]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log('Get deletable records failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 削除統計の取得
     * 
     * @param int $doctorId 産業医ID
     * @param string $period 期間（'month', 'year' など）
     * @return array 削除統計
     */
    public function getDeletionStats($doctorId, $period = 'month') {
        try {
            $dateCondition = $period === 'month' ? 
                "DATE_FORMAT(deleted_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')" :
                "YEAR(deleted_at) = YEAR(NOW())";
            
            $sql = "SELECT 
                        COUNT(*) as total_deleted,
                        SUM(service_hours) as total_deleted_hours
                    FROM service_records 
                    WHERE doctor_id = :doctor_id 
                    AND is_deleted = 1 
                    AND {$dateCondition}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['doctor_id' => $doctorId]);
            
            return $stmt->fetch() ?: ['total_deleted' => 0, 'total_deleted_hours' => 0];
            
        } catch (Exception $e) {
            error_log('Get deletion stats failed: ' . $e->getMessage());
            return ['total_deleted' => 0, 'total_deleted_hours' => 0];
        }
    }

    /**
     * 履歴付きで役務記録を承認
     */
    public function approveRecordWithHistory($id, $comment = null, $approvedBy = null) {
        // 現在の記録を取得
        $record = $this->findById($id);
        if (!$record) {
            return false;
        }
        
        $data = [
            'status' => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_by' => $approvedBy
        ];
        
        if ($comment) {
            $data['company_comment'] = $comment;
        }
        
        // 記録を更新
        $result = $this->update($id, $data);
        
        if ($result && $approvedBy) {
            // 履歴を記録
            require_once __DIR__ . '/ServiceRecordHistory.php';
            $historyModel = new ServiceRecordHistory();
            
            // 企業コメントをメタデータに保存し、履歴のcommentは固定文言
            $metadata = ['approved_at' => $data['approved_at']];
            if ($comment) {
                $metadata['company_comment'] = $comment;
            }
            
            $historyModel->recordAction(
                $id,
                'approved',
                $approvedBy,
                $record['status'],
                'approved',
                '役務記録を承認',  // 固定文言
                $metadata
            );
        }
        
        return $result;
    }

    /**
     * 履歴付きで役務記録を差戻し
     */
    public function rejectRecordWithHistory($id, $comment, $rejectedBy = null) {
        // 現在の記録を取得
        $record = $this->findById($id);
        if (!$record) {
            return false;
        }
        
        $data = [
            'status' => 'rejected',  
            'rejected_at' => date('Y-m-d H:i:s'),
            'rejected_by' => $rejectedBy,
            'company_comment' => $comment
        ];
        
        // 記録を更新
        $result = $this->update($id, $data);
        
        if ($result && $rejectedBy) {
            // 履歴を記録
            require_once __DIR__ . '/ServiceRecordHistory.php';
            $historyModel = new ServiceRecordHistory();
            
            // 差戻し理由をメタデータに保存し、履歴のcommentは固定文言
            $metadata = [
                'rejected_at' => $data['rejected_at'],
                'reject_reason' => $comment
            ];
            
            $historyModel->recordAction(
                $id,
                'rejected',
                $rejectedBy,
                $record['status'],
                'rejected',
                '役務記録を差戻し',  // 固定文言
                $metadata
            );
        }
        
        return $result;
    }

    /**
     * 履歴付きで役務記録の承認を取り消し
     */
    public function unapproveRecordWithHistory($id, $comment, $unapprovedBy = null) {
        // 現在の記録を取得
        $record = $this->findById($id);
        if (!$record) {
            return false;
        }
        
        $data = [
            'status' => 'pending',
            'company_comment' => $comment ?: null,
            'approved_at' => null,
            'approved_by' => null,
            'unapproved_at' => date('Y-m-d H:i:s'),
            'unapproved_by' => $unapprovedBy
        ];
        
        // 記録を更新
        $result = $this->update($id, $data);
        
        if ($result && $unapprovedBy) {
            // 履歴を記録
            require_once __DIR__ . '/ServiceRecordHistory.php';
            $historyModel = new ServiceRecordHistory();
            
            // 取消理由をメタデータに保存し、履歴のcommentは固定文言
            $metadata = ['unapproved_at' => $data['unapproved_at']];
            if ($comment) {
                $metadata['unapprove_reason'] = $comment;
            }
            
            $historyModel->recordAction(
                $id,
                'unapproved',
                $unapprovedBy,
                $record['status'],
                'pending',
                '役務記録の承認を取り消し',  // 固定文言
                $metadata
            );
        }
        
        return $result;
    }

    /**
     * 履歴付きで役務記録を更新（産業医による修正）
     */
    public function updateWithHistory($id, $data, $updatedBy = null) {
        // 現在の記録を取得
        $record = $this->findById($id);
        if (!$record) {
            return false;
        }
        
        $originalStatus = $record['status'];
        
        // 差し戻し記録を修正する場合はステータスを承認待ちに戻す（既存の処理を維持）
        if ($originalStatus === 'rejected') {
            $data['status'] = 'pending';
            $data['company_comment'] = null;
            $data['rejected_at'] = null;
            $data['rejected_by'] = null;
        }
        
        // 役務種別に応じた時間処理
        $serviceType = $data['service_type'] ?? $record['service_type'] ?? 'regular';
        if (!$this->requiresTimeInput($serviceType)) {
            // 書面作成・遠隔相談の場合は時間をnullに設定
            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['service_hours'] = 0;
        }
        
        // 記録を更新
        $result = $this->update($id, $data);
        
        if ($result && $updatedBy) {
            // 履歴を記録
            require_once __DIR__ . '/ServiceRecordHistory.php';
            $historyModel = new ServiceRecordHistory();
            
            $actionType = 'updated';
            $statusTo = $data['status'] ?? $originalStatus;
            $comment = '役務記録を変更';  // デフォルトのコメント
            $metadata = [];
            
            // 差し戻し後の修正の場合は再提出として記録
            if ($originalStatus === 'rejected' && $statusTo === 'pending') {
                $actionType = 'resubmitted';
                $comment = '差し戻された記録を修正して再提出';
                $metadata['resubmitted_from'] = 'rejected';
            }
            
            // 変更内容をメタデータに記録
            $changes = [];
            foreach (['service_date', 'start_time', 'end_time', 'service_type', 'description'] as $field) {
                if (isset($data[$field]) && $data[$field] !== $record[$field]) {
                    $changes[$field] = [
                        'from' => $record[$field],
                        'to' => $data[$field]
                    ];
                }
            }
            if (!empty($changes)) {
                $metadata['changes'] = $changes;
            }
            
            $historyModel->recordAction(
                $id,
                $actionType,
                $updatedBy,
                $originalStatus,
                $statusTo,
                $comment,
                $metadata
            );
        }
        
        return $result;
    }

    /**
     * 履歴付きで役務記録を作成
     */
    public function createWithHistory($data, $createdBy = null) {
        // 役務種別に応じた時間処理
        $serviceType = $data['service_type'] ?? 'regular';
        if (!$this->requiresTimeInput($serviceType)) {
            // 書面作成・遠隔相談の場合は時間をnullに設定
            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['service_hours'] = 0;
        }
        
        $result = $this->create($data);
        
        if ($result && $createdBy) {
            // 履歴を記録
            require_once __DIR__ . '/ServiceRecordHistory.php';
            $historyModel = new ServiceRecordHistory();
            $historyModel->recordAction(
                $result, // 作成されたID
                'created',
                $createdBy,
                null,
                'pending',
                '新規役務記録を作成',
                [
                    'service_date' => $data['service_date'],
                    'service_type' => $serviceType,
                    'service_hours' => $data['service_hours'] ?? 0
                ]
            );
        }
        
        return $result;
    }

    /**
     * 役務記録の履歴を取得
     */
    public function getRecordHistory($id) {
        require_once __DIR__ . '/ServiceRecordHistory.php';
        $historyModel = new ServiceRecordHistory();
        return $historyModel->getHistoryByServiceRecord($id);
    }

    /**
     * 役務記録の履歴統計を取得
     */
    public function getRecordHistoryStats($id) {
        require_once __DIR__ . '/ServiceRecordHistory.php';
        $historyModel = new ServiceRecordHistory();
        return $historyModel->getHistoryStats($id);
    }

    /**
     * ワークフローステータスを取得
     */
    public function getWorkflowStatus($id) {
        require_once __DIR__ . '/ServiceRecordHistory.php';
        $historyModel = new ServiceRecordHistory();
        return $historyModel->getWorkflowStatus($id);
    }

    /**
     * 役務種別の統計情報を取得（新役務種別対応版）
     */
    public function getServiceTypeStatistics($doctorId = null, $year = null, $month = null) {
        $sql = "SELECT 
                    service_type,
                    COUNT(*) as record_count,
                    SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as total_hours,
                    AVG(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') AND service_hours > 0 THEN service_hours ELSE NULL END) as avg_hours
                FROM {$this->table}
                WHERE 1=1";
        
        $params = [];
        
        if ($doctorId) {
            $sql .= " AND doctor_id = :doctor_id";
            $params['doctor_id'] = $doctorId;
        }
        
        if ($year) {
            $sql .= " AND YEAR(service_date) = :year";
            $params['year'] = $year;
        }
        
        if ($month) {
            $sql .= " AND MONTH(service_date) = :month";
            $params['month'] = $month;
        }
        
        $sql .= " GROUP BY service_type
                ORDER BY 
                    CASE service_type
                        WHEN 'regular' THEN 1
                        WHEN 'extension' THEN 2
                        WHEN 'emergency' THEN 3
                        WHEN 'document' THEN 4
                        WHEN 'remote_consultation' THEN 5
                        WHEN 'spot' THEN 6
                        ELSE 7
                    END";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * 役務種別別の月間推移を取得（新役務種別対応版）
     */
    public function getServiceTypeMonthlyTrend($doctorId = null, $year = null) {
        $sql = "SELECT 
                    YEAR(service_date) as year,
                    MONTH(service_date) as month,
                    service_type,
                    COUNT(*) as record_count,
                    SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension', 'spot') THEN service_hours ELSE 0 END) as total_hours
                FROM {$this->table}
                WHERE 1=1";
        
        $params = [];
        
        if ($doctorId) {
            $sql .= " AND doctor_id = :doctor_id";
            $params['doctor_id'] = $doctorId;
        }
        
        if ($year) {
            $sql .= " AND YEAR(service_date) = :year";
            $params['year'] = $year;
        } else {
            // 年指定がない場合は過去12ヶ月
            $sql .= " AND service_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        $sql .= " GROUP BY YEAR(service_date), MONTH(service_date), service_type
                ORDER BY year DESC, month DESC, 
                    CASE service_type
                        WHEN 'regular' THEN 1
                        WHEN 'extension' THEN 2
                        WHEN 'emergency' THEN 3
                        WHEN 'document' THEN 4
                        WHEN 'remote_consultation' THEN 5
                        WHEN 'spot' THEN 6
                        ELSE 7
                    END";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}
?>