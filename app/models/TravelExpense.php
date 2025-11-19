<?php
// app/models/TravelExpense.php
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../core/helpers.php';

class TravelExpense extends BaseModel {
    protected $table = 'travel_expenses';
    
    /**
     * 管理者用：全交通費記録を取得（ページネーション対応）
     */
    public function findAllWithDetailsPaginated($year = null, $month = null, $status = null, $contractId = null, $doctorId = null, $limit = 20, $offset = 0, $sortColumn = 'service_date', $sortOrder = 'desc') {
        $sql = "SELECT te.*, 
                    sr.service_date, sr.start_time, sr.end_time, sr.service_type,
                    c.name as company_name, b.name as branch_name,
                    u.name as doctor_name,
                    co.id as contract_id,
                    co.taxi_allowed,
                    CONCAT(c.name, ' - ', b.name) as contract_display_name
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                JOIN contracts co ON sr.contract_id = co.id
                JOIN companies c ON co.company_id = c.id
                JOIN branches b ON co.branch_id = b.id
                JOIN users u ON te.doctor_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        // 年月フィルター
        if ($year && $month) {
            $sql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
            $params['year'] = $year;
            $params['month'] = $month;
        } elseif ($year) {
            $sql .= " AND YEAR(sr.service_date) = :year";
            $params['year'] = $year;
        }
        
        // ステータスフィルター
        if ($status) {
            $sql .= " AND te.status = :status";
            $params['status'] = $status;
        }
        
        // 契約フィルター
        if ($contractId) {
            $sql .= " AND co.id = :contract_id";
            $params['contract_id'] = $contractId;
        }
        
        // 産業医フィルター
        if ($doctorId) {
            $sql .= " AND te.doctor_id = :doctor_id";
            $params['doctor_id'] = $doctorId;
        }
        
        // ORDER BY句を動的に構築
        $allowedColumns = [
            'service_date' => 'sr.service_date',
            'amount' => 'te.amount',
            'status' => 'te.status',
            'transport_type' => 'te.transport_type',
            'company_name' => 'c.name'
        ];
        
        $orderColumn = $allowedColumns[$sortColumn] ?? 'sr.service_date';
        $orderDirection = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        // 企業名でソートする場合は、企業名の後に拠点名もソート
        if ($sortColumn === 'company_name') {
            $sql .= " ORDER BY {$orderColumn} {$orderDirection}, b.name {$orderDirection}, te.created_at DESC";
        } else {
            $sql .= " ORDER BY {$orderColumn} {$orderDirection}, te.created_at DESC";
        }
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // パラメータをバインド
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * 条件に合致する交通費記録の総件数を取得
     */
    public function countAllWithDetails($year = null, $month = null, $status = null, $contractId = null, $doctorId = null) {
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                JOIN contracts co ON sr.contract_id = co.id
                JOIN companies c ON co.company_id = c.id
                JOIN branches b ON co.branch_id = b.id
                JOIN users u ON te.doctor_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        // 年月フィルター
        if ($year && $month) {
            $sql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
            $params['year'] = $year;
            $params['month'] = $month;
        } elseif ($year) {
            $sql .= " AND YEAR(sr.service_date) = :year";
            $params['year'] = $year;
        }
        
        // ステータスフィルター
        if ($status) {
            $sql .= " AND te.status = :status";
            $params['status'] = $status;
        }
        
        // 契約フィルター
        if ($contractId) {
            $sql .= " AND co.id = :contract_id";
            $params['contract_id'] = $contractId;
        }
        
        // 産業医フィルター
        if ($doctorId) {
            $sql .= " AND te.doctor_id = :doctor_id";
            $params['doctor_id'] = $doctorId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return (int)$result['total'];
    }
    
    /**
     * 管理者用：全交通費記録を取得（従来版 - 後方互換性のため）
     */
    public function findAllWithDetails($year = null, $month = null, $status = null, $contractId = null, $doctorId = null) {
        return $this->findAllWithDetailsPaginated($year, $month, $status, $contractId, $doctorId, 10000, 0);
    }
    
    /**
     * 役務記録IDで交通費を取得
     */
    public function findByServiceRecord($serviceRecordId) {
        $sql = "SELECT te.*, 
                    sr.service_date, sr.start_time, sr.end_time,
                    c.name as company_name, b.name as branch_name,
                    u.name as doctor_name,
                    co.taxi_allowed
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                JOIN contracts co ON sr.contract_id = co.id
                JOIN companies c ON co.company_id = c.id
                JOIN branches b ON co.branch_id = b.id
                JOIN users u ON te.doctor_id = u.id
                WHERE te.service_record_id = :service_record_id
                ORDER BY te.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['service_record_id' => $serviceRecordId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 産業医の交通費記録を取得
     */
    public function findByDoctor($doctorId, $year = null, $month = null) {
        $sql = "SELECT te.*, 
                    sr.service_date, sr.start_time, sr.end_time, sr.service_type,
                    c.name as company_name, b.name as branch_name,
                    co.taxi_allowed
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                JOIN contracts co ON sr.contract_id = co.id
                JOIN companies c ON co.company_id = c.id
                JOIN branches b ON co.branch_id = b.id
                WHERE te.doctor_id = :doctor_id";
        
        $params = ['doctor_id' => $doctorId];
        
        if ($year && $month) {
            $sql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
            $params['year'] = $year;
            $params['month'] = $month;
        } elseif ($year) {
            $sql .= " AND YEAR(sr.service_date) = :year";
            $params['year'] = $year;
        }
        
        $sql .= " ORDER BY sr.service_date DESC, te.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 交通費が登録されている契約一覧を取得
     */
    public function getContractsWithExpenses($doctorId = null) {
        $sql = "SELECT DISTINCT 
                    co.id,
                    CONCAT(c.name, ' - ', b.name) as display_name,
                    c.name as company_name,
                    b.name as branch_name,
                    u.name as doctor_name
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                JOIN contracts co ON sr.contract_id = co.id
                JOIN companies c ON co.company_id = c.id
                JOIN branches b ON co.branch_id = b.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.contract_status = 'active'";

        $params = [];
        if ($doctorId) {
            $sql .= " AND u.id = :doctorId";
            $params['doctorId'] = $doctorId;
        }
    
        $sql .= " ORDER BY c.name ASC, b.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 交通費を申請している産業医一覧を取得
     */
    public function getDoctorsWithExpenses() {
        $sql = "SELECT DISTINCT 
                    u.id,
                    u.name,
                    u.email,
                    COUNT(te.id) as expense_count,
                    SUM(te.amount) as total_amount
                FROM {$this->table} te
                JOIN users u ON te.doctor_id = u.id
                WHERE u.user_type = 'doctor' 
                AND u.is_active = 1
                GROUP BY u.id, u.name, u.email
                ORDER BY u.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * フィルター条件に基づく統計情報を取得
     */
    public function getFilteredStats($year = null, $month = null, $status = null, $contractId = null, $doctorId = null) {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(te.amount) as total_amount,
                    COUNT(CASE WHEN te.status = 'approved' THEN 1 END) as approved_count,
                    SUM(CASE WHEN te.status = 'approved' THEN te.amount ELSE 0 END) as approved_amount,
                    COUNT(CASE WHEN te.status = 'pending' THEN 1 END) as pending_count,
                    SUM(CASE WHEN te.status = 'pending' THEN te.amount ELSE 0 END) as pending_amount,
                    COUNT(CASE WHEN te.status = 'rejected' THEN 1 END) as rejected_count,
                    SUM(CASE WHEN te.status = 'rejected' THEN te.amount ELSE 0 END) as rejected_amount,
                    -- 交通手段別統計
                    COUNT(CASE WHEN te.transport_type = 'train' THEN 1 END) as train_count,
                    COUNT(CASE WHEN te.transport_type = 'bus' THEN 1 END) as bus_count,
                    COUNT(CASE WHEN te.transport_type = 'taxi' THEN 1 END) as taxi_count,
                    COUNT(CASE WHEN te.transport_type = 'gasoline' THEN 1 END) as gasoline_count,
                    COUNT(CASE WHEN te.transport_type = 'highway_toll' THEN 1 END) as highway_toll_count,
                    COUNT(CASE WHEN te.transport_type = 'parking' THEN 1 END) as parking_count,
                    COUNT(CASE WHEN te.transport_type = 'rental_car' THEN 1 END) as rental_car_count,
                    COUNT(CASE WHEN te.transport_type = 'airplane' THEN 1 END) as airplane_count,
                    COUNT(CASE WHEN te.transport_type = 'other' THEN 1 END) as other_count
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                JOIN contracts co ON sr.contract_id = co.id
                WHERE 1=1";
        
        $params = [];
        
        if ($year && $month) {
            $sql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
            $params['year'] = $year;
            $params['month'] = $month;
        } elseif ($year) {
            $sql .= " AND YEAR(sr.service_date) = :year";
            $params['year'] = $year;
        }
        
        if ($status) {
            $sql .= " AND te.status = :status";
            $params['status'] = $status;
        }
        
        if ($contractId) {
            $sql .= " AND co.id = :contract_id";
            $params['contract_id'] = $contractId;
        }
        
        if ($doctorId) {
            $sql .= " AND te.doctor_id = :doctor_id";
            $params['doctor_id'] = $doctorId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
    }

    /**
     * 交通費記録の詳細取得
     */
    public function findByIdWithDetails($id) {
        $sql = "SELECT te.*, 
                    sr.service_date, sr.start_time, sr.end_time, sr.service_type, sr.service_hours,
                    sr.description as service_description,
                    c.name as company_name, b.name as branch_name,
                    co.regular_visit_hours,
                    co.taxi_allowed,
                    u.name as doctor_name, u.email as doctor_email
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                JOIN contracts co ON sr.contract_id = co.id
                JOIN companies c ON co.company_id = c.id
                JOIN branches b ON co.branch_id = b.id
                JOIN users u ON te.doctor_id = u.id
                WHERE te.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * 交通費記録の作成（ファイルアップロード対応）
     */
    public function createWithFile($data, $uploadedFile = null) {
        try {
            $this->db->beginTransaction();
            
            // ファイルアップロード処理
            if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleFileUpload($uploadedFile);
                if ($uploadResult['success']) {
                    $data['receipt_file_path'] = $uploadResult['file_path'];
                    $data['receipt_file_name'] = $uploadResult['file_name'];
                    $data['receipt_file_size'] = $uploadResult['file_size'];
                }
            }
            
            $id = $this->create($data);
            
            if (!$id) {
                throw new Exception('交通費記録の作成に失敗しました');
            }
            
            // 役務記録の履歴に交通費追加を記録
            if (isset($data['service_record_id']) && isset($data['doctor_id'])) {
                $this->recordTravelExpenseHistory(
                    $data['service_record_id'],
                    'travel_expense_added',
                    $data['doctor_id'],
                    '交通費を追加',
                    [
                        'travel_expense_id' => $id,
                        'transport_type' => $data['transport_type'] ?? null,
                        'trip_type'=> $data['trip_type'] ?? null,
                        'departure_point' => $data['departure_point'] ?? null,
                        'arrival_point' => $data['arrival_point'] ?? null,
                        'amount' => $data['amount'] ?? 0,
                        'memo' => $data['memo'] ?? null
                    ]
                );
            }
            
            $this->db->commit();
            return $id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Travel expense creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 交通費記録の更新（ファイルアップロード対応）
     */
    public function updateWithFile($id, $data, $uploadedFile = null) {
        try {
            $this->db->beginTransaction();
            
            // 更新前のデータを取得
            $oldRecord = $this->findById($id);
            if (!$oldRecord) {
                throw new Exception('交通費記録が見つかりません');
            }
            
            // 新しいファイルがアップロードされた場合
            if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                // 既存ファイルを削除
                $existingRecord = $this->findById($id);
                if ($existingRecord && !empty($existingRecord['receipt_file_path'])) {
                    $this->deleteFile($existingRecord['receipt_file_path']);
                }
                
                // 新しいファイルをアップロード
                $uploadResult = $this->handleFileUpload($uploadedFile);
                if ($uploadResult['success']) {
                    $data['receipt_file_path'] = $uploadResult['file_path'];
                    $data['receipt_file_name'] = $uploadResult['file_name'];
                    $data['receipt_file_size'] = $uploadResult['file_size'];
                }
            }
            
            $result = $this->update($id, $data);
            
            if (!$result) {
                throw new Exception('交通費記録の更新に失敗しました');
            }
            
            // 役務記録の履歴に交通費変更を記録
            if (isset($oldRecord['service_record_id']) && isset($oldRecord['doctor_id'])) {
                // 変更内容を検出
                $changes = [];
                foreach (['transport_type', 'departure_point', 'arrival_point', 'trip_type', 'amount', 'memo'] as $field) {
                    if (isset($data[$field]) && $data[$field] != $oldRecord[$field]) {
                        $changes[$field] = [
                            'from' => $oldRecord[$field],
                            'to' => $data[$field]
                        ];
                    }
                }
                
                $this->recordTravelExpenseHistory(
                    $oldRecord['service_record_id'],
                    'travel_expense_updated',
                    $oldRecord['doctor_id'],
                    '交通費を変更',
                    [
                        'travel_expense_id' => $id,
                        'changes' => $changes
                    ]
                );
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Travel expense update failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ファイルアップロード処理
     */
    private function handleFileUpload($uploadedFile) {
        $uploadDir = __DIR__ . '/../../uploads/travel_expenses/';
        
        // アップロードディレクトリを作成
        if (!createUploadDirectory($uploadDir)) {
            return ['success' => false, 'error' => 'アップロードディレクトリの作成に失敗しました'];
        }
        
        // ファイル検証
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        if ($uploadedFile['size'] > $maxFileSize) {
            return ['success' => false, 'error' => 'ファイルサイズが大きすぎます（最大5MB）'];
        }
        
        if (!validateFileExtension($uploadedFile['name'], $allowedExtensions)) {
            return ['success' => false, 'error' => '許可されていないファイル形式です'];
        }
        
        // セキュアなファイル名を生成
        $secureFileName = generateSecureFileName($uploadedFile['name'], 'receipt_');
        $filePath = $uploadDir . $secureFileName;
        
        // ファイルを移動
        if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'ファイルの保存に失敗しました'];
        }
        
        return [
            'success' => true,
            'file_path' => 'uploads/travel_expenses/' . $secureFileName,
            'file_name' => $uploadedFile['name'],
            'file_size' => $uploadedFile['size']
        ];
    }
    
    /**
     * ファイル削除
     */
    private function deleteFile($filePath) {
        $fullPath = __DIR__ . '/../../' . $filePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    
    /**
     * 交通費記録の削除（ファイルも削除）
     */
    public function deleteWithFile($id) {
        try {
            $record = $this->findById($id);
            if (!$record) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // 役務記録の履歴に交通費削除を記録
            if (isset($record['service_record_id']) && isset($record['doctor_id'])) {
                $this->recordTravelExpenseHistory(
                    $record['service_record_id'],
                    'travel_expense_deleted',
                    $record['doctor_id'],
                    '交通費を削除',
                    [
                        'travel_expense_id' => $id,
                        'deleted_expense' => [
                            'transport_type' => $record['transport_type'],
                            'trip_type' => $record['trip_type'],
                            'departure_point' => $record['departure_point'],
                            'arrival_point' => $record['arrival_point'],
                            'amount' => $record['amount'],
                            'memo' => $record['memo']
                        ]
                    ]
                );
            }
            
            // ファイルを削除
            if (!empty($record['receipt_file_path'])) {
                $this->deleteFile($record['receipt_file_path']);
            }
            
            // レコードを削除
            $result = $this->delete($id);
            
            if (!$result) {
                throw new Exception('交通費記録の削除に失敗しました');
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Travel expense deletion failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 承認処理
     */
    public function approve($id, $comment = null, $approvedBy = null) {
        $data = [
            'status' => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
            'admin_comment' => $comment
        ];
        
        if ($approvedBy) {
            $data['approved_by'] = $approvedBy;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * 差戻し処理
     */
    public function reject($id, $comment, $rejectedBy = null) {
        $data = [
            'status' => 'rejected',
            'rejected_at' => date('Y-m-d H:i:s'),
            'admin_comment' => $comment
        ];
        
        if ($rejectedBy) {
            $data['rejected_by'] = $rejectedBy;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * 月間交通費統計を取得
     */
    public function getMonthlyStats($doctorId, $year, $month) {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(te.amount) as total_amount,
                    COUNT(CASE WHEN te.status = 'approved' THEN 1 END) as approved_count,
                    SUM(CASE WHEN te.status = 'approved' THEN te.amount ELSE 0 END) as approved_amount,
                    COUNT(CASE WHEN te.status = 'pending' THEN 1 END) as pending_count,
                    SUM(CASE WHEN te.status = 'pending' THEN te.amount ELSE 0 END) as pending_amount,
                    COUNT(CASE WHEN te.status = 'rejected' THEN 1 END) as rejected_count,
                    SUM(CASE WHEN te.status = 'rejected' THEN te.amount ELSE 0 END) as rejected_amount
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                WHERE te.doctor_id = :doctor_id 
                AND YEAR(sr.service_date) = :year 
                AND MONTH(sr.service_date) = :month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'doctor_id' => $doctorId,
            'year' => $year,
            'month' => $month
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * 交通手段別統計を取得
     */
    public function getTransportTypeStats($doctorId, $year = null, $month = null) {
        $sql = "SELECT 
                    transport_type,
                    COUNT(*) as count,
                    SUM(te.amount) as total_amount,
                    AVG(te.amount) as avg_amount
                FROM {$this->table} te
                JOIN service_records sr ON te.service_record_id = sr.id
                WHERE te.doctor_id = :doctor_id";
        
        $params = ['doctor_id' => $doctorId];
        
        if ($year && $month) {
            $sql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
            $params['year'] = $year;
            $params['month'] = $month;
        } elseif ($year) {
            $sql .= " AND YEAR(sr.service_date) = :year";
            $params['year'] = $year;
        }
        
        $sql .= " GROUP BY transport_type ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 役務記録の履歴に交通費の操作を記録
     * 
     * @param int $serviceRecordId 役務記録ID
     * @param string $actionType アクション種別
     * @param int $actionBy 実行者ID
     * @param string $comment コメント
     * @param array $metadata メタデータ
     */
    private function recordTravelExpenseHistory($serviceRecordId, $actionType, $actionBy, $comment, $metadata = []) {
        try {
            require_once __DIR__ . '/ServiceRecordHistory.php';
            $historyModel = new ServiceRecordHistory();
            
            $historyModel->recordAction(
                $serviceRecordId,
                $actionType,
                $actionBy,
                null,  // status_from
                null,  // status_to
                $comment,
                $metadata
            );
        } catch (Exception $e) {
            error_log('Failed to record travel expense history: ' . $e->getMessage());
            // 履歴記録の失敗は処理を中断しない
        }
    }
}
?>