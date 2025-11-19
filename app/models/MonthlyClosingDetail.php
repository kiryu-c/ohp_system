<?php
// app/models/MonthlyClosingDetail.php - 月次締め処理明細モデル

class MonthlyClosingDetail
{
    /**
     * 締め処理記録の明細を一括作成
     * 
     * @param int $closingRecordId 締め処理記録ID
     * @param array $simulationData シミュレーションデータ
     * @param array $contract 契約情報
     * @param PDO $db データベース接続
     * @return bool 成功したかどうか
     */
    public static function createDetails($closingRecordId, $simulationData, $contract, $db)
    {
        // 既存の明細を削除（再作成の場合）
        self::deleteByClosingRecordId($closingRecordId, $db);
        
        $lineNumber = 1;
        $details = [];
        
        // summaryデータの取得
        $summary = $simulationData['summary'] ?? [];
        
        // 契約情報から単価を取得
        $regularVisitRate = (float)($contract['regular_visit_rate'] ?? 0);
        $regularExtensionRate = (float)($contract['regular_extension_rate'] ?? 0);
        $emergencyVisitRate = (float)($contract['emergency_visit_rate'] ?? 0);
        $documentConsultationRate = (float)($contract['document_consultation_rate'] ?? 0);
        $spotRate = (float)($contract['spot_rate'] ?? 0);
        
        // 15分単位の料金を1時間単位に変換
        $regularExtensionHourlyRate = $regularExtensionRate * 4; // 15分×4=1時間
        $emergencyHourlyRate = $emergencyVisitRate * 4; // 15分×4=1時間
        $spotHourlyRate = $spotRate * 4; // 15分×4=1時間
        
        // 1. 定期訪問
        if (!empty($summary['regular_hours']) && $summary['regular_hours'] > 0) {
            $details[] = [
                'line_number' => $lineNumber++,
                'item_category' => 'service',
                'service_type' => 'regular',
                'item_name' => '定期訪問',
                'unit_type' => 'hours',
                'quantity' => $summary['regular_hours'],
                'unit_price' => $regularVisitRate,
                'amount' => $summary['regular_amount'],
                'description' => self::buildRegularDescription($simulationData),
                'related_service_record_ids' => self::extractServiceRecordIds($simulationData, 'regular')
            ];
        }
        
        // 2. 定期延長
        if (!empty($summary['regular_extension_hours']) && $summary['regular_extension_hours'] > 0) {
            $details[] = [
                'line_number' => $lineNumber++,
                'item_category' => 'service',
                'service_type' => 'regular_extension',
                'item_name' => '定期延長',
                'unit_type' => 'hours',
                'quantity' => $summary['regular_extension_hours'],
                'unit_price' => $regularExtensionHourlyRate,
                'amount' => $summary['regular_extension_amount'],
                'related_service_record_ids' => self::extractServiceRecordIds($simulationData, 'regular_extension')
            ];
        }
        
        // 3. 臨時訪問
        if (!empty($summary['emergency_hours']) && $summary['emergency_hours'] > 0) {
            $details[] = [
                'line_number' => $lineNumber++,
                'item_category' => 'service',
                'service_type' => 'emergency',
                'item_name' => '臨時訪問',
                'unit_type' => 'hours',
                'quantity' => $summary['emergency_hours'],
                'unit_price' => $emergencyHourlyRate,
                'amount' => $summary['emergency_amount'],
                'related_service_record_ids' => self::extractServiceRecordIds($simulationData, 'emergency')
            ];
        }
        
        // 4. 書面作成
        if (!empty($summary['document_count']) && $summary['document_count'] > 0) {
            $details[] = [
                'line_number' => $lineNumber++,
                'item_category' => 'service',
                'service_type' => 'document',
                'item_name' => '書面作成',
                'unit_type' => 'times',
                'quantity' => $summary['document_count'],
                'unit_price' => $documentConsultationRate,
                'amount' => $summary['document_amount'],
                'related_service_record_ids' => self::extractServiceRecordIds($simulationData, 'document')
            ];
        }
        
        // 5. 遠隔相談
        if (!empty($summary['remote_consultation_count']) && $summary['remote_consultation_count'] > 0) {
            $details[] = [
                'line_number' => $lineNumber++,
                'item_category' => 'service',
                'service_type' => 'remote_consultation',
                'item_name' => '遠隔相談',
                'unit_type' => 'times',
                'quantity' => $summary['remote_consultation_count'],
                'unit_price' => $documentConsultationRate,
                'amount' => $summary['remote_consultation_amount'],
                'related_service_record_ids' => self::extractServiceRecordIds($simulationData, 'remote_consultation')
            ];
        }
        
        // 6. スポット
        if (!empty($summary['spot_hours']) && $summary['spot_hours'] > 0) {
            $details[] = [
                'line_number' => $lineNumber++,
                'item_category' => 'service',
                'service_type' => 'spot',
                'item_name' => 'スポット',
                'unit_type' => 'hours',
                'quantity' => $summary['spot_hours'],
                'unit_price' => $spotHourlyRate,
                'amount' => $summary['spot_amount'],
                'related_service_record_ids' => self::extractServiceRecordIds($simulationData, 'spot')
            ];
        }
        
        // 7. その他（1件ずつ個別に明細行として登録）
        if (!empty($simulationData['service_breakdown'])) {
            foreach ($simulationData['service_breakdown'] as $item) {
                if ($item['billing_service_type'] === 'other' && $item['billing_amount'] > 0) {
                    $record = $item['service_record'];
                    $serviceDate = date('n/j', strtotime($record['service_date']));
                    $description = !empty($record['description']) ? $record['description'] : null;
                    
                    $details[] = [
                        'line_number' => $lineNumber++,
                        'item_category' => 'service',
                        'service_type' => 'other',
                        'item_name' => 'その他（' . $serviceDate . '）',
                        'unit_type' => 'amount',
                        'quantity' => 1,
                        'unit_price' => $item['billing_amount'],
                        'amount' => $item['billing_amount'],
                        'description' => $description,
                        'service_record_id' => $record['id'],
                        'related_service_record_ids' => (string)$record['id']
                    ];
                }
            }
        }
        
        
        // 8. 専属産業医登録料
        if (!empty($simulationData['exclusive_registration_fee']) && $simulationData['exclusive_registration_fee'] > 0) {
            $details[] = [
                'line_number' => $lineNumber++,
                'item_category' => 'service',
                'service_type' => null,
                'item_name' => '専属産業医登録料',
                'unit_type' => 'times',
                'quantity' => 1,
                'unit_price' => $simulationData['exclusive_registration_fee'],
                'amount' => $simulationData['exclusive_registration_fee'],
                'description' => null
            ];
        }
        
        // 9. 交通費（1件ずつ明細行として登録）
        if (!empty($simulationData['travel_expenses'])) {
            foreach ($simulationData['travel_expenses'] as $travelExpense) {
                $description = self::buildTravelExpenseDescription($travelExpense);
                
                $details[] = [
                    'line_number' => $lineNumber++,
                    'item_category' => 'travel_expense',
                    'service_type' => null,
                    'item_name' => '交通費',
                    'unit_type' => 'amount',
                    'quantity' => 1,
                    'unit_price' => null,
                    'amount' => $travelExpense['amount'],
                    'description' => $description,
                    'related_travel_expense_ids' => (string)$travelExpense['id']
                ];
            }
        }
        
        // 10. 小計（税抜）
        $details[] = [
            'line_number' => $lineNumber++,
            'item_category' => 'subtotal',
            'service_type' => null,
            'item_name' => '小計（税抜）',
            'unit_type' => null,
            'quantity' => null,
            'unit_price' => null,
            'amount' => $simulationData['total_amount'] ?? 0,
            'tax_rate' => null
        ];
        
        // 11. 消費税
        $taxAmount = $simulationData['tax_amount'] ?? 0;
        if (!empty($taxAmount) && $taxAmount > 0) {
            $taxRate = $simulationData['tax_rate'] ?? 0.10;
            $details[] = [
                'line_number' => $lineNumber++,
                'item_category' => 'tax',
                'service_type' => null,
                'item_name' => '消費税（' . ($taxRate * 100) . '%）',
                'unit_type' => null,
                'quantity' => null,
                'unit_price' => null,
                'amount' => $taxAmount,
                'tax_rate' => $taxRate
            ];
        }
        
        // 12. 合計（税込）
        $details[] = [
            'line_number' => $lineNumber++,
            'item_category' => 'total',
            'service_type' => null,
            'item_name' => '合計（税込）',
            'unit_type' => null,
            'quantity' => null,
            'unit_price' => null,
            'amount' => $simulationData['total_with_tax'] ?? 0,
            'tax_rate' => null
        ];
        
        // データベースに一括挿入
        return self::insertBatch($closingRecordId, $details, $db);
    }
    
    /**
     * 定期訪問の説明文を構築
     */
    private static function buildRegularDescription($simulationData)
    {
        $billingMethod = $simulationData['regular_billing_method'] ?? 'contract_hours';
        $contractHours = $simulationData['contract_hours'] ?? 0;
        $actualHours = $simulationData['actual_regular_hours'] ?? 0;
        
        if ($billingMethod === 'contract_hours') {
            return "";
        } else {
            return "実働時間：{$actualHours}時間で請求";
        }
    }
    
    /**
     * 交通費の説明文を構築
     * 
     * @param array $travelExpense 交通費データ
     * @return string 説明文（例：「電車 五反田駅 - 東京駅」）
     */
    private static function buildTravelExpenseDescription($travelExpense)
    {
        // 交通手段の日本語変換
        $transportTypeMap = [
            'train' => '電車',
            'bus' => 'バス',
            'taxi' => 'タクシー',
            'gasoline' => 'ガソリン代',
            'highway_toll' => '有料道路利用料',
            'parking' => '駐車場代',
            'rental_car' => 'レンタカー',
            'airplane' => '航空機',
            'other' => 'その他'
        ];
        
        $transportType = $travelExpense['transport_type'] ?? 'other';
        $transportTypeName = $transportTypeMap[$transportType] ?? 'その他';
        
        $departure = $travelExpense['departure_point'] ?? '';
        $arrival = $travelExpense['arrival_point'] ?? '';
        
        // 出発地・到着地がある場合
        if (!empty($departure) && !empty($arrival)) {
            $description = "{$transportTypeName} {$departure} - {$arrival}";
        } elseif (!empty($departure)) {
            $description = "{$transportTypeName} {$departure}";
        } elseif (!empty($arrival)) {
            $description = "{$transportTypeName} {$arrival}";
        } else {
            $description = $transportTypeName;
        }
        
        // 往復・片道の情報を追加
        if (!empty($travelExpense['trip_type'])) {
            $tripTypeMap = [
                'round_trip' => '往復',
                'one_way' => '片道'
            ];
            $tripType = $tripTypeMap[$travelExpense['trip_type']] ?? '';
            if ($tripType) {
                $description .= " ({$tripType})";
            }
        }
        
        // メモがある場合は追加
        if (!empty($travelExpense['memo'])) {
            $memo = mb_substr($travelExpense['memo'], 0, 50); // 50文字まで
            $description .= " - {$memo}";
        }
        
        return $description;
    }
    
    /**
     * シミュレーションデータから役務記録IDを抽出
     */
    private static function extractServiceRecordIds($simulationData, $serviceType)
    {
        if (empty($simulationData['service_breakdown'])) {
            return null;
        }
        
        $ids = [];
        foreach ($simulationData['service_breakdown'] as $item) {
            if (isset($item['service_record']) && 
                isset($item['billing_service_type']) && 
                $item['billing_service_type'] === $serviceType) {
                $ids[] = $item['service_record']['id'];
            }
        }
        
        return !empty($ids) ? implode(',', $ids) : null;
    }
    
    /**
     * シミュレーションデータから交通費IDを抽出
     */
    private static function extractTravelExpenseIds($simulationData)
    {
        if (empty($simulationData['travel_expenses'])) {
            return null;
        }
        
        $ids = array_column($simulationData['travel_expenses'], 'id');
        return !empty($ids) ? implode(',', $ids) : null;
    }
    
    /**
     * 明細を一括挿入
     */
    private static function insertBatch($closingRecordId, $details, $db)
    {
        if (empty($details)) {
            return true;
        }
        
        $sql = "INSERT INTO monthly_closing_details (
                    closing_record_id, service_record_id, line_number, item_category, service_type,
                    item_name, unit_type, quantity, unit_price, amount,
                    tax_rate, description, related_service_record_ids, related_travel_expense_ids
                ) VALUES (
                    :closing_record_id, :service_record_id, :line_number, :item_category, :service_type,
                    :item_name, :unit_type, :quantity, :unit_price, :amount,
                    :tax_rate, :description, :related_service_record_ids, :related_travel_expense_ids
                )";
        
        $stmt = $db->prepare($sql);
        
        foreach ($details as $detail) {
            $result = $stmt->execute([
                'closing_record_id' => $closingRecordId,
                'service_record_id' => $detail['service_record_id'] ?? null,
                'line_number' => $detail['line_number'],
                'item_category' => $detail['item_category'],
                'service_type' => $detail['service_type'] ?? null,
                'item_name' => $detail['item_name'],
                'unit_type' => $detail['unit_type'] ?? null,
                'quantity' => $detail['quantity'] ?? null,
                'unit_price' => $detail['unit_price'] ?? null,
                'amount' => $detail['amount'],
                'tax_rate' => $detail['tax_rate'] ?? null,
                'description' => $detail['description'] ?? null,
                'related_service_record_ids' => $detail['related_service_record_ids'] ?? null,
                'related_travel_expense_ids' => $detail['related_travel_expense_ids'] ?? null
            ]);
            
            if (!$result) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 締め処理記録IDで明細を取得
     */
    public static function getByClosingRecordId($closingRecordId, $db)
    {
        $sql = "SELECT * FROM monthly_closing_details 
                WHERE closing_record_id = :closing_record_id 
                ORDER BY line_number ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['closing_record_id' => $closingRecordId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 締め処理記録IDで明細を削除
     */
    public static function deleteByClosingRecordId($closingRecordId, $db)
    {
        $sql = "DELETE FROM monthly_closing_details WHERE closing_record_id = :closing_record_id";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute(['closing_record_id' => $closingRecordId]);
    }
    
    /**
     * CSV形式でエクスポート
     * 
     * @param int $closingRecordId 締め処理記録ID
     * @param PDO $db データベース接続
     * @return array CSVデータ（ヘッダー行 + データ行）
     */
    public static function exportToCsv($closingRecordId, $db)
    {
        $details = self::getByClosingRecordId($closingRecordId, $db);
        
        if (empty($details)) {
            return [];
        }
        
        // CSVヘッダー
        $csvData = [
            ['行番号', '項目カテゴリ', 'サービス種別', '項目名', '単位種別', '数量', '単価', '金額', '消費税率', '説明']
        ];
        
        // データ行
        foreach ($details as $detail) {
            $csvData[] = [
                $detail['line_number'],
                self::translateItemCategory($detail['item_category']),
                $detail['service_type'] ? self::translateServiceType($detail['service_type']) : '',
                $detail['item_name'],
                $detail['unit_type'] ? self::translateUnitType($detail['unit_type']) : '',
                $detail['quantity'] !== null ? number_format($detail['quantity'], 2) : '',
                $detail['unit_price'] !== null ? number_format($detail['unit_price']) : '',
                number_format($detail['amount']),
                $detail['tax_rate'] !== null ? ($detail['tax_rate'] * 100) . '%' : '',
                $detail['description'] ?? ''
            ];
        }
        
        return $csvData;
    }
    
    /**
     * 項目カテゴリの日本語変換
     */
    private static function translateItemCategory($category)
    {
        $translations = [
            'service' => '役務',
            'travel_expense' => '交通費',
            'subtotal' => '小計',
            'tax' => '消費税',
            'total' => '合計'
        ];
        
        return $translations[$category] ?? $category;
    }
    
    /**
     * サービス種別の日本語変換
     */
    private static function translateServiceType($type)
    {
        $translations = [
            'regular' => '定期訪問',
            'regular_extension' => '定期延長',
            'emergency' => '臨時訪問',
            'document' => '書面作成',
            'remote_consultation' => '遠隔相談',
            'spot' => 'スポット'
        ];
        
        return $translations[$type] ?? $type;
    }
    
    /**
     * 単位種別の日本語変換
     */
    private static function translateUnitType($type)
    {
        $translations = [
            'hours' => '時間',
            'times' => '回',
            'amount' => '金額'
        ];
        
        return $translations[$type] ?? $type;
    }
}