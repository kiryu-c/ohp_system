<?php
// app/controllers/CsvExportController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';

class CsvExportController extends BaseController
{
    /**
     * 年月選択モーダル用のデータ取得API
     * 締め処理済みの年月リストを返す
     */
    public function getAvailableMonthsApi()
    {
        // ヘッダーを先に設定
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            // セッション確認
            Session::requireLogin();
            
            // 管理者権限チェック
            $userType = Session::get('user_type');
            if ($userType !== 'admin') {
                throw new Exception('管理者権限が必要です');
            }
            
            // データベース接続
            $db = Database::getInstance()->getConnection();
            
            // 確定済みの締め処理記録から年月リストを取得
            $sql = "SELECT DISTINCT 
                        closing_period
                    FROM monthly_closing_records
                    WHERE status = 'finalized'
                    ORDER BY closing_period DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $months = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 結果を返す
            echo json_encode([
                'success' => true,
                'months' => $months,
                'count' => count($months)
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            error_log("Error in getAvailableMonthsApi: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    /**
     * 指定年月の請求CSVをダウンロード
     */
    public function download()
    {
        try {
            // セッション確認
            Session::requireLogin();
            
            // 管理者権限チェック
            $userType = Session::get('user_type');
            if ($userType !== 'admin') {
                $this->setFlash('error', '管理者権限が必要です');
                redirect('dashboard');
                return;
            }
            
            // POSTパラメータから年月を取得
            $yearMonth = $_POST['year_month'] ?? null;
            
            if (empty($yearMonth)) {
                $this->setFlash('error', '年月が指定されていません');
                redirect('dashboard');
                return;
            }
            
            // YYYY-MM形式のバリデーション
            if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
                $this->setFlash('error', '不正な年月形式です');
                redirect('dashboard');
                return;
            }
            
            // データベース接続
            $db = Database::getInstance()->getConnection();
            
            // 指定年月の確定済み締め処理記録を取得
            $closingRecords = $this->getClosingRecordsByYearMonth($db, $yearMonth);
            
            if (empty($closingRecords)) {
                $this->setFlash('error', "指定された年月({$yearMonth})の確定済み締め処理データが見つかりません");
                redirect('dashboard');
                return;
            }
            
            // CSVデータを生成
            $csvData = $this->generateCsvData($db, $closingRecords);
            
            // CSVファイルとして出力
            $this->outputCsv($csvData, $yearMonth);
            
        } catch (Exception $e) {
            error_log("Error in CSV download: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->setFlash('error', 'CSVのダウンロードに失敗しました: ' . $e->getMessage());
            redirect('dashboard');
        }
    }
    
    /**
     * 指定年月の確定済み締め処理記録を取得
     * 
     * @param PDO $db データベース接続
     * @param string $yearMonth YYYY-MM形式
     * @return array 締め処理記録の配列
     */
    private function getClosingRecordsByYearMonth($db, $yearMonth)
    {
        $sql = "SELECT 
                    mcr.id,
                    mcr.contract_id,
                    mcr.closing_period,
                    mcr.total_amount,
                    mcr.tax_amount,
                    mcr.total_amount_with_tax,
                    c.company_id,
                    c.branch_id,
                    c.doctor_id,
                    c.visit_frequency,
                    c.bimonthly_type,
                    comp.name as company_name,
                    br.name as branch_name,
                    u.name as doctor_name,
                    u.trade_name as doctor_trade_name,
                    u.contract_type as doctor_contract_type
                FROM monthly_closing_records mcr
                INNER JOIN contracts c ON mcr.contract_id = c.id
                INNER JOIN companies comp ON c.company_id = comp.id
                LEFT JOIN branches br ON c.branch_id = br.id
                INNER JOIN users u ON c.doctor_id = u.id
                WHERE mcr.closing_period = :year_month
                    AND mcr.status = 'finalized'
                ORDER BY comp.name, br.name, u.name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['year_month' => $yearMonth]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * CSVデータを生成
     * 
     * @param PDO $db データベース接続
     * @param array $closingRecords 締め処理記録の配列
     * @return array CSVデータ(2次元配列)
     */
    private function generateCsvData($db, $closingRecords)
    {
        // CSVヘッダー
        $csvData = [[
            '請求書ID',
            '契約ID',
            '企業名',
            '拠点名',
            '産業医名',
            '産業医屋号',
            '産業医区分',
            '項目名',
            '単価',
            '数量',
            '請求金額',
            '単位',
            '説明・備考',
            '請求書PDF URL'
        ]];
        
        // 各締め処理記録の明細を取得してCSVデータに追加
        foreach ($closingRecords as $record) {
            $details = $this->getClosingDetails($db, $record['id']);
            
            // 請求書PDFのURLを生成（締め処理記録IDを使用）
            $pdfUrl = $this->generatePdfUrl($record['id']);
            
            foreach ($details as $detail) {
                // total, subtotalは出力しない
                if (in_array($detail['item_category'], ['total', 'subtotal'])) {
                    continue;
                }
                
                // 産業医区分の変換
                $doctorType = '';
                if ($record['doctor_contract_type'] === 'corporate') {
                    $doctorType = '法人';
                } elseif ($record['doctor_contract_type'] === 'individual') {
                    $doctorType = '個人';
                }
                
                // 単位の変換
                $unit = $this->translateUnitType($detail['unit_type']);
                
                // 項目名の変換(隔月訪問の場合)
                $itemName = $this->translateItemName(
                    $detail['item_name'],
                    $detail['service_type'],
                    $record['visit_frequency'],
                    $record['bimonthly_type']
                );
                
                $csvData[] = [
                    $record['id'],                                    // 請求書ID
                    $record['contract_id'],                           // 契約ID
                    $record['company_name'],                          // 企業名
                    $record['branch_name'] ?? '',                     // 拠点名
                    $record['doctor_name'],                           // 産業医名
                    $record['doctor_trade_name'] ?? '',               // 産業医屋号
                    $doctorType,                                       // 産業医区分
                    $itemName,                                        // 項目名
                    $detail['unit_price'] !== null ? number_format($detail['unit_price']) : '', // 単価
                    $detail['quantity'] !== null ? $detail['quantity'] : '', // 数量
                    number_format($detail['amount']),                 // 請求金額
                    $unit,                                            // 単位
                    $detail['description'] ?? '',                     // 説明・備考
                    $pdfUrl                                           // 請求書PDF URL
                ];
            }
        }
        
        return $csvData;
    }
    
    /**
     * 締め処理明細を取得
     * 
     * @param PDO $db データベース接続
     * @param int $closingRecordId 締め処理記録ID
     * @return array 明細の配列
     */
    private function getClosingDetails($db, $closingRecordId)
    {
        $sql = "SELECT 
                    item_category,
                    service_type,
                    item_name,
                    unit_type,
                    quantity,
                    unit_price,
                    amount,
                    description
                FROM monthly_closing_details
                WHERE closing_record_id = :closing_record_id
                ORDER BY line_number ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['closing_record_id' => $closingRecordId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 項目名の変換(隔月訪問の場合に対応)
     * 
     * @param string $itemName 元の項目名
     * @param string $serviceType サービス種別
     * @param string $visitFrequency 訪問頻度(monthly/bimonthly/weekly)
     * @param string|null $bimonthlyType 隔月タイプ(even:偶数月/odd:奇数月)
     * @return string 変換後の項目名
     */
    private function translateItemName($itemName, $serviceType, $visitFrequency, $bimonthlyType)
    {
        // 定期訪問かつ隔月の場合のみ項目名を変換
        if ($serviceType === 'regular' && $visitFrequency === 'bimonthly') {
            // 隔月タイプに応じて変換
            if ($bimonthlyType === 'odd') {
                return '定期訪問(隔月奇数)';
            } elseif ($bimonthlyType === 'even') {
                return '定期訪問(隔月偶数)';
            }
        }
        
        // それ以外は元の項目名をそのまま返す
        return $itemName;
    }
    
    /**
     * 単位種別の日本語変換
     * 
     * @param string $type 単位種別
     * @return string 日本語の単位
     */
    private function translateUnitType($type)
    {
        $translations = [
            'hours' => '時間',
            'times' => '回',
            'amount' => '金額'
        ];
        
        return $translations[$type] ?? '';
    }
    
    /**
     * 請求書PDFのURLを生成
     * 既存のInvoiceDownloadControllerを使用
     * 
     * @param int $closingRecordId 締め処理記録ID
     * @return string URL
     */
    private function generatePdfUrl($closingRecordId)
    {
        if (empty($closingRecordId)) {
            return '';
        }
        
        // プロトコルとホストを取得
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // スクリプト名からベースパスを取得
        // 例: /op_website/public/index.php → /op_website/public
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        
        // Windowsパスの場合、バックスラッシュをスラッシュに変換
        $basePath = str_replace('\\', '/', $basePath);
        
        // ルートの場合は空文字に
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }
        
        // 正しいルートパスでURLを生成
        // web.phpのルート定義: /invoice-download/{id}
        return $protocol . '://' . $host . $basePath . '/invoice-download/' . $closingRecordId;
    }
    
    /**
     * CSVファイルとして出力
     * 
     * @param array $csvData CSVデータ(2次元配列)
     * @param string $yearMonth 年月(ファイル名用)
     */
    private function outputCsv($csvData, $yearMonth)
    {
        // ファイル名を生成(例:請求明細_2025-01.csv)
        $filename = "請求明細_{$yearMonth}.csv";
        
        // ヘッダー設定
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // BOM付きUTF-8で出力(Excelで文字化けしないように)
        echo "\xEF\xBB\xBF";
        
        // 出力バッファを開く
        $output = fopen('php://output', 'w');
        
        // CSVデータを出力
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}