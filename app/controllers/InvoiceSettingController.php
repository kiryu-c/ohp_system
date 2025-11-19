<?php
// app/controllers/InvoiceSettingController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/InvoiceSetting.php';

class InvoiceSettingController extends BaseController {
    
    /**
     * 請求書設定の表示・編集画面
     */
    public function index() {
        Session::requireUserType('admin');
        
        $invoiceSettingModel = new InvoiceSetting();
        $setting = $invoiceSettingModel->getSetting();
        
        ob_start();
        include __DIR__ . '/../views/invoice_settings/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 請求書設定の保存
     */
    public function store() {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect('invoice_settings');
        }
        
        // フォームデータ取得
        $companyName = sanitize($_POST['company_name'] ?? '');
        $postalCode = sanitize($_POST['postal_code'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $departmentName = sanitize($_POST['department_name'] ?? '');
        $paymentMonthOffset = (int)($_POST['payment_month_offset'] ?? 1);
        $paymentDayOfMonth = (int)($_POST['payment_day_of_month'] ?? 31);
        $invoiceNote = sanitize($_POST['invoice_note'] ?? '');
        $taxRate = (float)($_POST['tax_rate'] ?? 0.10);
        
        // バリデーション
        $errors = [];
        
        if (empty($companyName)) {
            $errors[] = '企業名を入力してください。';
        }
        
        if (empty($postalCode)) {
            $errors[] = '郵便番号を入力してください。';
        } elseif (!preg_match('/^\d{3}-?\d{4}$/', $postalCode)) {
            $errors[] = '郵便番号は123-4567の形式で入力してください。';
        }
        
        if (empty($address)) {
            $errors[] = '住所を入力してください。';
        }
        
        if (!in_array($paymentMonthOffset, [1, 2])) {
            $errors[] = '支払月は翌月または翌々月を選択してください。';
        }
        
        if ($paymentDayOfMonth < 1 || $paymentDayOfMonth > 31) {
            $errors[] = '支払日は1〜31の範囲で入力してください。';
        }
        
        if ($taxRate < 0 || $taxRate > 1) {
            $errors[] = '消費税率は0%〜100%の範囲で入力してください。';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect('invoice_settings');
        }
        
        // 郵便番号のハイフン整形
        $postalCode = preg_replace('/(\d{3})(\d{4})/', '$1-$2', str_replace('-', '', $postalCode));
        
        $data = [
            'company_name' => $companyName,
            'postal_code' => $postalCode,
            'address' => $address,
            'department_name' => $departmentName,
            'payment_month_offset' => $paymentMonthOffset,
            'payment_day_of_month' => $paymentDayOfMonth,
            'invoice_note' => $invoiceNote,
            'tax_rate' => $taxRate,
            'updated_by' => Session::get('user_id')
        ];
        
        $invoiceSettingModel = new InvoiceSetting();
        $existingSetting = $invoiceSettingModel->getSetting();
        
        if ($existingSetting) {
            // 更新
            if ($invoiceSettingModel->updateSetting($data)) {
                $this->setFlash('success', '請求書設定を更新しました。');
            } else {
                $this->setFlash('error', '請求書設定の更新に失敗しました。');
            }
        } else {
            // 新規作成
            $data['created_by'] = Session::get('user_id');
            if ($invoiceSettingModel->createSetting($data)) {
                $this->setFlash('success', '請求書設定を登録しました。');
            } else {
                $this->setFlash('error', '請求書設定の登録に失敗しました。');
            }
        }
        
        redirect('invoice_settings');
    }
}
?>