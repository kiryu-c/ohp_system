<?php
// app/models/TravelExpenseTemplate.php
require_once __DIR__ . '/BaseModel.php';

class TravelExpenseTemplate extends BaseModel {
    protected $table = 'travel_expense_templates';
    
    /**
     * 産業医の交通費テンプレート一覧を取得
     */
    public function findByDoctor($doctorId, $orderBy = 'usage_count DESC, last_used_at DESC') {
        $sql = "SELECT * FROM {$this->table} 
                WHERE doctor_id = :doctor_id 
                AND is_active = 1 
                ORDER BY {$orderBy}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 自動テンプレート名を生成
     */
    public function generateTemplateName($data) {
        $transportLabels = [
            'train' => '電車',
            'bus' => 'バス',
            'taxi' => 'タクシー',
            'gasoline' => 'ガソリン',
            'highway_toll' => '有料道路',
            'parking' => '駐車料金',
            'rental_car' => 'レンタカー',
            'airplane' => '航空機',
            'other' => 'その他'
        ];
        
        $tripTypeLabels = [
            'round_trip' => '往復',
            'one_way' => '片道'
        ];
        
        $transportLabel = $transportLabels[$data['transport_type']] ?? $data['transport_type'];
        $tripTypeLabel = $tripTypeLabels[$data['trip_type']] ?? $data['trip_type'];
        
        $returnName = $transportLabel;
        if ($tripTypeLabel) {
           $returnName .=  "（{$tripTypeLabel}）";
        }
        if ($data['departure_point']) {
           $returnName .=  ": {$data['departure_point']} → {$data['arrival_point']}";
        }
        return $returnName;
    }
    
    /**
     * 同じ組み合わせのテンプレートが存在するかチェック
     */
    public function findSimilarTemplate($doctorId, $data) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE doctor_id = :doctor_id 
                AND transport_type = :transport_type
                AND departure_point = :departure_point
                AND arrival_point = :arrival_point
                AND trip_type = :trip_type
                AND is_active = 1
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'doctor_id' => $doctorId,
            'transport_type' => $data['transport_type'],
            'departure_point' => $data['departure_point'],
            'arrival_point' => $data['arrival_point'],
            'trip_type' => $data['trip_type']
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * 交通費データからテンプレートを作成
     */
    public function createFromExpense($expenseData, $customName = null) {
        // 同じ組み合わせが存在するかチェック
        $existing = $this->findSimilarTemplate($expenseData['doctor_id'], $expenseData);
        if ($existing) {
            // 既存テンプレートの使用回数と金額を更新
            $this->updateUsage($existing['id'], $expenseData['amount']);
            return $existing['id'];
        }
        
        $templateData = [
            'doctor_id' => $expenseData['doctor_id'],
            'template_name' => $customName ?: $this->generateTemplateName($expenseData),
            'transport_type' => $expenseData['transport_type'],
            'departure_point' => $expenseData['departure_point'],
            'arrival_point' => $expenseData['arrival_point'],
            'trip_type' => $expenseData['trip_type'],
            'amount' => $expenseData['amount'],
            'memo' => $expenseData['memo'] ?? null,
            'usage_count' => 1,
            'last_used_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($templateData);
    }
    
    /**
     * テンプレート使用時の統計更新
     */
    public function updateUsage($templateId, $newAmount = null) {
        $updateData = [
            'usage_count' => 'usage_count + 1',
            'last_used_at' => date('Y-m-d H:i:s')
        ];
        
        // 金額が異なる場合は最新の金額に更新
        if ($newAmount !== null) {
            $updateData['amount'] = $newAmount;
        }
        
        // usage_countは式として扱うため、特別な処理が必要
        $sql = "UPDATE {$this->table} 
                SET usage_count = usage_count + 1,
                    last_used_at = :last_used_at";
        
        $params = ['last_used_at' => date('Y-m-d H:i:s')];
        
        if ($newAmount !== null) {
            $sql .= ", amount = :amount";
            $params['amount'] = $newAmount;
        }
        
        $sql .= " WHERE id = :id";
        $params['id'] = $templateId;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * テンプレートの削除（論理削除）
     */
    public function softDelete($id, $doctorId) {
        return $this->update($id, [
            'is_active' => 0
        ]);
    }
    
    /**
     * テンプレート名の更新
     */
    public function updateTemplateName($id, $doctorId, $newName) {
        // 権限チェック
        $template = $this->findById($id);
        if (!$template || $template['doctor_id'] != $doctorId) {
            return false;
        }
        
        return $this->update($id, [
            'template_name' => $newName
        ]);
    }
    
    /**
     * よく使うテンプレートを取得
     */
    public function getFrequentlyUsed($doctorId, $limit = 5) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE doctor_id = :doctor_id 
                AND is_active = 1 
                AND usage_count > 1
                ORDER BY usage_count DESC, last_used_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * 最近使ったテンプレートを取得
     */
    public function getRecentlyUsed($doctorId, $limit = 5) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE doctor_id = :doctor_id 
                AND is_active = 1 
                AND last_used_at IS NOT NULL
                ORDER BY last_used_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * 指定契約の役務で使用されたテンプレートを取得
     */
    public function getUsedInContract($doctorId, $contractId, $limit = 10) {
        $sql = "SELECT tet.*, COUNT(te.id) as usage_in_contract, MAX(te.created_at) as last_used_in_contract
                FROM {$this->table} tet
                INNER JOIN travel_expenses te ON (
                    tet.transport_type = te.transport_type
                    AND tet.departure_point = te.departure_point 
                    AND tet.arrival_point = te.arrival_point
                    AND tet.trip_type = te.trip_type
                )
                INNER JOIN service_records sr ON te.service_record_id = sr.id
                WHERE tet.doctor_id = :doctor_id
                AND tet.is_active = 1
                AND sr.contract_id = :contract_id
                GROUP BY tet.id
                ORDER BY usage_in_contract DESC, last_used_in_contract DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        $stmt->bindValue(':contract_id', $contractId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * 契約とサービス記録から推奨テンプレートを取得
     */
    public function getRecommendedForContract($doctorId, $contractId, $serviceRecordId, $limit = 5) {
        // まず現在のサービス記録の情報を取得
        $serviceCheckSql = "SELECT sr.*, c.name as company_name, b.name as branch_name 
                            FROM service_records sr
                            JOIN contracts co ON sr.contract_id = co.id
                            JOIN companies c ON co.company_id = c.id  
                            JOIN branches b ON co.branch_id = b.id
                            WHERE sr.id = :service_record_id AND sr.contract_id = :contract_id";
        
        $stmt = $this->db->prepare($serviceCheckSql);
        $stmt->execute([
            'service_record_id' => $serviceRecordId,
            'contract_id' => $contractId
        ]);
        $currentService = $stmt->fetch();
        
        if (!$currentService) {
            return [];
        }
        
        // この契約での過去の交通費実績からテンプレートを抽出
        $sql = "SELECT tet.*, 
                    COUNT(te.id) as usage_in_contract,
                    MAX(te.created_at) as last_used_in_contract,
                    AVG(te.amount) as avg_amount_in_contract
                FROM {$this->table} tet
                INNER JOIN travel_expenses te ON (
                    tet.transport_type = te.transport_type
                    AND tet.departure_point = te.departure_point 
                    AND tet.arrival_point = te.arrival_point
                    AND tet.trip_type = te.trip_type
                )
                INNER JOIN service_records sr ON te.service_record_id = sr.id
                WHERE tet.doctor_id = :doctor_id
                AND tet.is_active = 1
                AND sr.contract_id = :contract_id
                AND sr.id != :service_record_id  -- 現在のサービス記録は除外
                GROUP BY tet.id
                ORDER BY usage_in_contract DESC, last_used_in_contract DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        $stmt->bindValue(':contract_id', $contractId, PDO::PARAM_INT);
        $stmt->bindValue(':service_record_id', $serviceRecordId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // 到着地点に会社名・支店名が含まれているものを優先
        if ($results) {
            $companyName = $currentService['company_name'];
            $branchName = $currentService['branch_name'];
            
            usort($results, function($a, $b) use ($companyName, $branchName) {
                $aScore = 0;
                $bScore = 0;
                
                // 会社名が含まれている場合は+2点
                if (strpos($a['arrival_point'], $companyName) !== false) $aScore += 2;
                if (strpos($b['arrival_point'], $companyName) !== false) $bScore += 2;
                
                // 支店名が含まれている場合は+1点
                if (strpos($a['arrival_point'], $branchName) !== false) $aScore += 1;
                if (strpos($b['arrival_point'], $branchName) !== false) $bScore += 1;
                
                if ($aScore != $bScore) {
                    return $bScore - $aScore; // スコアの高い順
                }
                
                // スコアが同じ場合は使用回数順
                return $b['usage_in_contract'] - $a['usage_in_contract'];
            });
        }
        
        return $results;
    }
}