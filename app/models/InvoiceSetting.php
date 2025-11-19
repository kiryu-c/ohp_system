<?php
// app/models/InvoiceSetting.php
require_once __DIR__ . '/BaseModel.php';

class InvoiceSetting extends BaseModel {
    protected $table = 'invoice_settings';
    
    /**
     * 請求書設定を取得（システム全体で1レコードのみ）
     */
    public function getSetting() {
        $sql = "SELECT * FROM {$this->table} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * 請求書設定を新規作成
     */
    public function createSetting($data) {
        $sql = "INSERT INTO {$this->table} (
                company_name, 
                postal_code, 
                address, 
                department_name, 
                payment_month_offset, 
                payment_day_of_month, 
                invoice_note,
                tax_rate,
                created_by,
                updated_by,
                created_at,
                updated_at
            ) VALUES (
                :company_name,
                :postal_code,
                :address,
                :department_name,
                :payment_month_offset,
                :payment_day_of_month,
                :invoice_note,
                :tax_rate,
                :created_by,
                :updated_by,
                NOW(),
                NOW()
            )";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'company_name' => $data['company_name'],
            'postal_code' => $data['postal_code'],
            'address' => $data['address'],
            'department_name' => $data['department_name'] ?? null,
            'payment_month_offset' => $data['payment_month_offset'],
            'payment_day_of_month' => $data['payment_day_of_month'],
            'invoice_note' => $data['invoice_note'] ?? null,
            'tax_rate' => $data['tax_rate'] ?? 0.10,
            'created_by' => $data['created_by'],
            'updated_by' => $data['updated_by']
        ]);
    }
    
    /**
     * 請求書設定を更新
     */
    public function updateSetting($data) {
        $sql = "UPDATE {$this->table} SET
                    company_name = :company_name,
                    postal_code = :postal_code,
                    address = :address,
                    department_name = :department_name,
                    payment_month_offset = :payment_month_offset,
                    payment_day_of_month = :payment_day_of_month,
                    invoice_note = :invoice_note,
                    tax_rate = :tax_rate,,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = (SELECT id FROM {$this->table} LIMIT 1)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'company_name' => $data['company_name'],
            'postal_code' => $data['postal_code'],
            'address' => $data['address'],
            'department_name' => $data['department_name'] ?? null,
            'payment_month_offset' => $data['payment_month_offset'],
            'payment_day_of_month' => $data['payment_day_of_month'],
            'invoice_note' => $data['invoice_note'] ?? null,
            'tax_rate' => $data['tax_rate'] ?? 0.10,
            'updated_by' => $data['updated_by']
        ]);
    }
    
    /**
     * 支払期日を計算
     * @param string $closingPeriod 締め対象月（YYYY-MM形式）
     * @return string 支払期日（YYYY-MM-DD形式）
     */
    public function calculatePaymentDate($closingPeriod) {
        $setting = $this->getSetting();
        if (!$setting) {
            return null;
        }
        
        list($year, $month) = explode('-', $closingPeriod);
        
        // 支払月を計算
        $paymentMonth = (int)$month + $setting['payment_month_offset'];
        $paymentYear = (int)$year;
        
        if ($paymentMonth > 12) {
            $paymentMonth -= 12;
            $paymentYear++;
        }
        
        // 支払日を計算（月末日を超えないように調整）
        $lastDayOfMonth = date('t', strtotime("{$paymentYear}-{$paymentMonth}-01"));
        $paymentDay = min($setting['payment_day_of_month'], $lastDayOfMonth);
        
        return sprintf('%04d-%02d-%02d', $paymentYear, $paymentMonth, $paymentDay);
    }
}
?>