<?php
// app/models/Contract.php - タクシー可否対応版
require_once __DIR__ . '/BaseModel.php';

class Contract extends BaseModel {
    protected $table = 'contracts';
    
    public function findActive() {
        return $this->findAll(['contract_status' => 'active']);
    }
    
    public function findByDoctor($doctorId) {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name,
                CONCAT(c.name, ' - ', b.name) as company_branch_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                WHERE co.doctor_id = :doctor_id AND co.contract_status = 'active'
                ORDER BY c.name, b.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報、締結方法情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
        }
        unset($contract);
        
        return $contracts;
    }
    
    public function findByCompany($companyId) {
        $sql = "SELECT co.*, u.name as doctor_name, b.name as branch_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.company_id = :company_id AND co.contract_status = 'active'
                ORDER BY b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報、請求方法情報、料金情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
            
            // 料金設定のラベルを追加
            $contract['regular_visit_rate_display'] = $this->formatRate($contract['regular_visit_rate'], '円/時間');
            $contract['regular_extension_rate_display'] = $this->formatRate($contract['regular_extension_rate'], '円/15分');
            $contract['emergency_visit_rate_display'] = $this->formatRate($contract['emergency_visit_rate'], '円/15分');
            $contract['document_consultation_rate_display'] = $this->formatRate($contract['document_consultation_rate'], '円/回');
        }
        unset($contract);
        
        return $contracts;
    }
    
    public function findByBranch($branchId) {
        $sql = "SELECT co.*, u.name as doctor_name
                FROM {$this->table} co
                JOIN users u ON co.doctor_id = u.id
                WHERE co.branch_id = :branch_id AND co.contract_status = 'active'
                ORDER BY u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['branch_id' => $branchId]);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報、締結方法情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
        }
        unset($contract);
        
        return $contracts;
    }
    
    public function findWithDetails($contractId) {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name, b.address as branch_address
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.id = :contract_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        
        $contract = $stmt->fetch();
        
        if ($contract) {
            // 訪問頻度情報とタクシー可否情報、請求方法情報を追加
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
            
            // 料金設定のラベルを追加
            $contract['regular_visit_rate_display'] = $this->formatRate($contract['regular_visit_rate'], '円/時間');
            $contract['regular_extension_rate_display'] = $this->formatRate($contract['regular_extension_rate'], '円/15分');
            $contract['emergency_visit_rate_display'] = $this->formatRate($contract['emergency_visit_rate'], '円/15分');
            $contract['document_consultation_rate_display'] = $this->formatRate($contract['document_consultation_rate'], '円/回');
        }
        
        return $contract;
    }

    /**
     * 料金の表示形式を整形
     */
    private function formatRate($rate, $unit = '') {
        if ($rate === null || $rate === '' || $rate <= 0) {
            return '未設定';
        }
        return number_format($rate) . $unit;
    }
    
    /**
     * 契約の料金設定を取得
     */
    public function getRates($contractId) {
        $sql = "SELECT regular_visit_rate, emergency_visit_rate, document_consultation_rate 
                FROM {$this->table} 
                WHERE id = :contract_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
        
        $rates = $stmt->fetch();
        
        if ($rates) {
            return [
                'regular_visit_rate' => (int)$rates['regular_visit_rate'],
                'regular_extension_rate' => (int)$rates['regular_extension_rate'],
                'emergency_visit_rate' => (int)$rates['emergency_visit_rate'],
                'document_consultation_rate' => (int)$rates['document_consultation_rate'],
                'regular_visit_rate_display' => $this->formatRate($rates['regular_visit_rate'], '円/時間'),
                'regular_extension_rate_display' => $this->formatRate($rates['regular_extension_rate'], '円/15分'),
                'emergency_visit_rate_display' => $this->formatRate($rates['emergency_visit_rate'], '円/15分'),
                'document_consultation_rate_display' => $this->formatRate($rates['document_consultation_rate'], '円/回')
            ];
        }
        
        return [
            'regular_visit_rate' => 0,
            'regular_extension_rate' => 0,
            'emergency_visit_rate' => 0,
            'document_consultation_rate' => 0,
            'regular_visit_rate_display' => '未設定',
            'regular_extension_rate_display' => '未設定',
            'emergency_visit_rate_display' => '未設定',
            'document_consultation_rate_display' => '未設定'
        ];
    }

    /**
     * 契約の月間推定料金を計算
     */
    public function calculateMonthlyEstimatedCost($contractId, $year = null, $month = null) {
        if ($year === null) $year = date('Y');
        if ($month === null) $month = date('n');
        
        $contract = $this->findById($contractId);
        if (!$contract) {
            return null;
        }
        
        $rates = $this->getRates($contractId);
        
        // 月間使用量を取得
        $monthlyUsage = $this->getMonthlyUsage($contractId, $year, $month);
        
        if (!$monthlyUsage) {
            return null;
        }
        
        // 各サービス種別の推定費用を計算
        $estimatedCosts = [
            'regular_cost' => ($monthlyUsage['regular_hours'] ?? 0) * $rates['regular_visit_rate'],
            'extension_cost' => ($monthlyUsage['extension_hours'] ?? 0) * 4 * $rates['regular_extension_rate'], // 延長は15分単位
            'emergency_cost' => ($monthlyUsage['emergency_hours'] ?? 0) * 4 * $rates['emergency_visit_rate'], // 1時間 = 4 × 15分
            'extension_cost' => ($monthlyUsage['extension_hours'] ?? 0) * $rates['regular_visit_rate'], // 延長も定期料金と同額
            'document_cost' => ($monthlyUsage['document_count'] ?? 0) * $rates['document_consultation_rate'],
            'consultation_cost' => ($monthlyUsage['remote_consultation_count'] ?? 0) * $rates['document_consultation_rate']
        ];
        
        $estimatedCosts['total_cost'] = array_sum($estimatedCosts);
        
        // 表示用の整形
        foreach ($estimatedCosts as $key => $cost) {
            $estimatedCosts[$key . '_display'] = number_format($cost) . '円';
        }
        
        return array_merge($monthlyUsage, $estimatedCosts, [
            'rates' => $rates,
            'contract_info' => $contract
        ]);
    }

    /**
     * 料金設定別の契約統計を取得
     */
    public function getRateStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_contracts,
                    COUNT(CASE WHEN regular_visit_rate IS NOT NULL AND regular_visit_rate > 0 THEN 1 END) as contracts_with_regular_rate,
                    COUNT(CASE WHEN emergency_visit_rate IS NOT NULL AND emergency_visit_rate > 0 THEN 1 END) as contracts_with_emergency_rate,
                    COUNT(CASE WHEN document_consultation_rate IS NOT NULL AND document_consultation_rate > 0 THEN 1 END) as contracts_with_document_rate,
                    AVG(CASE WHEN regular_visit_rate > 0 THEN regular_visit_rate END) as avg_regular_rate,
                    AVG(CASE WHEN emergency_visit_rate > 0 THEN emergency_visit_rate END) as avg_emergency_rate,
                    AVG(CASE WHEN document_consultation_rate > 0 THEN document_consultation_rate END) as avg_document_rate,
                    MIN(CASE WHEN regular_visit_rate > 0 THEN regular_visit_rate END) as min_regular_rate,
                    MAX(CASE WHEN regular_visit_rate > 0 THEN regular_visit_rate END) as max_regular_rate,
                    MIN(CASE WHEN regular_extension_rate > 0 THEN regular_extension_rate END) as min_extension_rate,
                    MAX(CASE WHEN regular_extension_rate > 0 THEN regular_extension_rate END) as max_extension_rate,
                    MIN(CASE WHEN emergency_visit_rate > 0 THEN emergency_visit_rate END) as min_emergency_rate,
                    MAX(CASE WHEN emergency_visit_rate > 0 THEN emergency_visit_rate END) as max_emergency_rate,
                    MIN(CASE WHEN document_consultation_rate > 0 THEN document_consultation_rate END) as min_document_rate,
                    MAX(CASE WHEN document_consultation_rate > 0 THEN document_consultation_rate END) as max_document_rate
                FROM {$this->table}
                WHERE contract_status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetch();
        
        if ($stats) {
            // 統計情報に表示用の整形を追加
            foreach (['avg', 'min', 'max'] as $type) {
                foreach (['regular', 'emergency', 'document'] as $service) {
                    $key = $type . '_' . $service . '_rate';
                    if ($stats[$key] !== null) {
                        $unit = $service === 'regular' ? '円/時間' : 
                               ($service === 'emergency' ? '円/15分' : '円/回');
                        $stats[$key . '_display'] = $this->formatRate($stats[$key], $unit);
                    } else {
                        $stats[$key . '_display'] = '-';
                    }
                }
            }
            
            // 設定率の計算
            if ($stats['total_contracts'] > 0) {
                $stats['regular_rate_percentage'] = round(($stats['contracts_with_regular_rate'] / $stats['total_contracts']) * 100, 1);
                $stats['extension_rate_percentage'] = round(($stats['contracts_with_extension_rate'] / $stats['total_contracts']) * 100, 1);
                $stats['emergency_rate_percentage'] = round(($stats['contracts_with_emergency_rate'] / $stats['total_contracts']) * 100, 1);
                $stats['document_rate_percentage'] = round(($stats['contracts_with_document_rate'] / $stats['total_contracts']) * 100, 1);
            }
        }
        
        return $stats;
    }
    
    /**
     * 料金範囲で契約を検索
     */
    public function findByRateRange($serviceType, $minRate = null, $maxRate = null) {
        $rateColumn = match($serviceType) {
            'regular' => 'regular_visit_rate',
            'extension' => 'regular_extension_rate',
            'emergency' => 'emergency_visit_rate',
            'document' => 'document_consultation_rate',
            default => 'regular_visit_rate'
        };
        
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name, {$rateColumn} as rate
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.contract_status = 'active'
                AND {$rateColumn} IS NOT NULL
                AND {$rateColumn} > 0";
        
        $params = [];
        
        if ($minRate !== null) {
            $sql .= " AND {$rateColumn} >= :min_rate";
            $params['min_rate'] = $minRate;
        }
        
        if ($maxRate !== null) {
            $sql .= " AND {$rateColumn} <= :max_rate";
            $params['max_rate'] = $maxRate;
        }
        
        $sql .= " ORDER BY {$rateColumn} ASC, c.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に詳細情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
            
            // 料金設定のラベルを追加
            $contract['rate_display'] = $this->formatRate($contract['rate'], 
                $serviceType === 'regular' ? '円/時間' : 
                ($serviceType === 'emergency' || $serviceType === 'extension' ? '円/15分' : '円/回'));
        }
        unset($contract);
        
        return $contracts;
    }
    
    /**
     * 料金未設定の契約を取得
     */
    public function findContractsWithoutRates() {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.contract_status = 'active'
                AND (co.regular_visit_rate IS NULL OR co.regular_visit_rate = 0)
                AND (co.regular_extension_rate IS NULL OR co.regular_extension_rate = 0)
                AND (co.emergency_visit_rate IS NULL OR co.emergency_visit_rate = 0)
                AND (co.document_consultation_rate IS NULL OR co.document_consultation_rate = 0)
                ORDER BY c.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に詳細情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
        }
        unset($contract);
        
        return $contracts;
    }

    /**
     * 定期延長料金の統計情報を取得
     */
    public function getExtensionRateStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_contracts,
                    COUNT(CASE WHEN regular_extension_rate IS NOT NULL AND regular_extension_rate > 0 THEN 1 END) as contracts_with_extension_rate,
                    AVG(CASE WHEN regular_extension_rate > 0 THEN regular_extension_rate END) as avg_extension_rate,
                    MIN(CASE WHEN regular_extension_rate > 0 THEN regular_extension_rate END) as min_extension_rate,
                    MAX(CASE WHEN regular_extension_rate > 0 THEN regular_extension_rate END) as max_extension_rate
                FROM {$this->table}
                WHERE contract_status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetch();
        
        if ($stats && $stats['total_contracts'] > 0) {
            $stats['extension_rate_percentage'] = round(($stats['contracts_with_extension_rate'] / $stats['total_contracts']) * 100, 1);
            
            foreach (['avg', 'min', 'max'] as $type) {
                $key = $type . '_extension_rate';
                $stats[$key . '_display'] = $stats[$key] !== null ? $this->formatRate($stats[$key], '円/15分') : '-';
            }
        }
        
        return $stats;
    }

    /**
     * 定期延長料金設定済みの契約を取得
     */
    public function findContractsWithExtensionRate() {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.contract_status = 'active'
                AND co.regular_extension_rate IS NOT NULL
                AND co.regular_extension_rate > 0
                ORDER BY co.regular_extension_rate DESC, c.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に詳細情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
            $contract['regular_extension_rate_display'] = $this->formatRate($contract['regular_extension_rate'], '円/15分');
        }
        unset($contract);
        
        return $contracts;
    }

    
    /**
     * 料金設定の一括更新（マイグレーション用）
     */
    public function migrateRateSettings() {
        // 料金設定のデフォルト値を設定（必要に応じて）
        // この例では何もしないが、必要に応じてデフォルト値を設定できる
        $sql = "UPDATE {$this->table} 
                SET updated_at = NOW()
                WHERE regular_visit_rate IS NULL 
                AND emergency_visit_rate IS NULL 
                AND document_consultation_rate IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        
        return $stmt->rowCount();
    }

    /**
     * 税種別のラベルを取得
     */
    public function getTaxTypeLabel($taxType) {
        switch ($taxType) {
            case 'exclusive':
                return '外税';
            case 'inclusive':
                return '内税';
            default:
                return '外税'; // デフォルト
        }
    }

    /**
     * 税種別別の契約統計を取得
     */
    public function getTaxTypeStatistics() {
        $sql = "SELECT 
                    tax_type,
                    COUNT(*) as contract_count,
                    SUM(CASE WHEN contract_status = 'active' THEN 1 ELSE 0 END) as active_count,
                    AVG(regular_visit_hours) as avg_hours,
                    SUM(regular_visit_hours) as total_hours
                FROM {$this->table}
                GROUP BY tax_type
                ORDER BY tax_type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetchAll();
        
        foreach ($stats as &$stat) {
            $stat['tax_type_label'] = $this->getTaxTypeLabel($stat['tax_type']);
        }
        unset($stat);
        
        return $stats;
    }
    
    /**
     * 税種別別の契約を検索
     */
    public function findByTaxType($taxType) {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.tax_type = :tax_type AND co.contract_status = 'active'
                ORDER BY c.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tax_type' => $taxType]);
        
        $contracts = $stmt->fetchAll();
        
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
        }
        unset($contract);
        
        return $contracts;
    }

    /**
     * 訪問頻度別の契約一覧を取得
     */
    public function findByFrequency($frequency) {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.visit_frequency = :frequency AND co.contract_status = 'active'
                ORDER BY c.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['frequency' => $frequency]);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報、締結方法情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
        }
        unset($contract);
        
        return $contracts;
    }
    
    /**
     * タクシー利用可否での契約検索
     */
    public function findByTaxiAllowed($taxiAllowed = true) {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.taxi_allowed = :taxi_allowed AND co.contract_status = 'active'
                ORDER BY c.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['taxi_allowed' => $taxiAllowed ? 1 : 0]);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報、締結方法情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
        }
        unset($contract);
        
        return $contracts;
    }
    
    /**
     * タクシー利用可否の統計情報を取得
     */
    public function getTaxiAllowedStatistics() {
        $sql = "SELECT 
                    taxi_allowed,
                    COUNT(*) as contract_count,
                    SUM(CASE WHEN contract_status = 'active' THEN 1 ELSE 0 END) as active_count,
                    AVG(regular_visit_hours) as avg_hours
                FROM {$this->table}
                GROUP BY taxi_allowed
                ORDER BY taxi_allowed";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetchAll();
        
        // 統計情報に詳細を追加
        foreach ($stats as &$stat) {
            $stat['taxi_allowed_label'] = $stat['taxi_allowed'] ? 'タクシー利用可' : 'タクシー利用不可';
        }
        unset($stat);
        
        return $stats;
    }
    
    /**
     * 特定の契約のタクシー利用可否を取得
     */
    public function getTaxiAllowed($contractId) {
        $contract = $this->findById($contractId);
        return $contract ? (bool)$contract['taxi_allowed'] : false;
    }
    
    /**
     * 特定の産業医のタクシー利用可能な契約を取得
     */
    public function findTaxiAllowedContractsByDoctor($doctorId) {
        return $this->findByDoctorWithTaxiFilter($doctorId, true);
    }
    
    /**
     * 産業医の契約をタクシー可否でフィルタリング
     */
    public function findByDoctorWithTaxiFilter($doctorId, $taxiAllowed = null) {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name,
                CONCAT(c.name, ' - ', b.name) as company_branch_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                WHERE co.doctor_id = :doctor_id AND co.contract_status = 'active'";
        
        $params = ['doctor_id' => $doctorId];
        
        if ($taxiAllowed !== null) {
            $sql .= " AND co.taxi_allowed = :taxi_allowed";
            $params['taxi_allowed'] = $taxiAllowed ? 1 : 0;
        }
        
        $sql .= " ORDER BY c.name, b.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報、締結方法情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
        }
        unset($contract);
        
        return $contracts;
    }
    
    /**
     * 今月が訪問月の契約を取得（隔月契約対応）
     */
    public function findVisitMonthContracts($year = null, $month = null) {
        if ($year === null) $year = date('Y');
        if ($month === null) $month = date('n');
        
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.contract_status = 'active'
                ORDER BY c.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $allContracts = $stmt->fetchAll();
        $visitMonthContracts = [];
        
        foreach ($allContracts as $contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, $year, $month);
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
            
            if ($contract['is_visit_month']) {
                $visitMonthContracts[] = $contract;
            }
        }
        
        return $visitMonthContracts;
    }
    
    /**
     * 訪問頻度とタクシー可否の統計を取得
     */
    public function getFrequencyAndTaxiStatistics() {
        $sql = "SELECT 
                    visit_frequency,
                    taxi_allowed,
                    tax_type,
                    COUNT(*) as contract_count,
                    SUM(CASE WHEN contract_status = 'active' THEN 1 ELSE 0 END) as active_count,
                    AVG(regular_visit_hours) as avg_hours,
                    SUM(regular_visit_hours) as total_hours
                FROM {$this->table}
                GROUP BY visit_frequency, taxi_allowed, tax_type
                ORDER BY visit_frequency, taxi_allowed, tax_type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetchAll();
        
        // 統計情報に詳細を追加
        foreach ($stats as &$stat) {
            $stat['frequency_info'] = get_visit_frequency_info($stat['visit_frequency']);
            $stat['taxi_allowed_label'] = $stat['taxi_allowed'] ? 'タクシー利用可' : 'タクシー利用不可';
            $stat['tax_type_label'] = $this->getTaxTypeLabel($stat['tax_type']);
        }
        unset($stat);
        
        return $stats;
    }
    
    /**
     * 訪問頻度の統計を取得
     */
    public function getFrequencyStatistics() {
        $sql = "SELECT 
                    visit_frequency,
                    COUNT(*) as contract_count,
                    SUM(CASE WHEN contract_status = 'active' THEN 1 ELSE 0 END) as active_count,
                    AVG(regular_visit_hours) as avg_hours,
                    SUM(regular_visit_hours) as total_hours
                FROM {$this->table}
                GROUP BY visit_frequency
                ORDER BY visit_frequency";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetchAll();
        
        // 統計情報に詳細を追加
        foreach ($stats as &$stat) {
            $stat['frequency_info'] = get_visit_frequency_info($stat['visit_frequency']);
            $stat['percentage'] = 0; // 後で全体に対する割合を計算
        }
        unset($stat);
        
        // 全体に対する割合を計算
        $totalContracts = array_sum(array_column($stats, 'contract_count'));
        if ($totalContracts > 0) {
            foreach ($stats as &$stat) {
                $stat['percentage'] = ($stat['contract_count'] / $totalContracts) * 100;
            }
            unset($stat);
        }
        
        return $stats;
    }
    
    /**
     * 契約の月間使用量を取得（訪問頻度考慮）
     */
    public function getMonthlyUsage($contractId, $year, $month) {
        // 契約情報を取得
        $contract = $this->findById($contractId);
        if (!$contract) {
            return null;
        }
        
        // 訪問頻度に応じた月間限度時間を計算
        $monthlyUsage = calculate_monthly_usage_by_frequency(
            $contract['visit_frequency'] ?? 'monthly',
            $contract['regular_visit_hours'],
            $year,
            $month,
            $contract
        );
        
        // 実際の使用量を取得
        $sql = "SELECT 
                    SUM(CASE WHEN service_type = 'regular' THEN service_hours ELSE 0 END) as regular_hours,
                    SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                    SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                    COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                    COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                    SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as total_hours
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
        
        $usage = $stmt->fetch() ?: [
            'regular_hours' => 0,
            'emergency_hours' => 0,
            'extension_hours' => 0,
            'document_count' => 0,
            'remote_consultation_count' => 0,
            'total_hours' => 0
        ];
        
        // 結果をマージ
        return array_merge($monthlyUsage, $usage, [
            'contract_info' => $contract,
            'remaining_hours' => max(0, $monthlyUsage['monthly_limit'] - $usage['regular_hours']),
            'usage_percentage' => $monthlyUsage['monthly_limit'] > 0 ? 
                ($usage['regular_hours'] / $monthlyUsage['monthly_limit']) * 100 : 0,
            'is_over_limit' => $usage['regular_hours'] > $monthlyUsage['monthly_limit']
        ]);
    }
    
    /**
     * 隔月契約の次回訪問予定月を取得
     */
    public function getNextVisitMonth($contractId) {
        $contract = $this->findById($contractId);
        if (!$contract || $contract['visit_frequency'] !== 'bimonthly') {
            return null;
        }
        
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // 現在月から12ヶ月先まで検索
        for ($i = 0; $i < 12; $i++) {
            $checkMonth = $currentMonth + $i;
            $checkYear = $currentYear;
            
            // 月が12を超えた場合の調整
            while ($checkMonth > 12) {
                $checkMonth -= 12;
                $checkYear++;
            }
            
            if (is_visit_month($contract, $checkYear, $checkMonth)) {
                return [
                    'year' => $checkYear,
                    'month' => $checkMonth,
                    'date_string' => $checkYear . '年' . $checkMonth . '月',
                    'months_ahead' => $i
                ];
            }
        }
        
        return null;
    }
    
    /**
     * 契約期間中の訪問回数を計算
     */
    public function calculateTotalVisitCount($contractId) {
        $contract = $this->findById($contractId);
        if (!$contract) {
            return 0;
        }
        
        $startDate = new DateTime($contract['start_date']);
        $endDate = $contract['end_date'] ? new DateTime($contract['end_date']) : new DateTime('+1 year');
        
        $interval = $startDate->diff($endDate);
        $totalMonths = ($interval->y * 12) + $interval->m;
        
        switch ($contract['visit_frequency']) {
            case 'monthly':
                return $totalMonths;
            case 'bimonthly':
                return ceil($totalMonths / 2);
            default:
                return $totalMonths;
        }
    }
    
    public function isValidContract($contractId, $doctorId) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE id = :contract_id AND doctor_id = :doctor_id AND contract_status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'doctor_id' => $doctorId
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    public function checkDuplicateContract($doctorId, $branchId) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE doctor_id = :doctor_id AND branch_id = :branch_id 
                AND contract_status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'doctor_id' => $doctorId,
            'branch_id' => $branchId
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    public function checkDuplicateContractForUpdate($contractId, $doctorId, $branchId) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE doctor_id = :doctor_id AND branch_id = :branch_id 
                AND contract_status = 'active' AND id != :contract_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'doctor_id' => $doctorId,
            'branch_id' => $branchId,
            'contract_id' => $contractId
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * ファイル情報を含めて契約を作成
     */
    public function createWithFile($data, $fileInfo = null) {
        try {
            $this->db->beginTransaction();
            
            // デフォルト値を設定
            if (!isset($data['visit_frequency'])) {
                $data['visit_frequency'] = 'monthly';
            }
            
            // タクシー可否のデフォルト値を設定
            if (!isset($data['taxi_allowed'])) {
                $data['taxi_allowed'] = 0;
            }
            
            // 祝日訪問のデフォルト値を設定
            if (!isset($data['exclude_holidays'])) {
                $data['exclude_holidays'] = 1; // デフォルトは祝日非訪問
            }
            
            // 税種別のデフォルト値を設定
            if (!isset($data['tax_type'])) {
                $data['tax_type'] = 'exclusive';
            }
            
            // バージョン番号を設定（新規作成時は常に1）
            $data['version_number'] = 1;
            
            // 反映日を設定（契約開始日と同じ）
            $data['effective_date'] = $data['start_date'];
            
            // 反映終了日はNULL（最新版）
            $data['effective_end_date'] = null;
            
            // parent_contract_idはNULL（初版）
            $data['parent_contract_id'] = null;
            
            // ファイル情報を追加
            if ($fileInfo) {
                $data['contract_file_path'] = $fileInfo['file_path'];
                $data['contract_file_name'] = $fileInfo['file_name'];
                $data['contract_file_size'] = $fileInfo['file_size'];
            }
            
            $contractId = $this->create($data);
            
            $this->db->commit();
            return $contractId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * ファイル情報を含めて契約を更新
     */
    public function updateWithFile($id, $data, $fileInfo = null, $removeFile = false) {
        try {
            $this->db->beginTransaction();
            
            // 現在の契約情報を取得
            $currentContract = $this->findById($id);
            if (!$currentContract) {
                throw new Exception('契約が見つかりません');
            }
            
            // デフォルト値を設定
            if (!isset($data['visit_frequency'])) {
                $data['visit_frequency'] = $currentContract['visit_frequency'] ?? 'monthly';
            }
            
            // タクシー可否のデフォルト値を設定
            if (!isset($data['taxi_allowed'])) {
                $data['taxi_allowed'] = $currentContract['taxi_allowed'] ?? 0;
            }
            
            // 祝日除外のデフォルト値を設定
            if (!isset($data['exclude_holidays'])) {
                $data['exclude_holidays'] = $currentContract['exclude_holidays'] ?? 1;
            }
            
            // 税種別のデフォルト値を設定
            if (!isset($data['tax_type'])) {
                $data['tax_type'] = $currentContract['tax_type'] ?? 'exclusive';
            }
            
            // ファイル削除の場合
            if ($removeFile) {
                // 物理ファイルを削除
                if (!empty($currentContract['contract_file_path'])) {
                    $this->deletePhysicalFile($currentContract['contract_file_path']);
                }
                $data['contract_file_path'] = null;
                $data['contract_file_name'] = null;
                $data['contract_file_size'] = null;
            }
            // 新しいファイルがアップロードされた場合
            elseif ($fileInfo) {
                // 既存ファイルを削除
                if (!empty($currentContract['contract_file_path'])) {
                    $this->deletePhysicalFile($currentContract['contract_file_path']);
                }
                $data['contract_file_path'] = $fileInfo['file_path'];
                $data['contract_file_name'] = $fileInfo['file_name'];
                $data['contract_file_size'] = $fileInfo['file_size'];
            }
            
            $result = $this->update($id, $data);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 物理ファイルを削除
     */
    private function deletePhysicalFile($filePath) {
        // 相対パスを絶対パスに変換
        // $filePath は /uploads/contracts/... の形式
        if (strpos($filePath, '/uploads/') === 0) {
            // public外のuploads/を参照
            $absolutePath = __DIR__ . '/../..' . $filePath;
        } elseif (strpos($filePath, ':') !== false || strpos($filePath, '\\') === 0) {
            // 既に絶対パス（Windows形式またはUnix形式）
            $absolutePath = $filePath;
        } else {
            // その他の形式
            $absolutePath = $filePath;
        }
        
        // パスを正規化
        $absolutePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absolutePath);
        
        // ファイルが存在すれば削除
        if (file_exists($absolutePath) && is_file($absolutePath)) {
            if (unlink($absolutePath)) {
                error_log("Contract file deleted: {$absolutePath}");
                return true;
            } else {
                error_log("Failed to delete contract file: {$absolutePath}");
                return false;
            }
        } else {
            error_log("Contract file not found for deletion: {$absolutePath}");
            return false;
        }
    }
    
    /**
     * ファイルのダウンロードパスを取得
     */
    public function getFileDownloadPath($contractId) {
        $contract = $this->findById($contractId);
        if ($contract && !empty($contract['contract_file_path'])) {
            return $contract['contract_file_path'];
        }
        return null;
    }
    
    /**
     * 契約削除時にファイルも削除
     */
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // 契約情報を取得
            $contract = $this->findById($id);
            if ($contract && !empty($contract['contract_file_path'])) {
                $this->deletePhysicalFile($contract['contract_file_path']);
            }
            
            // 週間スケジュールを削除（外部キー制約で自動削除されるが明示的に実行）
            $this->deleteWeeklySchedule($id);

            // 契約データを削除
            $result = parent::delete($id);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * ファイル付きの契約一覧を取得（管理者用）
     */
    public function findAllWithFiles() {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                ORDER BY co.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
        }
        unset($contract);
        
        return $contracts;
    }
    
    /**
     * ファイルが存在する契約のみを取得
     */
    public function findContractsWithFiles() {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.contract_file_path IS NOT NULL 
                AND co.contract_file_path != ''
                ORDER BY co.updated_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
        }
        unset($contract);
        
        return $contracts;
    }
    
    /**
     * ファイルサイズの合計を取得
     */
    public function getTotalFileSize() {
        $sql = "SELECT SUM(contract_file_size) as total_size 
                FROM {$this->table} 
                WHERE contract_file_size IS NOT NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total_size'] ?? 0;
    }
    
    /**
     * 孤立ファイルをチェック（DBにあるがファイルが存在しない）
     */
    public function findOrphanedFiles() {
        $sql = "SELECT id, contract_file_path, contract_file_name 
                FROM {$this->table} 
                WHERE contract_file_path IS NOT NULL 
                AND contract_file_path != ''";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $contracts = $stmt->fetchAll();
        
        $orphaned = [];
        foreach ($contracts as $contract) {
            if (!file_exists($contract['contract_file_path'])) {
                $orphaned[] = $contract;
            }
        }
        
        return $orphaned;
    }
    
    /**
     * 孤立ファイルのDBレコードをクリーンアップ
     */
    public function cleanupOrphanedFileRecords() {
        $orphaned = $this->findOrphanedFiles();
        $cleanedCount = 0;
        
        try {
            $this->db->beginTransaction();
            
            foreach ($orphaned as $contract) {
                $sql = "UPDATE {$this->table} 
                        SET contract_file_path = NULL, 
                            contract_file_name = NULL, 
                            contract_file_size = NULL 
                        WHERE id = :id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['id' => $contract['id']]);
                $cleanedCount++;
            }
            
            $this->db->commit();
            return $cleanedCount;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 契約満了予定の契約を取得
     */
    public function findExpiringContracts($daysAhead = 30) {
        $targetDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.contract_status = 'active'
                AND co.end_date IS NOT NULL
                AND co.end_date <= :target_date
                ORDER BY co.end_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['target_date' => $targetDate]);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に訪問頻度情報とタクシー可否情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['days_until_expiry'] = (new DateTime($contract['end_date']))->diff(new DateTime())->days;
        }
        unset($contract);
        
        return $contracts;
    }
    
    /**
     * 訪問頻度の一括更新（マイグレーション用）
     */
    public function migrateVisitFrequency() {
        $sql = "UPDATE {$this->table} 
                SET visit_frequency = 'monthly' 
                WHERE visit_frequency IS NULL OR visit_frequency = ''";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * タクシー可否の一括更新（マイグレーション用）
     */
    public function migrateTaxiAllowed() {
        $sql = "UPDATE {$this->table} 
                SET taxi_allowed = 0 
                WHERE taxi_allowed IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        
        return $stmt->rowCount();
    }

    /**
     * 週間スケジュール付きで契約を作成
     */
    public function createWithWeeklySchedule($data, $weeklyDays = [], $fileInfo = null) {
        try {
            $this->db->beginTransaction();
            
            // デフォルト値を設定
            if (!isset($data['visit_frequency'])) {
                $data['visit_frequency'] = 'monthly';
            }
            
            if (!isset($data['taxi_allowed'])) {
                $data['taxi_allowed'] = 0;
            }
            
            // 祝日除外のデフォルト値を設定
            if (!isset($data['exclude_holidays'])) {
                $data['exclude_holidays'] = 1; // デフォルトは祝日除外
            }
            
            // 税種別のデフォルト値を設定
            if (!isset($data['tax_type'])) {
                $data['tax_type'] = 'exclusive';
            }
            
            // 遠隔相談・書面作成のデフォルト値を設定
            if (!isset($data['use_remote_consultation'])) {
                $data['use_remote_consultation'] = 0;
            }
            if (!isset($data['use_document_creation'])) {
                $data['use_document_creation'] = 0;
            }
            
            // バージョン番号を設定（新規作成時は常に1）
            $data['version_number'] = 1;
            
            // 反映日を設定（契約開始日と同じ）
            $data['effective_date'] = $data['start_date'];
            
            // 反映終了日はNULL（最新版）
            $data['effective_end_date'] = null;
            
            // parent_contract_idはNULL（初版）
            $data['parent_contract_id'] = null;
            
            // ファイル情報を追加
            if ($fileInfo) {
                $data['contract_file_path'] = $fileInfo['file_path'];
                $data['contract_file_name'] = $fileInfo['file_name'];
                $data['contract_file_size'] = $fileInfo['file_size'];
            }
            
            $contractId = $this->create($data);
            
            // 週間スケジュールが指定されている場合は保存
            if ($data['visit_frequency'] === 'weekly' && !empty($weeklyDays)) {
                $this->saveWeeklySchedule($contractId, $weeklyDays);
            }
            
            $this->db->commit();
            return $contractId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 週間スケジュール付きで契約を更新
     */
    public function updateWithWeeklySchedule($id, $data, $weeklyDays = [], $fileInfo = null, $removeFile = false) {
        try {
            $this->db->beginTransaction();
            
            // 現在の契約情報を取得
            $currentContract = $this->findById($id);
            if (!$currentContract) {
                throw new Exception('契約が見つかりません');
            }
            
            // デフォルト値を設定
            if (!isset($data['visit_frequency'])) {
                $data['visit_frequency'] = $currentContract['visit_frequency'] ?? 'monthly';
            }
            
            if (!isset($data['taxi_allowed'])) {
                $data['taxi_allowed'] = $currentContract['taxi_allowed'] ?? 0;
            }
            
            if (!isset($data['exclude_holidays'])) {
                $data['exclude_holidays'] = $currentContract['exclude_holidays'] ?? 1;
            }
            
            if (!isset($data['tax_type'])) {
                $data['tax_type'] = $currentContract['tax_type'] ?? 'exclusive';
            }
            
            // 反映日が指定されていない場合は現在の反映日を使用
            if (!isset($data['effective_date'])) {
                $data['effective_date'] = $currentContract['effective_date'];
            }
            
            // 反映日が1日かチェック
            $effectiveDate = new DateTime($data['effective_date']);
            if ($effectiveDate->format('j') !== '1') {
                throw new Exception('反映日は毎月1日を指定してください');
            }
            
            // 反映日が契約開始日より前かチェック
            $startDate = new DateTime($data['start_date'] ?? $currentContract['start_date']);
            if ($effectiveDate < $startDate) {
                throw new Exception('反映日は契約開始日以降を指定してください');
            }
            
            // 反映日が契約終了日より後かチェック
            if (!empty($data['end_date'])) {
                $endDate = new DateTime($data['end_date']);
                if ($effectiveDate >= $endDate) {
                    throw new Exception('反映日は契約終了日より前を指定してください');
                }
            }
            
            // 反映日が変更されたかチェック
            $isEffectiveDateChanged = ($data['effective_date'] !== $currentContract['effective_date']);
            
            if ($isEffectiveDateChanged) {
                // ★★★ 新しいバージョンとして作成 ★★★
                
                // 初版契約IDを取得(現在の契約がすでに改訂版の場合はそのparent_contract_idを、初版の場合は現在のIDを使用)
                $parentContractId = $currentContract['parent_contract_id'] ?? $currentContract['id'];
                
                // 同じ反映日の契約が既に存在するかチェック
                $checkSql = "SELECT id FROM {$this->table} 
                            WHERE (id = :parent_contract_id OR parent_contract_id = :parent_contract_id2)
                            AND effective_date = :effective_date";
                $stmt = $this->db->prepare($checkSql);
                $stmt->execute([
                    'parent_contract_id' => $parentContractId,
                    'parent_contract_id2' => $parentContractId,
                    'effective_date' => $data['effective_date']
                ]);
                
                if ($stmt->fetch()) {
                    throw new Exception('指定された反映日で既に契約バージョンが存在します');
                }
                
                // 現在のバージョンの反映終了日を更新
                // (現在編集中のバージョンが最新版の場合のみ)
                if (empty($currentContract['effective_end_date'])) {
                    $effectiveDateObj = new DateTime($data['effective_date']);
                    $effectiveDateObj->modify('-1 day');
                    $effectiveEndDate = $effectiveDateObj->format('Y-m-d');
                    
                    $updateCurrentSql = "UPDATE {$this->table} 
                                        SET effective_end_date = :effective_end_date,
                                            updated_at = NOW()
                                        WHERE id = :id";
                    $stmt = $this->db->prepare($updateCurrentSql);
                    $stmt->execute([
                        'effective_end_date' => $effectiveEndDate,
                        'id' => $id
                    ]);
                }
                
                // 新しいバージョン番号を取得
                $maxVersionSql = "SELECT MAX(version_number) as max_version FROM {$this->table} 
                                WHERE id = :parent_contract_id OR parent_contract_id = :parent_contract_id2";
                $stmt = $this->db->prepare($maxVersionSql);
                $stmt->execute([
                    'parent_contract_id' => $parentContractId,
                    'parent_contract_id2' => $parentContractId
                ]);
                $maxVersion = $stmt->fetchColumn();
                
                // 新しいレコードのデータを準備
                $newData = [
                    'doctor_id' => $data['doctor_id'] ?? $currentContract['doctor_id'],
                    'company_id' => $data['company_id'] ?? $currentContract['company_id'],
                    'branch_id' => $data['branch_id'] ?? $currentContract['branch_id'],
                    'regular_visit_hours' => $data['regular_visit_hours'],
                    'visit_frequency' => $data['visit_frequency'],
                    'bimonthly_type' => $data['bimonthly_type'] ?? null,
                    'exclude_holidays' => $data['exclude_holidays'],
                    'taxi_allowed' => $data['taxi_allowed'],
                    'tax_type' => $data['tax_type'],
                    'start_date' => $currentContract['start_date'], // 開始日は変更しない
                    'end_date' => $data['end_date'] ?? $currentContract['end_date'],
                    'contract_status' => $data['contract_status'] ?? $currentContract['contract_status'],
                    'regular_visit_rate' => $data['regular_visit_rate'] ?? null,
                    'regular_extension_rate' => $data['regular_extension_rate'] ?? null,
                    'emergency_visit_rate' => $data['emergency_visit_rate'] ?? null,
                    'document_consultation_rate' => $data['document_consultation_rate'] ?? null,
                    'version_number' => ($maxVersion ?? 1) + 1,
                    'effective_date' => $data['effective_date'],
                    'effective_end_date' => null, // 最新版
                    'parent_contract_id' => $parentContractId
                ];
                
                // ファイル処理
                // 注意: 反映日変更時（新バージョン作成）は物理ファイルを削除しない
                // 既存バージョンと新バージョンで同じファイルを共有する可能性があるため
                if ($removeFile) {
                    // ファイル削除フラグがある場合は、新バージョンにはファイル情報を含めない
                    // ただし、物理ファイルは削除しない（旧バージョンで使用中の可能性があるため）
                    $newData['contract_file_path'] = null;
                    $newData['contract_file_name'] = null;
                    $newData['contract_file_size'] = null;
                } elseif ($fileInfo) {
                    // 新しいファイルがアップロードされた場合
                    // 新バージョンには新しいファイル情報を設定
                    // 旧ファイルは削除しない（旧バージョンで使用中のため）
                    $newData['contract_file_path'] = $fileInfo['file_path'];
                    $newData['contract_file_name'] = $fileInfo['file_name'];
                    $newData['contract_file_size'] = $fileInfo['file_size'];
                } else {
                    // ファイルが変更されていない場合は現在のファイル情報を引き継ぐ
                    $newData['contract_file_path'] = $currentContract['contract_file_path'];
                    $newData['contract_file_name'] = $currentContract['contract_file_name'];
                    $newData['contract_file_size'] = $currentContract['contract_file_size'];
                }
                
                // 新しいレコードを作成
                $newContractId = $this->create($newData);
                
                // 週間スケジュールの処理
                if ($newData['visit_frequency'] === 'weekly') {
                    // 現在のスケジュールを引き継ぐか、新しいスケジュールを設定
                    if (!empty($weeklyDays)) {
                        $this->saveWeeklySchedule($newContractId, $weeklyDays);
                    } else {
                        // 現在のスケジュールを引き継ぐ
                        $currentScheduleSql = "SELECT day_of_week FROM contract_weekly_schedules 
                                            WHERE contract_id = :contract_id";
                        $stmt = $this->db->prepare($currentScheduleSql);
                        $stmt->execute(['contract_id' => $id]);
                        $currentSchedule = array_column($stmt->fetchAll(), 'day_of_week');
                        
                        if (!empty($currentSchedule)) {
                            $this->saveWeeklySchedule($newContractId, $currentSchedule);
                        }
                    }
                }
                
                $this->db->commit();
                
                // 新しいバージョンのIDを返す
                return $newContractId;
                
            } else {
                // ★★★ 反映日が同じ場合は既存レコードを更新 ★★★
                
                // ファイル処理
                if ($removeFile) {
                    if (!empty($currentContract['contract_file_path'])) {
                        $this->deletePhysicalFile($currentContract['contract_file_path']);
                    }
                    $data['contract_file_path'] = null;
                    $data['contract_file_name'] = null;
                    $data['contract_file_size'] = null;
                } elseif ($fileInfo) {
                    if (!empty($currentContract['contract_file_path'])) {
                        $this->deletePhysicalFile($currentContract['contract_file_path']);
                    }
                    $data['contract_file_path'] = $fileInfo['file_path'];
                    $data['contract_file_name'] = $fileInfo['file_name'];
                    $data['contract_file_size'] = $fileInfo['file_size'];
                }
                
                // バージョン関連のフィールドは変更しない
                unset($data['version_number']);
                unset($data['parent_contract_id']);
                unset($data['effective_end_date']);
                
                // start_dateも変更しない
                unset($data['start_date']);
                
                $result = $this->update($id, $data);
                
                // 週間スケジュールの処理
                if ($data['visit_frequency'] === 'weekly') {
                    $this->saveWeeklySchedule($id, $weeklyDays);
                } else {
                    $this->deleteWeeklySchedule($id);
                }
                
                $this->db->commit();
                return $result;
            }
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * 祝日除外設定のラベルを取得
     */
    public function getExcludeHolidaysLabel($excludeHolidays) {
        return $excludeHolidays ? '祝日非訪問' : '祝日訪問可';
    }

    /**
     * 祝日除外設定別の契約統計を取得
     */
    public function getHolidayExclusionStatistics() {
        $sql = "SELECT 
                    exclude_holidays,
                    COUNT(*) as contract_count,
                    SUM(CASE WHEN contract_status = 'active' THEN 1 ELSE 0 END) as active_count,
                    AVG(regular_visit_hours) as avg_hours
                FROM {$this->table}
                WHERE visit_frequency = 'weekly'
                GROUP BY exclude_holidays
                ORDER BY exclude_holidays";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = $stmt->fetchAll();
        
        // 統計情報に詳細を追加
        foreach ($stats as &$stat) {
            $stat['exclude_holidays_label'] = $this->getExcludeHolidaysLabel($stat['exclude_holidays']);
        }
        unset($stat);
        
        return $stats;
    }

    /**
     * 祝日除外設定での契約検索
     */
    public function findByHolidayExclusion($excludeHolidays = true) {
        $sql = "SELECT co.*, c.name as company_name, b.name as branch_name, 
                u.name as doctor_name
                FROM {$this->table} co
                JOIN branches b ON co.branch_id = b.id
                JOIN companies c ON co.company_id = c.id
                JOIN users u ON co.doctor_id = u.id
                WHERE co.exclude_holidays = :exclude_holidays 
                AND co.visit_frequency = 'weekly'
                AND co.contract_status = 'active'
                ORDER BY c.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['exclude_holidays' => $excludeHolidays ? 1 : 0]);
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に詳細情報を追加
        foreach ($contracts as &$contract) {
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $this->getTaxTypeLabel($contract['tax_type']);
            $contract['exclude_holidays_label'] = $this->getExcludeHolidaysLabel($contract['exclude_holidays']);
        }
        unset($contract);
        
        return $contracts;
    }

    /**
     * 祝日除外設定を更新（マイグレーション用）
     */
    public function migrateExcludeHolidays() {
        $sql = "UPDATE {$this->table} 
                SET exclude_holidays = 1 
                WHERE exclude_holidays IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        
        return $stmt->rowCount();
    }

    /**
     * 週間スケジュールを保存
     */
    private function saveWeeklySchedule($contractId, $dayNumbers) {
        // 既存のスケジュールを削除
        $this->deleteWeeklySchedule($contractId);
        
        // 新しいスケジュールを挿入
        if (!empty($dayNumbers)) {
            $sql = "INSERT INTO contract_weekly_schedules (contract_id, day_of_week) 
                    VALUES (:contract_id, :day_of_week)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($dayNumbers as $dayOfWeek) {
                if ($dayOfWeek >= 1 && $dayOfWeek <= 7) {
                    $stmt->execute([
                        'contract_id' => $contractId,
                        'day_of_week' => $dayOfWeek
                    ]);
                }
            }
        }
    }

    /**
     * 契約の詳細情報を取得（週間スケジュール付き）
     */
    public function findWithDetailsAndSchedule($contractId) {
        $contract = $this->findWithDetails($contractId);
        
        if (!$contract) {
            return null;
        }
        
        // 週間スケジュール情報を追加
        if ($contract['visit_frequency'] === 'weekly') {
            $weeklyDays = get_weekly_schedule($contractId, $this->db);
            $contract['weekly_days'] = $weeklyDays;
            $contract['weekly_schedule'] = format_weekly_schedule($weeklyDays);
            
            // 今月の詳細情報
            $currentYear = date('Y');
            $currentMonth = date('n');
            $weeklyDetails = get_weekly_contract_details($contractId, $currentYear, $currentMonth, $this->db);
            $contract['weekly_details'] = $weeklyDetails;
        }
        
        return $contract;
    }
    
    /**
     * 医師の契約を取得（週間スケジュール付き）
     */
    public function findByDoctorWithSchedule($doctorId) {
        $contracts = $this->findByDoctor($doctorId);
        
        foreach ($contracts as &$contract) {
            if ($contract['visit_frequency'] === 'weekly') {
                $weeklyDays = get_weekly_schedule($contract['id'], $this->db);
                $contract['weekly_days'] = $weeklyDays;
                $contract['weekly_schedule'] = format_weekly_schedule($weeklyDays);
            }
        }
        unset($contract);
        
        return $contracts;
    }

    /**
     * 週間スケジュールを削除
     */
    private function deleteWeeklySchedule($contractId) {
        $sql = "DELETE FROM contract_weekly_schedules WHERE contract_id = :contract_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId]);
    }
    
    /**
     * 週間契約の統計情報を取得
     */
    public function getWeeklyContractStatistics() {
        $sql = "SELECT 
                    c.id,
                    c.doctor_id,
                    u.name as doctor_name,
                    comp.name as company_name,
                    b.name as branch_name,
                    c.regular_visit_hours,
                    GROUP_CONCAT(cws.day_of_week ORDER BY cws.day_of_week) as weekly_days
                FROM contracts c
                JOIN users u ON c.doctor_id = u.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                LEFT JOIN contract_weekly_schedules cws ON c.id = cws.contract_id
                WHERE c.visit_frequency = 'weekly' 
                AND c.contract_status = 'active'
                GROUP BY c.id
                ORDER BY comp.name, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $contracts = $stmt->fetchAll();
        
        // 各契約に詳細情報を追加
        foreach ($contracts as &$contract) {
            $weeklyDays = $contract['weekly_days'] ? explode(',', $contract['weekly_days']) : [];
            $contract['weekly_schedule'] = format_weekly_schedule($weeklyDays);
            
            // 今月の訪問予定回数を計算
            $currentYear = date('Y');
            $currentMonth = date('n');
            $weeklyDetails = get_weekly_contract_details($contract['id'], $currentYear, $currentMonth, $this->db);
            $contract['monthly_visits'] = $weeklyDetails['total_visits'];
            $contract['monthly_hours'] = $weeklyDetails['total_visits'] * $contract['regular_visit_hours'];
        }
        unset($contract);
        
        return $contracts;
    }

}
?>