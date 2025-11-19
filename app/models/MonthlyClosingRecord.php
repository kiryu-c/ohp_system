<?php
// app/models/MonthlyClosingRecord.php
require_once __DIR__ . '/../core/Database.php';

class MonthlyClosingRecord
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * 締め記録を作成
     */
    public function create($data)
    {
        $sql = "INSERT INTO monthly_closing_records (
                    contract_id, doctor_id, closing_period, contract_hours,
                    total_approved_hours, regular_hours, regular_amount,
                    regular_extension_hours, regular_extension_amount,
                    emergency_hours, emergency_amount, document_count, document_amount,
                    remote_consultation_count, remote_consultation_amount,
                    spot_hours, spot_amount, spot_count,
                    other_count, other_amount,
                    total_amount, tax_rate, tax_amount, total_amount_with_tax,
                    status, simulation_data, finalized_at, finalized_by, created_at, updated_at
                ) VALUES (
                    :contract_id, :doctor_id, :closing_period, :contract_hours,
                    :total_approved_hours, :regular_hours, :regular_amount,
                    :regular_extension_hours, :regular_extension_amount,
                    :emergency_hours, :emergency_amount, :document_count, :document_amount,
                    :remote_consultation_count, :remote_consultation_amount,
                    :spot_hours, :spot_amount, :spot_count,
                    :other_count, :other_amount,
                    :total_amount, :tax_rate, :tax_amount, :total_amount_with_tax,
                    :status, :simulation_data, :finalized_at, :finalized_by, NOW(), NOW()
                )";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    /**
     * 締め記録を更新
     */
    public function update($id, $data)
    {
        $sql = "UPDATE monthly_closing_records SET
                    doctor_id = :doctor_id,
                    contract_hours = :contract_hours,
                    total_approved_hours = :total_approved_hours,
                    regular_hours = :regular_hours,
                    regular_amount = :regular_amount,
                    regular_extension_hours = :regular_extension_hours,
                    regular_extension_amount = :regular_extension_amount,
                    emergency_hours = :emergency_hours,
                    emergency_amount = :emergency_amount,
                    document_count = :document_count,
                    document_amount = :document_amount,
                    remote_consultation_count = :remote_consultation_count,
                    remote_consultation_amount = :remote_consultation_amount,
                    spot_hours = :spot_hours,
                    spot_amount = :spot_amount,
                    spot_count = :spot_count,
                    other_count = :other_count,
                    other_amount = :other_amount,
                    total_amount = :total_amount,
                    tax_rate = :tax_rate,
                    tax_amount = :tax_amount,
                    total_amount_with_tax = :total_amount_with_tax,
                    status = :status,
                    simulation_data = :simulation_data,
                    finalized_at = :finalized_at,
                    finalized_by = :finalized_by,
                    updated_at = NOW()
                WHERE id = :id";
        
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    /**
     * 締め記録をIDで取得
     */
    public function findById($id)
    {
        $sql = "SELECT mcr.*, 
                       c.regular_visit_hours,
                       comp.name as company_name,
                       b.name as branch_name,
                       u.name as doctor_name,
                       finalizer.name as finalizer_name
                FROM monthly_closing_records mcr
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                JOIN users u ON mcr.doctor_id = u.id
                LEFT JOIN users finalizer ON mcr.finalized_by = finalizer.id
                WHERE mcr.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * 契約と期間で締め記録を取得
     */
    public function findByContractAndPeriod($contractId, $closingPeriod)
    {
        $sql = "SELECT mcr.*, 
                       c.regular_visit_hours,
                       comp.name as company_name,
                       b.name as branch_name,
                       u.name as doctor_name,
                       finalizer.name as finalizer_name
                FROM monthly_closing_records mcr
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                JOIN users u ON mcr.doctor_id = u.id
                LEFT JOIN users finalizer ON mcr.finalized_by = finalizer.id
                WHERE mcr.contract_id = :contract_id 
                AND mcr.closing_period = :closing_period";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'closing_period' => $closingPeriod
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * 産業医の締め記録一覧を取得
     */
    public function findByDoctor($doctorId, $year = null)
    {
        $sql = "SELECT mcr.*, 
                       c.regular_visit_hours,
                       comp.name as company_name,
                       b.name as branch_name,
                       finalizer.name as finalizer_name
                FROM monthly_closing_records mcr
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                LEFT JOIN users finalizer ON mcr.finalized_by = finalizer.id
                WHERE mcr.doctor_id = :doctor_id";
        
        $params = ['doctor_id' => $doctorId];
        
        if ($year) {
            $sql .= " AND YEAR(STR_TO_DATE(CONCAT(mcr.closing_period, '-01'), '%Y-%m-%d')) = :year";
            $params['year'] = $year;
        }
        
        $sql .= " ORDER BY mcr.closing_period DESC, comp.name, b.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 企業の締め記録一覧を取得
     */
    public function findByCompany($companyId, $year = null)
    {
        $sql = "SELECT mcr.*, 
                       c.regular_visit_hours,
                       comp.name as company_name,
                       b.name as branch_name,
                       u.name as doctor_name,
                       finalizer.name as finalizer_name
                FROM monthly_closing_records mcr
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                JOIN users u ON mcr.doctor_id = u.id
                LEFT JOIN users finalizer ON mcr.finalized_by = finalizer.id
                WHERE comp.id = :company_id";
        
        $params = ['company_id' => $companyId];
        
        if ($year) {
            $sql .= " AND YEAR(STR_TO_DATE(CONCAT(mcr.closing_period, '-01'), '%Y-%m-%d')) = :year";
            $params['year'] = $year;
        }
        
        $sql .= " ORDER BY mcr.closing_period DESC, b.name, u.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 締め記録を削除（論理削除ではなく物理削除）
     */
    public function delete($id)
    {
        $sql = "DELETE FROM monthly_closing_records WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * 月次サマリーを取得
     */
    public function getMonthlySummary($contractId, $year, $month)
    {
        $closingPeriod = sprintf('%04d-%02d', $year, $month);
        
        $sql = "SELECT 
                    mcr.total_approved_hours,
                    mcr.regular_hours,
                    mcr.regular_amount,
                    mcr.regular_extension_hours,
                    mcr.regular_extension_amount,
                    mcr.emergency_hours,
                    mcr.emergency_amount,
                    mcr.document_count,
                    mcr.document_amount,
                    mcr.remote_consultation_count,
                    mcr.remote_consultation_amount,
                    mcr.total_amount,
                    mcr.tax_amount,
                    mcr.total_amount_with_tax,
                    mcr.status,
                    mcr.finalized_at
                FROM monthly_closing_records mcr
                WHERE mcr.contract_id = :contract_id 
                AND mcr.closing_period = :closing_period";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'closing_period' => $closingPeriod
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * 年間のサマリーを取得
     */
    public function getYearlySummary($contractId, $year)
    {
        $sql = "SELECT 
                    SUM(mcr.total_approved_hours) as total_hours,
                    SUM(mcr.regular_hours) as regular_hours,
                    SUM(mcr.regular_amount) as regular_amount,
                    SUM(mcr.regular_extension_hours) as regular_extension_hours,
                    SUM(mcr.regular_extension_amount) as regular_extension_amount,
                    SUM(mcr.emergency_hours) as emergency_hours,
                    SUM(mcr.emergency_amount) as emergency_amount,
                    SUM(mcr.document_count) as document_count,
                    SUM(mcr.document_amount) as document_amount,
                    SUM(mcr.remote_consultation_count) as remote_consultation_count,
                    SUM(mcr.remote_consultation_amount) as remote_consultation_amount,
                    SUM(mcr.total_amount) as total_amount,
                    SUM(mcr.tax_amount) as tax_amount,
                    SUM(mcr.total_amount_with_tax) as total_amount_with_tax,
                    COUNT(*) as closed_months
                FROM monthly_closing_records mcr
                WHERE mcr.contract_id = :contract_id 
                AND YEAR(STR_TO_DATE(CONCAT(mcr.closing_period, '-01'), '%Y-%m-%d')) = :year
                AND mcr.status = 'finalized'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * 契約の未締めの月を取得
     */
    public function getUnclosedMonths($contractId, $year = null)
    {
        $currentYear = $year ?: date('Y');
        
        // 当年の1月から現在月まで、または指定年の12ヶ月間をチェック
        $endMonth = ($currentYear == date('Y')) ? (int)date('n') : 12;
        
        $unclosedMonths = [];
        
        for ($month = 1; $month <= $endMonth; $month++) {
            $closingPeriod = sprintf('%04d-%02d', $currentYear, $month);
            
            // 締め記録があるかチェック
            $sql = "SELECT COUNT(*) FROM monthly_closing_records 
                    WHERE contract_id = :contract_id 
                    AND closing_period = :closing_period 
                    AND status = 'finalized'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'contract_id' => $contractId,
                'closing_period' => $closingPeriod
            ]);
            
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                // その月に承認済み役務記録があるかチェック
                $serviceSql = "SELECT COUNT(*) FROM service_records 
                               WHERE contract_id = :contract_id 
                               AND YEAR(service_date) = :year 
                               AND MONTH(service_date) = :month 
                               AND status = 'approved'";
                
                $serviceStmt = $this->db->prepare($serviceSql);
                $serviceStmt->execute([
                    'contract_id' => $contractId,
                    'year' => $currentYear,
                    'month' => $month
                ]);
                
                $serviceCount = $serviceStmt->fetchColumn();
                
                if ($serviceCount > 0) {
                    $unclosedMonths[] = [
                        'year' => $currentYear,
                        'month' => $month,
                        'closing_period' => $closingPeriod,
                        'service_records_count' => $serviceCount
                    ];
                }
            }
        }
        
        return $unclosedMonths;
    }
}
?>