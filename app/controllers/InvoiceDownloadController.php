<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../core/Database.php';

class InvoiceDownloadController extends BaseController
{
    /**
     * 請求書PDFダウンロード
     */
    public function download($closingRecordId = null)
    {
        Session::requireLogin();
        
        if ($closingRecordId === null) {
            $closingRecordId = (int)($_GET['id'] ?? 0);
        } else {
            $closingRecordId = (int)$closingRecordId;
        }
        
        if (!$closingRecordId) {
            $this->setFlash('error', '請求書IDが指定されていません。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        $userType = Session::get('user_type');
        $userId = Session::get('user_id');
        
        // 権限チェック付きで締め記録を取得
        if ($userType === 'doctor') {
            $sql = "SELECT mcr.*, c.doctor_id 
                    FROM monthly_closing_records mcr
                    JOIN contracts c ON mcr.contract_id = c.id
                    WHERE mcr.id = :id AND c.doctor_id = :user_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $closingRecordId, 'user_id' => $userId]);
            
        } elseif ($userType === 'admin') {
            $sql = "SELECT * FROM monthly_closing_records WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $closingRecordId]);
            
        } else {
            // 企業ユーザーはアクセス不可
            $this->setFlash('error', 'この請求書にアクセスする権限がありません。');
            redirect('closing');
        }
        
        $record = $stmt->fetch();
        
        if (!$record) {
            $this->setFlash('error', '請求書が見つかりません。');
            redirect('closing');
        }
        
        if (empty($record['invoice_pdf_path'])) {
            $this->setFlash('error', '請求書PDFが生成されていません。');
            redirect('closing');
        }
        
        $filepath = $record['invoice_pdf_path'];
        
        if (!file_exists($filepath)) {
            $this->setFlash('error', '請求書ファイルが見つかりません。');
            redirect('closing');
        }
        
        // PDFを配信
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($filepath);
        exit;
    }
}