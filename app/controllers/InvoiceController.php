<?php
// app/controllers/InvoiceController.php - 請求書PDF生成コントローラー
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../vendor/tcpdf/tcpdf.php';
require_once __DIR__ . '/../core/Database.php';

class InvoiceController extends BaseController
{
    /**
     * 請求書PDF生成
     */
    public function generate($closingRecordId)
    {
        // 締め処理データを取得
        $invoiceData = $this->getInvoiceData($closingRecordId);
        
        if (!$invoiceData) {
            throw new Exception('締め処理データが見つかりません。');
        }
        
        // simulation_dataをデコード(役務明細が含まれる)
        $simulationData = json_decode($invoiceData['simulation_data'], true);
        
        if (!$simulationData) {
            throw new Exception('シミュレーションデータが不正です。');
        }
        
        // PDF生成
        $pdf = $this->createInvoicePDF($invoiceData, $simulationData);
        
        // PDFを保存
        $filepath = $this->savePDF($pdf, $closingRecordId, $invoiceData);
        
        return $filepath;
    }
    
    /**
     * 請求書データ取得
     */
    private function getInvoiceData($closingRecordId)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT 
                    mcr.*,
                    u.name as doctor_name,
                    u.trade_name,
                    u.postal_code as doctor_postal_code,
                    u.address as doctor_address,
                    u.bank_name,
                    u.bank_branch_name,
                    u.bank_account_type,
                    u.bank_account_number,
                    u.bank_account_holder,
                    c.company_id,
                    c.branch_id,
                    c.tax_type,
                    c.visit_frequency,
                    c.use_remote_consultation,
                    c.use_document_creation,
                    comp.name as company_name,
                    b.name as branch_name,
                    invs.company_name as invoice_company_name,
                    invs.postal_code as invoice_postal_code,
                    invs.address as invoice_address,
                    invs.department_name as invoice_department_name
                FROM monthly_closing_records mcr
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN users u ON mcr.doctor_id = u.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                LEFT JOIN invoice_settings invs ON 1=1
                WHERE mcr.id = :closing_record_id
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['closing_record_id' => $closingRecordId]);
        
        return $stmt->fetch();
    }
    
    /**
     * PDF生成
     */
    private function createInvoicePDF($invoiceData, $simulationData)
    {
        // PDF初期化
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // ドキュメント情報
        $pdf->SetCreator('産業医システム');
        $pdf->SetAuthor($invoiceData['trade_name'] ?: $invoiceData['doctor_name']);
        $pdf->SetTitle('請求書 ' . $invoiceData['closing_period']);
        
        // ヘッダー・フッターを無効化
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // マージン設定
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // フォント設定(日本語対応)
        //$pdf->SetFont('kozgopromedium', '', 10);
        $pdf->setFont('notosansjp', '', 10);
        
        // ページ追加
        $pdf->AddPage();
        
        // ページ番号を右上に追加
        $pdf->SetY(10);
        $pdf->SetX(-30);
        $pdf->SetFont('notosansjp', '', 9);
        $pageNum = $pdf->getAliasNumPage();
        $totalPages = $pdf->getAliasNbPages();
        $pdf->Cell(0, 0, $pageNum . ' / ' . $totalPages, 0, 0, 'R');
        
        // フォントを元に戻して位置をリセット
        $pdf->SetFont('notosansjp', '', 10);
        $pdf->SetY(15);
        
        // 請求書HTML生成
        $html = $this->generateInvoiceHTML($invoiceData, $simulationData);
        
        // HTML出力
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf;
    }
    
    /**
     * 請求書HTML生成
     */
    private function generateInvoiceHTML($data, $simulation)
    {
        // 発行元名(屋号優先)
        $issuerName = $data['trade_name'] ?: $data['doctor_name'];
        
        // 請求先(invoice_settingsから取得、なければ企業名を使用)
        $recipientName = $data['invoice_company_name'] ?: $data['company_name'];
        $recipientDept = $data['invoice_department_name'] ? $data['invoice_department_name'] . ' 様' : '';
        $recipientPostalCode = $data['invoice_postal_code'] ?: '';
        $recipientAddress = $data['invoice_address'] ?: '';
        
        // 請求日(締め処理確定日)
        $invoiceDate = date('Y年m月d日', strtotime($data['finalized_at']));
        
        // 請求期間
        $closingPeriod = date('Y年m月分', strtotime($data['closing_period'] . '-01'));
        
        // 銀行口座種別
        $accountTypeMap = [
            'ordinary' => '普通',
            'current' => '当座',
            'savings' => '貯蓄'
        ];
        $accountType = $accountTypeMap[$data['bank_account_type']] ?? '';
        
        $html = '
        <style>
            table { border-collapse: collapse; width: 100%; }
            .header-table td { padding: 3px; vertical-align: top; }
            .detail-table { margin-top: 10px; }
            .detail-table th, .detail-table td { 
                border: 1px solid #000; 
                padding: 5px; 
                font-size: 9pt;
            }
            .detail-table th { 
                background-color: #f0f0f0; 
                font-weight: bold;
                text-align: center;
            }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .title { font-size: 18pt; font-weight: bold; text-align: center; margin: 20px 0; }
            .section-title { 
                font-weight: bold; 
                background-color: #e0e0e0; 
                padding: 5px; 
                margin-top: 15px;
            }
            .spacer { margin-top: 20px; }
            .info-box { 
                border: 1px solid #000; 
                padding: 8px; 
                background-color: #fafafa;
            }
        </style>
        
        <div class="title">請　求　書</div>
        
        <!-- 請求日・請求期間(中央寄せ) -->
        <div style="text-align: right; font-size: 9pt; margin-bottom: 15px;">
            <div style="margin-bottom: 3px;">請求日:' . $invoiceDate . '</div>
            <div>請求期間:' . $closingPeriod . '</div>
        </div>
        
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <!-- 請求先情報 -->
                    <div style="font-size: 12pt; font-weight: bold;">' . h($recipientName) . '</div>';
        
        if ($recipientPostalCode && $recipientAddress) {
            $html .= '
                    <div style="margin-top: 5px; font-size: 9pt;">
                        〒' . h($recipientPostalCode) . '<br>
                        ' . h($recipientAddress) . '
                    </div>';
        }
        
        if ($recipientDept) {
            $html .= '
                    <div style="font-size: 11pt; margin-top: 8px;">' . h($recipientDept) . '</div>';
        }
        
        $html .= '
                </td>
                <td style="width: 50%; text-align: right;">
                    <!-- 発行元情報 -->
                    <div style="font-size: 11pt; font-weight: bold;">' . h($issuerName) . '</div>
                    <div style="font-size: 9pt; margin-top: 3px;">
                        〒' . h($data['doctor_postal_code']) . '<br>
                        ' . h($data['doctor_address']) . '
                    </div>
                </td>
            </tr>
        </table>
        
        <div class="spacer"></div>
        
        <!-- 請求金額と振込先情報を横並び -->
        <table style="margin-top: 20px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <!-- 請求金額 -->
                    <table style="width: 90%;">
                        <tr>
                            <td style="border: 2px solid #000; padding: 15px; text-align: center;">
                                <span style="font-size: 10pt;">ご請求金額</span><br>
                                <span style="font-size: 16pt; font-weight: bold;">¥' . number_format($data['total_amount_with_tax']) . '</span>
                                <span style="font-size: 10pt;">(税込)</span>
                            </td>
                        </tr>
                    </table>
                    <div style="margin-top: 5px; font-size: 9pt;">
                        ※内経費:¥' . number_format($data['travel_expense_amount']) . '
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <!-- 振込先情報 -->
                    <table style="border: 1px solid #000; background-color: #fafafa; width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 20px 20px 20px 20px; font-size: 9pt; border: none;">
                                <div style="font-weight: bold; margin-bottom: 12px; font-size: 10pt;">お振込先</div>
                                <div style="line-height: 0.9; margin: 0; padding: 0;">銀行名: ' . h($data['bank_name']) . '</div>
                                <div style="line-height: 0.9; margin: 0; padding: 0;">支店名: ' . h($data['bank_branch_name']) . '</div>
                                <div style="line-height: 0.9; margin: 0; padding: 0;">口座種別: ' . $accountType . '</div>
                                <div style="line-height: 0.9; margin: 0; padding: 0;">口座番号: ' . h($data['bank_account_number']) . '</div>
                                <div style="line-height: 0.9; margin: 0; padding: 0;">口座名義: ' . h($data['bank_account_holder']) . '</div>
                                <div style="height: 10px;"></div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 5px; font-size: 11pt;">
            下記の通りご請求申し上げます。
        </div>';
        
        // 役務明細
        $html .= '
        <div class="section-title">役務明細</div>
        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 18%;">企業 - 拠点</th>
                    <th style="width: 12%;">日付</th>
                    <th style="width: 12%;">実働時間</th>
                    <th style="width: 15%;">役務種別</th>
                    <th style="width: 13%;">実施方法</th>
                    <th style="width: 30%;">役務内容</th>
                </tr>
            </thead>
            <tbody>';

        $serviceTypeMap = [
            'regular' => '定期訪問',
            'regular_extension' => '定期訪問延長',
            'emergency' => '臨時訪問',
            'document' => '書面相談',
            'remote_consultation' => '遠隔相談',
            'spot' => 'スポット',
            'other' => 'その他'
        ];

        $implementationMethodMap = [
            'onsite' => '訪問',
            'remote' => '臨時',
            'document' => '書面',
            'phone' => '遠隔'
        ];

        $visitTypeMap = [
            'visit' => '訪問',
            'online' => 'オンライン'
        ];

        // 企業 - 拠点の情報
        $companyBranch = h($data['company_name']) . ' - ' . h($data['branch_name']);
        
        // スポット契約かどうかを判定
        $isSpotContract = ($data['visit_frequency'] === 'spot');

        // service_breakdownから明細を生成
        if (isset($simulation['service_breakdown']) && is_array($simulation['service_breakdown'])) {
            foreach ($simulation['service_breakdown'] as $item) {
                if (isset($item['service_record']) && $item['service_record']) {
                    $record = $item['service_record'];
                    
                    // スポット契約の場合、スポット以外は表示しない
                    if ($isSpotContract && $item['billing_service_type'] !== 'spot') {
                        continue;
                    }
                    
                    $serviceDate = date('Y/m/d', strtotime($record['service_date']));
                    $serviceType = $serviceTypeMap[$item['billing_service_type']] ?? '';
                    
                    // 実施方法の判定
                    if (isset($record['visit_type'])) {
                        $implementationMethod = $visitTypeMap[$record['visit_type']] ?? '';
                    } elseif (isset($record['implementation_method'])) {
                        $implementationMethod = $implementationMethodMap[$record['implementation_method']] ?? '';
                    } else {
                        $implementationMethod = '-';
                    }
                    
                    // 役務時間の表示
                    if (in_array($item['billing_service_type'], ['regular', 'emergency', 'spot'])) {
                        // 定期訪問・臨時訪問・スポットは実働時間(original_hours)を表示
                        if (isset($item['original_hours']) && $item['original_hours'] > 0) {
                            $h = floor($item['original_hours']);
                            $m = round(($item['original_hours'] - $h) * 60);
                            $hours = $m == 0 ? $h . '時間' : $h . '時間' . $m . '分';
                        } else {
                            $hours = '-';
                        }
                    } elseif (in_array($item['billing_service_type'], ['document', 'remote_consultation', 'other'])) {
                        // 書面作成・遠隔相談・その他は回数表示
                        $hours = '1回';
                    } elseif ($item['billing_hours'] > 0) {
                        // その他は請求時間を表示
                        $h = floor($item['billing_hours']);
                        $m = round(($item['billing_hours'] - $h) * 60);
                        $hours = $m == 0 ? $h . '時間' : $h . '時間' . $m . '分';
                    } else {
                        $hours = '-';
                    }
                    
                    // 役務内容
                    $description = isset($record['description']) && trim($record['description']) !== '' 
                        ? h($record['description']) 
                        : '-';
                    
                    $html .= "
                    <tr>
                        <td style=\"width: 18%;\">{$companyBranch}</td>
                        <td class=\"text-center\" style=\"width: 12%;\">{$serviceDate}</td>
                        <td class=\"text-right\" style=\"width: 12%;\">{$hours}</td>
                        <td style=\"width: 15%;\">{$serviceType}</td>
                        <td class=\"text-center\" style=\"width: 13%;\">{$implementationMethod}</td>
                        <td style=\"width: 30%;\">{$description}</td>
                    </tr>";
                }
            }
        }

        $html .= '
            </tbody>
        </table>';
        
        // 交通費明細セクション
        if (isset($simulation['travel_expenses']) && is_array($simulation['travel_expenses']) && count($simulation['travel_expenses']) > 0) {
            $html .= '
        <div class="spacer"></div>
        <div class="section-title">交通費明細</div>
        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 18%;">日付</th>
                    <th style="width: 20%;">出発地</th>
                    <th style="width: 20%;">到着地</th>
                    <th style="width: 18%;">交通手段</th>
                    <th style="width: 12%;">往復区分</th>
                    <th style="width: 12%;">金額</th>
                </tr>
            </thead>
            <tbody>';
            
            // 交通手段のマッピング
            $transportTypeMap = [
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
            
            // 往復区分のマッピング
            $tripTypeMap = [
                'one_way' => '片道',
                'round_trip' => '往復'
            ];
            
            // 交通費を日付順にソート
            $travelExpenses = $simulation['travel_expenses'];
            usort($travelExpenses, function($a, $b) {
                return strcmp($a['service_date'], $b['service_date']);
            });
            
            foreach ($travelExpenses as $expense) {
                $serviceDate = date('Y/m/d', strtotime($expense['service_date']));
                $departurePoint = h($expense['departure_point']);
                $arrivalPoint = h($expense['arrival_point']);
                $transportType = $transportTypeMap[$expense['transport_type']] ?? h($expense['transport_type']);
                $tripType = $tripTypeMap[$expense['trip_type']] ?? h($expense['trip_type']);
                $amount = '¥' . number_format($expense['amount']);
                
                $html .= "
                <tr>
                    <td class=\"text-center\" style=\"width: 18%;\">{$serviceDate}</td>
                    <td style=\"width: 20%;\">{$departurePoint}</td>
                    <td style=\"width: 20%;\">{$arrivalPoint}</td>
                    <td class=\"text-center\" style=\"width: 18%;\">{$transportType}</td>
                    <td class=\"text-center\" style=\"width: 12%;\">{$tripType}</td>
                    <td class=\"text-right\" style=\"width: 12%;\">{$amount}</td>
                </tr>";
            }
            
            // 合計行
            $travelExpenseTotal = isset($simulation['travel_expense_total']) ? $simulation['travel_expense_total'] : 0;
            $html .= '
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="5" class="text-right" style="width: 88%;">交通費合計</td>
                    <td class="text-right" style="width: 12%;">¥' . number_format($travelExpenseTotal) . '</td>
                </tr>';
            
            $html .= '
            </tbody>
        </table>';
        }
        
        // 契約情報から税種別を取得
        $taxType = $data['tax_type'] ?? 'exclusive';
        $db = Database::getInstance()->getConnection();
        
        // invoice_settingsから消費税率を取得
        $settingsSql = "SELECT tax_rate FROM invoice_settings LIMIT 1";
        $stmt = $db->prepare($settingsSql);
        $stmt->execute();
        $settingsRow = $stmt->fetch();
        $taxRate = $settingsRow ? (float)$settingsRow['tax_rate'] : 0.10;
        $taxRatePercent = $taxRate * 100;
        
        // 課税対象金額を計算（交通費を除く）
        $taxableAmount = $data['total_amount'] - $data['travel_expense_amount'];
        
        // 請求セクション
        $html .= '
        <div class="spacer"></div>
        <div class="section-title">請求</div>
        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 40%;">項目</th>
                    <th style="width: 30%;">時間/回数</th>
                    <th style="width: 30%;">金額</th>
                </tr>
            </thead>
            <tbody>';
        
        // スポット契約かどうかを判定
        $isSpotContract = ($data['visit_frequency'] === 'spot');
        
        if ($isSpotContract) {
            // スポット契約の場合、スポットのみ表示
            $spotHours = $data['spot_hours'] > 0 ? $this->formatHoursMinutes($data['spot_hours']) : '0時間';
            $spotAmount = '¥' . number_format($data['spot_amount']);
            $html .= "
            <tr>
                <td style=\"width: 40%;\">スポット</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$spotHours}</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$spotAmount}</td>
            </tr>";
        } else {
            // スポット契約以外の場合、従来通りの表示
            
            // 定期訪問
            $regularHours = $data['regular_hours'] > 0 ? $this->formatHoursMinutes($data['regular_hours']) : '0時間';
            $regularAmount = '¥' . number_format($data['regular_amount']);
            $html .= "
            <tr>
                <td style=\"width: 40%;\">定期訪問</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$regularHours}</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$regularAmount}</td>
            </tr>";
        
            // 定期延長
            $extensionHours = $data['regular_extension_hours'] > 0 ? $this->formatHoursMinutes($data['regular_extension_hours']) : '0時間';
            $extensionAmount = '¥' . number_format($data['regular_extension_amount']);
            $html .= "
            <tr>
                <td style=\"width: 40%;\">定期延長</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$extensionHours}</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$extensionAmount}</td>
            </tr>";
        
            // 臨時訪問
            $emergencyHours = $data['emergency_hours'] > 0 ? $this->formatHoursMinutes($data['emergency_hours']) : '0時間';
            $emergencyAmount = '¥' . number_format($data['emergency_amount']);
            $html .= "
            <tr>
                <td style=\"width: 40%;\">臨時訪問</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$emergencyHours}</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$emergencyAmount}</td>
            </tr>";
        
            // 遠隔相談（use_remote_consultationが1の場合のみ表示）
            if ($data['use_remote_consultation'] == 1) {
                $remoteCount = $data['remote_consultation_count'] > 0 ? $data['remote_consultation_count'] . '回' : '0回';
                $remoteAmount = '¥' . number_format($data['remote_consultation_amount']);
                $html .= "
            <tr>
                <td style=\"width: 40%;\">遠隔相談</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$remoteCount}</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$remoteAmount}</td>
            </tr>";
            }
        
            // 書面作成（use_document_creationが1の場合のみ表示）
            if ($data['use_document_creation'] == 1) {
                $documentCount = $data['document_count'] > 0 ? $data['document_count'] . '回' : '0回';
                $documentAmount = '¥' . number_format($data['document_amount']);
                $html .= "
            <tr>
                <td style=\"width: 40%;\">書面作成</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$documentCount}</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$documentAmount}</td>
            </tr>";
            }
        }
        
        
        // その他役務（1件ずつ表示）
        if (isset($simulation['service_breakdown']) && is_array($simulation['service_breakdown'])) {
            foreach ($simulation['service_breakdown'] as $item) {
                if ($item['billing_service_type'] === 'other' && $item['billing_amount'] > 0) {
                    $record = $item['service_record'];
                    $serviceDate = date('n/j', strtotime($record['service_date']));
                    $otherAmount = '¥' . number_format($item['billing_amount']);
                    $html .= "
            <tr>
                <td style=\"width: 40%;\">その他（{$serviceDate}）</td>
                <td class=\"text-right\" style=\"width: 30%;\">1回</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$otherAmount}</td>
            </tr>";
                }
            }
        }
        
        // 専属産業医登録料
        $exclusiveRegistrationFee = !empty($simulation['exclusive_registration_fee']) ? (float)$simulation['exclusive_registration_fee'] : 0;
        if ($exclusiveRegistrationFee > 0) {
            $exclusiveFeeAmount = '¥' . number_format($exclusiveRegistrationFee);
            $html .= "
            <tr>
                <td style=\"width: 40%;\">専属産業医登録料</td>
                <td class=\"text-right\" style=\"width: 30%;\">1回</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$exclusiveFeeAmount}</td>
            </tr>";
        }
        
        // 交通費
        $travelAmount = '¥' . number_format($data['travel_expense_amount']);
        $html .= "
            <tr>
                <td style=\"width: 40%;\">交通費</td>
                <td class=\"text-right\" style=\"width: 30%;\">-</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$travelAmount}</td>
            </tr>";
        
        // 外税の場合のみ消費税行を表示
        if ($taxType === 'exclusive') {
            $taxAmount = '¥' . number_format($data['tax_amount']);
            $html .= "
            <tr>
                <td style=\"width: 40%;\">消費税 ({$taxRatePercent}%)</td>
                <td class=\"text-right\" style=\"width: 30%;\">-</td>
                <td class=\"text-right\" style=\"width: 30%;\">{$taxAmount}</td>
            </tr>";
            
            // 外税の場合、課税対象額に消費税を含める
            $taxableAmountDisplay = $taxableAmount + $data['tax_amount'];
        } else {
            // 内税の場合、課税対象額はそのまま
            $taxableAmountDisplay = $taxableAmount;
        }
        
        // 合計
        $html .= '
                <tr style="font-weight: bold; font-size: 11pt; background-color: #e0e0e0;">
                    <td colspan="2" class="text-right" style="width: 70%;">合計</td>
                    <td class="text-right" style="width: 30%;">¥' . number_format($data['total_amount_with_tax']) . '</td>
                </tr>';
        
        // 内訳
        $html .= '
                <tr style="background-color: #f5f5f5;">
                    <td colspan="3" style="padding: 8px;">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="width: 50%; text-align: right; border: none; padding: 2px;">
                                    <span style="font-size: 9pt; color: #666;">(' . $taxRatePercent . '%対象</span>
                                </td>
                                <td style="width: 50%; text-align: right; border: none; padding: 2px;">
                                    <strong style="font-size: 9pt;">¥' . number_format($taxableAmountDisplay) . ')</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 50%; text-align: right; border: none; padding: 2px;">
                                    <span style="font-size: 9pt; color: #666;">(消費税対象外</span>
                                </td>
                                <td style="width: 50%; text-align: right; border: none; padding: 2px;">
                                    <strong style="font-size: 9pt;">¥' . number_format($data['travel_expense_amount']) . ')</strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>';
        
        
        return $html;
    }

    /**
     * 時間を「N時間M分」形式にフォーマット
     */
    private function formatHoursMinutes($hours)
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        
        if ($m == 0) {
            return $h . '時間';
        } else {
            return $h . '時間' . $m . '分';
        }
    }
    
    /**
     * PDF保存
     */
    private function savePDF($pdf, $closingRecordId, $invoiceData)
    {
        // 保存ディレクトリ
        $saveDir = __DIR__ . '/../../storage/invoices/' . date('Y', strtotime($invoiceData['closing_period']));
        
        if (!is_dir($saveDir)) {
            mkdir($saveDir, 0755, true);
        }
        
        // 請求期間をyyyy年mm月形式に変換
        $periodFormatted = date('Y年m月', strtotime($invoiceData['closing_period'] . '-01'));
        
        // 契約ID
        $contractId = $invoiceData['contract_id'];
        
        // 企業名と拠点名をファイル名用にサニタイズ
        $companyName = $this->sanitizeFilename($invoiceData['company_name']);
        $branchName = $this->sanitizeFilename($invoiceData['branch_name']);
        
        // ファイル名生成: 請求書_yyyy年mm月_契約ID_企業名_拠点名.pdf
        $filename = sprintf(
            '請求書_%s_%s_%s_%s.pdf',
            $periodFormatted,
            $contractId,
            $companyName,
            $branchName
        );
        
        $filepath = $saveDir . '/' . $filename;
        
        // PDF保存
        $pdf->Output($filepath, 'F');
        
        // DBにファイルパスを記録
        $this->updateInvoicePath($closingRecordId, $filepath);
        
        return $filepath;
    }
    
    /**
     * ファイル名用の文字列をサニタイズ
     */
    private function sanitizeFilename($string)
    {
        // ファイル名に使用できない文字を除去・置換
        $string = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $string);
        // 空白を削除
        $string = str_replace(' ', '', $string);
        // 連続するアンダースコアを1つに
        $string = preg_replace('/_+/', '_', $string);
        // 前後のアンダースコアを削除
        $string = trim($string, '_');
        
        return $string;
    }
    
    /**
     * 請求書パスをDBに記録
     */
    private function updateInvoicePath($closingRecordId, $filepath)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "UPDATE monthly_closing_records 
                SET invoice_pdf_path = :invoice_pdf_path,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id' => $closingRecordId,
            'invoice_pdf_path' => $filepath
        ]);
    }
}

// HTMLエスケープ用ヘルパー関数
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}