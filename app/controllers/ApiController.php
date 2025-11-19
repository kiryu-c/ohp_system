<?php
// app/controllers/ApiController.php - 毎週契約の月間時間計算対応版
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Branch.php';
require_once __DIR__ . '/../models/Contract.php';

class ApiController extends BaseController {
    
    public function getBranchesByCompany($companyId) {
        // 管理者権限をチェック
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        // URLパラメータまたは引数から企業IDを取得
        $companyId = $companyId ?? (int)($_GET['company_id'] ?? 0);

        if ($companyId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '無効な企業IDです'
            ]);
            return;
        }
        
        $branchModel = new Branch();

        try {
            $branches = $branchModel->findByCompany($companyId);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'branches' => $branches  // dataではなくbranchesに統一
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log('Branch fetch error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => '拠点情報の取得に失敗しました',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 企業の拠点一覧をJSON形式で返す（ユーザー編集用）
     */
    public function getBranchesForUserEdit() {
        // 管理者権限をチェック
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $companyId = (int)($_GET['company_id'] ?? 0);
        
        if ($companyId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '無効な企業IDです'
            ]);
            return;
        }
        
        $branchModel = new Branch();
        
        try {
            $branches = $branchModel->findByCompany($companyId);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'branches' => $branches
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log('Branch fetch error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => '拠点情報の取得に失敗しました',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function checkDuplicateContract() {
        Session::requireLogin();
        
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $doctorId = $input['doctor_id'] ?? 0;
        $branchId = $input['branch_id'] ?? 0;
        
        $contractModel = new Contract();
        $exists = $contractModel->checkDuplicateContract($doctorId, $branchId);
        
        echo json_encode([
            'success' => true,
            'exists' => $exists
        ]);
        exit;
    }

    /**
     * 契約の月間使用状況を取得（毎週契約の祝日考慮対応版）
     */
    public function getContractMonthlyUsage($contractId) {
        // 認証チェック
        Session::requireLogin();
        
        header('Content-Type: application/json');
        
        try {
            $year = (int)($_GET['year'] ?? date('Y'));
            $month = (int)($_GET['month'] ?? date('n'));
            $excludeRecordId = (int)($_GET['exclude_record_id'] ?? 0); // 編集時に除外する記録ID
            $userId = Session::get('user_id');
            $userType = Session::get('user_type');
            
            // 契約へのアクセス権限チェック
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $contractSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                        FROM contracts co
                        JOIN companies c ON co.company_id = c.id
                        JOIN branches b ON co.branch_id = b.id
                        WHERE co.id = :contract_id";
            
            $params = ['contract_id' => $contractId];
            
            // ユーザータイプに応じたアクセス権限チェック
            if ($userType === 'doctor') {
                $contractSql .= " AND co.doctor_id = :user_id";
                $params['user_id'] = $userId;
            } elseif ($userType === 'company') {
                $contractSql .= " AND co.company_id = :company_id";
                $params['company_id'] = Session::get('company_id');
            }
            // 管理者の場合はアクセス制限なし
            
            $stmt = $db->prepare($contractSql);
            $stmt->execute($params);
            $contract = $stmt->fetch();
            
            if (!$contract) {
                echo json_encode([
                    'success' => false,
                    'error' => '契約が見つかりません'
                ]);
                return;
            }
            
            // 月間使用状況を取得（除外する記録がある場合は除外）
            $usageSql = "SELECT 
                            SUM(CASE WHEN service_type = 'regular' OR service_type IS NULL THEN service_hours ELSE 0 END) as regular_hours,
                            SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                            SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                            SUM(CASE WHEN service_type = 'spot' THEN service_hours ELSE 0 END) as spot_hours,
                            SUM(service_hours) as total_hours,
                            COUNT(*) as record_count,
                            COUNT(CASE WHEN service_type = 'regular' OR service_type IS NULL THEN 1 END) as regular_count,
                            COUNT(CASE WHEN service_type = 'emergency' THEN 1 END) as emergency_count,
                            COUNT(CASE WHEN service_type = 'spot' THEN 1 END) as spot_count,
                            COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                            COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
                        FROM service_records 
                        WHERE contract_id = :contract_id 
                        AND YEAR(service_date) = :year 
                        AND MONTH(service_date) = :month";
            
            $usageParams = [
                'contract_id' => $contractId,
                'year' => $year,
                'month' => $month
            ];
            
            // 編集時は指定された記録を除外
            if ($excludeRecordId > 0) {
                $usageSql .= " AND id != :exclude_record_id";
                $usageParams['exclude_record_id'] = $excludeRecordId;
            }
            
            $stmt = $db->prepare($usageSql);
            $stmt->execute($usageParams);
            
            $usage = $stmt->fetch();
            
            // 除外された記録の情報も取得（デバッグ用）
            $excludedRecord = null;
            if ($excludeRecordId > 0) {
                $excludedSql = "SELECT service_hours, service_type FROM service_records WHERE id = :record_id";
                $stmt = $db->prepare($excludedSql);
                $stmt->execute(['record_id' => $excludeRecordId]);
                $excludedRecord = $stmt->fetch();
            }
            
            // データを整形
            $regularHours = (float)($usage['regular_hours'] ?? 0);
            $emergencyHours = (float)($usage['emergency_hours'] ?? 0);
            $extensionHours = (float)($usage['extension_hours'] ?? 0);
            $spotHours = (float)($usage['spot_hours'] ?? 0);
            $totalHours = (float)($usage['total_hours'] ?? 0);
            
            // 訪問頻度に応じた月間上限の計算
            $regularLimit = $this->calculateMonthlyLimit($contract, $year, $month, $db);
            
            $remainingHours = max(0, $regularLimit - $regularHours);
            $usagePercentage = $regularLimit > 0 ? ($regularHours / $regularLimit) * 100 : 0;
            
            $response = [
                'success' => true,
                'contract_id' => (int)$contractId,
                'year' => $year,
                'month' => $month,
                'excluded_record_id' => $excludeRecordId,
                'contract_info' => [
                    'company_name' => $contract['company_name'],
                    'branch_name' => $contract['branch_name'],
                    'regular_visit_hours' => (float)$contract['regular_visit_hours'],
                    'visit_frequency' => $contract['visit_frequency'] ?? 'monthly'
                ],
                'regular_hours' => $regularHours,
                'regular_count' => (int)($usage['regular_count'] ?? 0),
                'emergency_hours' => $emergencyHours,
                'emergency_count' => (int)($usage['emergency_count'] ?? 0),
                'extension_hours' => $extensionHours,
                'spot_hours' => $spotHours,
                'spot_count' => (int)($usage['spot_count'] ?? 0),
                'document_count' => (int)($usage['document_count'] ?? 0),
                'remote_consultation_count' => (int)($usage['remote_consultation_count'] ?? 0),
                'total_hours' => $totalHours,
                'regular_limit' => $regularLimit,
                'remaining_hours' => $remainingHours,
                'usage_percentage' => round($usagePercentage, 1),
                'record_count' => (int)($usage['record_count'] ?? 0),
                'status_counts' => [
                    'pending' => (int)($usage['pending_count'] ?? 0),
                    'approved' => (int)($usage['approved_count'] ?? 0),
                    'rejected' => (int)($usage['rejected_count'] ?? 0)
                ]
            ];
            
            // デバッグ情報を追加
            if ($excludedRecord) {
                $response['debug_info'] = [
                    'excluded_record' => $excludedRecord,
                    'total_with_excluded' => $regularHours + (($excludedRecord['service_type'] ?? 'regular') === 'regular' ? (float)$excludedRecord['service_hours'] : 0)
                ];
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log('Monthly usage API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => '月間使用状況の取得に失敗しました',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 訪問頻度に応じた月間上限時間を計算（祝日考慮版）
     */
    private function calculateMonthlyLimit($contract, $year, $month, $db) {
        $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
        $regularVisitHours = (float)$contract['regular_visit_hours'];
        
        switch ($visitFrequency) {
            case 'monthly':
                // 毎月契約：契約時間がそのまま月間上限
                return $regularVisitHours;
                
            case 'bimonthly':
                // 隔月契約：訪問月かどうかで判定
                $isVisitMonth = is_visit_month($contract, $year, $month);
                return $isVisitMonth ? $regularVisitHours : 0;
                
            case 'weekly':
                // 毎週契約：祝日を考慮した訪問回数 × 週間時間
                return $this->calculateWeeklyMonthlyHours($contract['id'], $regularVisitHours, $year, $month, $db);
                
            default:
                return $regularVisitHours;
        }
    }

    /**
     * 毎週契約の月間時間を計算（祝日考慮版）
     */
    private function calculateWeeklyMonthlyHours($contractId, $weeklyHours, $year, $month, $db) {
        // 修正: helpers.phpの新しい関数を使用
        return calculate_weekly_monthly_hours_with_contract_settings($contractId, $weeklyHours, $year, $month, $db);
    }

    /**
     * 月間使用状況の詳細取得（デバッグ用）
     */
    public function getContractMonthlyUsageDetail($contractId) {
        Session::requireLogin();
        
        header('Content-Type: application/json');
        
        try {
            $year = (int)($_GET['year'] ?? date('Y'));
            $month = (int)($_GET['month'] ?? date('n'));
            $userId = Session::get('user_id');
            $userType = Session::get('user_type');
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 権限チェック
            $contractSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                        FROM contracts co
                        JOIN companies c ON co.company_id = c.id
                        JOIN branches b ON co.branch_id = b.id
                        WHERE co.id = :contract_id";
            
            $params = ['contract_id' => $contractId];
            
            if ($userType === 'doctor') {
                $contractSql .= " AND co.doctor_id = :user_id";
                $params['user_id'] = $userId;
            } elseif ($userType === 'company') {
                $contractSql .= " AND co.company_id = :company_id";
                $params['company_id'] = Session::get('company_id');
            }
            
            $stmt = $db->prepare($contractSql);
            $stmt->execute($params);
            $contract = $stmt->fetch();
            
            if (!$contract) {
                echo json_encode(['success' => false, 'error' => '契約が見つかりません']);
                return;
            }
            
            // 詳細な役務記録を取得
            $recordsSql = "SELECT 
                            service_date,
                            start_time,
                            end_time,
                            service_hours,
                            service_type,
                            status,
                            description,
                            is_auto_split,
                            created_at
                        FROM service_records 
                        WHERE contract_id = :contract_id 
                        AND YEAR(service_date) = :year 
                        AND MONTH(service_date) = :month
                        ORDER BY service_date DESC, start_time DESC";
            
            $stmt = $db->prepare($recordsSql);
            $stmt->execute([
                'contract_id' => $contractId,
                'year' => $year,
                'month' => $month
            ]);
            
            $records = $stmt->fetchAll();
            
            // 集計データを計算
            $summary = [
                'total_records' => count($records),
                'regular_hours' => 0,
                'emergency_hours' => 0,
                'extension_hours' => 0,
                'auto_split_count' => 0
            ];
            
            foreach ($records as $record) {
                $serviceType = $record['service_type'] ?? 'regular';
                $hours = (float)$record['service_hours'];
                
                if ($serviceType === 'regular') {
                    $summary['regular_hours'] += $hours;
                } elseif ($serviceType === 'emergency') {
                    $summary['emergency_hours'] += $hours;
                } elseif ($serviceType === 'extension') {
                    $summary['extension_hours'] += $hours;
                }
                
                if ($record['is_auto_split']) {
                    $summary['auto_split_count']++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'contract_info' => $contract,
                'summary' => $summary,
                'records' => $records,
                'debug_info' => [
                    'query_params' => [
                        'contract_id' => $contractId,
                        'year' => $year,
                        'month' => $month
                    ],
                    'user_info' => [
                        'user_id' => $userId,
                        'user_type' => $userType,
                        'company_id' => Session::get('company_id')
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Monthly usage detail API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => '詳細取得に失敗しました',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 隔月契約の訪問月判定APIエンドポイント
     */
    public function checkVisitMonth() {
        Session::requireLogin();
        
        header('Content-Type: application/json');
        
        try {
            $contractId = (int)($_POST['contract_id'] ?? 0);
            $serviceDate = $_POST['service_date'] ?? '';
            
            if (!$contractId || !$serviceDate) {
                echo json_encode([
                    'success' => false,
                    'error' => 'パラメータが不足しています'
                ]);
                return;
            }
            
            // CSRFトークンの検証
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode([
                    'success' => false,
                    'error' => 'CSRFトークンが無効です'
                ]);
                return;
            }
            
            // 契約情報を取得
            require_once __DIR__ . '/../models/Contract.php';
            $contractModel = new Contract();
            $contract = $contractModel->findById($contractId);
            
            if (!$contract) {
                echo json_encode([
                    'success' => false,
                    'error' => '契約が見つかりません'
                ]);
                return;
            }
            
            // アクセス権限チェック
            $userId = Session::get('user_id');
            $userType = Session::get('user_type');
            
            if ($userType === 'doctor' && $contract['doctor_id'] != $userId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'アクセス権限がありません'
                ]);
                return;
            }
            
            if ($userType === 'company' && $contract['company_id'] != Session::get('company_id')) {
                echo json_encode([
                    'success' => false,
                    'error' => 'アクセス権限がありません'
                ]);
                return;
            }
            
            // 訪問月判定
            $serviceDateObj = new DateTime($serviceDate);
            $year = (int)$serviceDateObj->format('Y');
            $month = (int)$serviceDateObj->format('n');
            
            $isVisitMonth = is_visit_month($contract, $year, $month);
            
            echo json_encode([
                'success' => true,
                'is_visit_month' => $isVisitMonth,
                'contract_id' => $contractId,
                'service_date' => $serviceDate,
                'visit_frequency' => $contract['visit_frequency'],
                'debug_info' => [
                    'year' => $year,
                    'month' => $month,
                    'contract_start_date' => $contract['start_date']
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Visit month check API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました'
            ]);
        }
    }

    /**
     * 累積時間チェックAPI(延長理由必須判定用)
     * 
     * 指定した役務日時点での累積時間を計算し、延長判定を行う
     * - 当月の役務を日付順に並べ、指定日時より前の役務のみを累積
     * - 累積時間 + 今回の役務時間が月の訪問時間を超える場合に延長フラグを立てる
     * - 1回の役務時間が契約時間を超える場合も延長フラグを立てる
     * 
     * @return void (JSON出力)
     */
    public function checkCumulativeHours() {
        Session::requireLogin();
        
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $contractId = (int)($input['contract_id'] ?? 0);
            $serviceDate = $input['service_date'] ?? '';
            $serviceHours = (float)($input['service_hours'] ?? 0);
            $excludeRecordId = (int)($input['exclude_record_id'] ?? 0); // 編集時に除外する記録ID
            
            if ($contractId <= 0 || empty($serviceDate)) {
                echo json_encode([
                    'success' => false,
                    'error' => '必須パラメータが不足しています'
                ]);
                return;
            }
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 契約情報を取得
            $contractSql = "SELECT * FROM contracts WHERE id = :contract_id";
            $stmt = $db->prepare($contractSql);
            $stmt->execute(['contract_id' => $contractId]);
            $contract = $stmt->fetch();
            
            if (!$contract) {
                echo json_encode([
                    'success' => false,
                    'error' => '契約が見つかりません'
                ]);
                return;
            }
            
            // アクセス権限チェック
            $userId = Session::get('user_id');
            $userType = Session::get('user_type');
            
            if ($userType === 'doctor' && $contract['doctor_id'] != $userId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'アクセス権限がありません'
                ]);
                return;
            }
            
            if ($userType === 'company' && $contract['company_id'] != Session::get('company_id')) {
                echo json_encode([
                    'success' => false,
                    'error' => 'アクセス権限がありません'
                ]);
                return;
            }
            
            // 役務日の年月を取得
            $year = (int)date('Y', strtotime($serviceDate));
            $month = (int)date('n', strtotime($serviceDate));
            
            // 月の訪問時間を計算
            $monthlyLimit = $this->calculateMonthlyLimit($contract, $year, $month, $db);
            
            // 当月の指定日より前の役務を累積(編集中の記録は除外)
            $cumulativeSql = "SELECT 
                                SUM(service_hours) as cumulative_hours
                            FROM service_records
                            WHERE contract_id = :contract_id
                            AND YEAR(service_date) = :year
                            AND MONTH(service_date) = :month
                            AND service_date < :service_date
                            AND service_type = 'regular' 
                            AND status != 'rejected'";
            
            $params = [
                'contract_id' => $contractId,
                'year' => $year,
                'month' => $month,
                'service_date' => $serviceDate
            ];
            
            // 編集時は編集中の記録を除外
            if ($excludeRecordId > 0) {
                $cumulativeSql .= " AND id != :exclude_record_id";
                $params['exclude_record_id'] = $excludeRecordId;
            }
            
            $stmt = $db->prepare($cumulativeSql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            $cumulativeHours = (float)($result['cumulative_hours'] ?? 0);
            
            // 同日の役務も考慮(編集中の記録は除外)
            $sameDateSql = "SELECT 
                                SUM(service_hours) as same_date_hours
                            FROM service_records
                            WHERE contract_id = :contract_id
                            AND service_date = :service_date
                            AND service_type = 'regular' 
                            AND status != 'rejected'";
            
            $sameDateParams = [
                'contract_id' => $contractId,
                'service_date' => $serviceDate
            ];
            
            if ($excludeRecordId > 0) {
                $sameDateSql .= " AND id != :exclude_record_id";
                $sameDateParams['exclude_record_id'] = $excludeRecordId;
            }
            
            $stmt = $db->prepare($sameDateSql);
            $stmt->execute($sameDateParams);
            $sameDateResult = $stmt->fetch();
            
            $sameDateHours = (float)($sameDateResult['same_date_hours'] ?? 0);
            
            // 累積時間に同日の役務を加算
            $totalBeforeThisService = $cumulativeHours + $sameDateHours;
            
            // 今回の役務を加えた時の合計
            $totalWithThisService = $totalBeforeThisService + $serviceHours;
            
            // 延長判定
            $exceedsMonthlyLimit = $totalWithThisService > $monthlyLimit;
            $exceedsSingleVisitLimit = $serviceHours > (float)$contract['regular_visit_hours'];
            $requiresOvertimeReason = $exceedsMonthlyLimit || $exceedsSingleVisitLimit;
            
            echo json_encode([
                'success' => true,
                'monthly_limit' => $monthlyLimit,
                'cumulative_hours' => $totalBeforeThisService,
                'this_service_hours' => $serviceHours,
                'total_with_this_service' => $totalWithThisService,
                'exceeds_monthly_limit' => $exceedsMonthlyLimit,
                'exceeds_single_visit_limit' => $exceedsSingleVisitLimit,
                'requires_overtime_reason' => $requiresOvertimeReason,
                'contract_regular_hours' => (float)$contract['regular_visit_hours']
            ]);
            
        } catch (Exception $e) {
            error_log('Cumulative hours check error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => '累積時間チェックに失敗しました',
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>