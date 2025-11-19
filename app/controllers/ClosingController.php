<?php
// app/controllers/ClosingController.php - 月次締め処理コントローラー
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/ServiceRecord.php';
require_once __DIR__ . '/../models/MonthlyClosingRecord.php';
require_once __DIR__ . '/../models/MonthlyClosingDetail.php';
require_once __DIR__ . '/../models/ClosingProcessHistory.php';
require_once __DIR__ . '/../core/Database.php';

class ClosingController extends BaseController
{
    /**
     * 締め処理一覧画面
     */
    public function index()
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        $userId = Session::get('user_id');
        
        $currentMonth = $_GET['month'] ?? date('Y-m');
        
        // フィルターパラメータの取得
        $filterServiceType = $_GET['service_type'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $filterSearch = $_GET['search'] ?? '';
        
        // 対象年月の開始日と終了日を計算
        $targetMonthStart = $currentMonth . '-01';
        $targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
        
        $db = Database::getInstance()->getConnection();
        
        if ($userType === 'doctor') {
            // 産業医の場合は自分の契約のみ
            // 対象年月に有効な契約を抽出(effective_date <= 対象月末 AND (effective_end_date IS NULL OR effective_end_date >= 対象月初))
            $sql = "SELECT c.*, comp.name as company_name, b.name as branch_name
                    FROM contracts c
                    JOIN companies comp ON c.company_id = comp.id
                    JOIN branches b ON c.branch_id = b.id
                    WHERE c.doctor_id = :doctor_id 
                    AND c.contract_status = 'active'
                    AND c.effective_date <= :target_month_end
                    AND (c.effective_end_date IS NULL OR c.effective_end_date >= :target_month_start)
                    ORDER BY comp.name, b.name";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'doctor_id' => $userId,
                'target_month_end' => $targetMonthEnd,
                'target_month_start' => $targetMonthStart
            ]);
            
        } elseif ($userType === 'company') {
            // 企業ユーザーの場合は自分の会社の契約のみ
            $sql = "SELECT c.*, comp.name as company_name, b.name as branch_name, u.name as doctor_name
                    FROM contracts c
                    JOIN companies comp ON c.company_id = comp.id
                    JOIN branches b ON c.branch_id = b.id
                    JOIN users u ON c.doctor_id = u.id
                    JOIN user_branch_mappings ubm ON ubm.branch_id = b.id
                    WHERE ubm.user_id = :user_id
                    AND ubm.is_active = 1
                    AND c.contract_status = 'active'
                    AND b.is_active = 1
                    AND c.effective_date <= :target_month_end
                    AND (c.effective_end_date IS NULL OR c.effective_end_date >= :target_month_start)
                    ORDER BY comp.name, b.name, u.name";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'target_month_end' => $targetMonthEnd,
                'target_month_start' => $targetMonthStart
            ]);
            
        } elseif ($userType === 'admin') {
            // 管理者の場合は全ての契約
            $sql = "SELECT c.*, comp.name as company_name, b.name as branch_name, u.name as doctor_name
                    FROM contracts c
                    JOIN companies comp ON c.company_id = comp.id
                    JOIN branches b ON c.branch_id = b.id
                    JOIN users u ON c.doctor_id = u.id
                    WHERE c.contract_status = 'active'
                    AND b.is_active = 1
                    AND c.effective_date <= :target_month_end
                    AND (c.effective_end_date IS NULL OR c.effective_end_date >= :target_month_start)
                    ORDER BY comp.name, b.name, u.name";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'target_month_end' => $targetMonthEnd,
                'target_month_start' => $targetMonthStart
            ]);
            
        } else {
            $this->setFlash('error', 'アクセス権限がありません。');
            redirect('dashboard');
        }
        
        $contracts = $stmt->fetchAll();
        
        // 各契約の締め状況を取得
        $closingData = [];
        foreach ($contracts as $contract) {
            $closingRecord = $this->getClosingRecord($contract['id'], $currentMonth);
            $unClosedRecordsCount = $this->getUnClosedRecordsCount($contract['id'], $currentMonth);
            $unapprovedRecordsCount = $this->getUnapprovedRecordsCount($contract['id'], $currentMonth);
            $unapprovedTravelExpensesCount = $this->getUnapprovedTravelExpensesCount($contract['id'], $currentMonth);
            
            // 締め状況を判定
            $status = $this->determineClosingStatus($closingRecord);
            
            // フィルタリング処理
            // 役務種別フィルター
            if ($filterServiceType) {
                // 日本語から英語に変換
                $typeMapping = [
                    '定期訪問' => 'regular',
                    '臨時訪問' => 'emergency',
                    '延長' => 'extension',
                    '書面作成' => 'document',
                    '遠隔相談' => 'remote_consultation',
                    '欠勤' => 'absence',
                    'スポット対応' => 'spot',
                    'その他' => 'other'
                ];
                
                $englishType = $typeMapping[$filterServiceType] ?? $filterServiceType;
                $hasServiceType = $this->hasServiceTypeInMonth($contract['id'], $currentMonth, $englishType, $db);
                if (!$hasServiceType) {
                    continue;
                }
            }
            
            // 締め状況フィルター
            if ($filterStatus && $status !== $filterStatus) {
                continue;
            }
            
            // フリーテキスト検索フィルター
            if ($filterSearch) {
                $searchTerm = mb_strtolower($filterSearch);
                $shouldInclude = false;
                
                if ($userType === 'admin') {
                    // 管理者: 企業・拠点・産業医で検索
                    $companyName = mb_strtolower($contract['company_name'] ?? '');
                    $branchName = mb_strtolower($contract['branch_name'] ?? '');
                    $doctorName = mb_strtolower($contract['doctor_name'] ?? '');
                    
                    if (strpos($companyName, $searchTerm) !== false || 
                        strpos($branchName, $searchTerm) !== false || 
                        strpos($doctorName, $searchTerm) !== false) {
                        $shouldInclude = true;
                    }
                } elseif ($userType === 'company') {
                    // 企業ユーザー: 拠点・産業医で検索
                    $branchName = mb_strtolower($contract['branch_name'] ?? '');
                    $doctorName = mb_strtolower($contract['doctor_name'] ?? '');
                    
                    if (strpos($branchName, $searchTerm) !== false || 
                        strpos($doctorName, $searchTerm) !== false) {
                        $shouldInclude = true;
                    }
                } else {
                    // 産業医ユーザー: 企業・拠点で検索
                    $companyName = mb_strtolower($contract['company_name'] ?? '');
                    $branchName = mb_strtolower($contract['branch_name'] ?? '');
                    
                    if (strpos($companyName, $searchTerm) !== false || 
                        strpos($branchName, $searchTerm) !== false) {
                        $shouldInclude = true;
                    }
                }
                
                if (!$shouldInclude) {
                    continue;
                }
            }
            
            $closingData[] = [
                'contract' => $contract,
                'closing_record' => $closingRecord,
                'unclosed_records_count' => $unClosedRecordsCount,
                'unapproved_records_count' => $unapprovedRecordsCount,
                'unapproved_travel_expenses_count' => $unapprovedTravelExpensesCount,
                'can_process' => $this->canProcessClosing($contract['id'], $currentMonth, $unapprovedRecordsCount, $userType, $closingRecord, $unapprovedTravelExpensesCount) 
            ];
        }
        
        // 役務種別の一覧を取得(フィルター用)
        $availableServiceTypes = $this->getAvailableServiceTypes($db, $userType, $userId);
        
        ob_start();
        include __DIR__ . '/../views/closing/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    /**
     * 締め状況を判定する
     */
    private function determineClosingStatus($closingRecord)
    {
        if (!$closingRecord) {
            return '未処理';
        }
        
        if ($closingRecord['company_approved'] == 1) {
            return '企業承認済み';
        }
        
        if ($closingRecord['company_rejected_at']) {
            return '差戻し';
        }
        
        if ($closingRecord['status'] === 'finalized') {
            return '企業承認待ち';
        }
        
        return '下書き';
    }

    /**
     * 指定月に特定の役務種別が存在するかチェック
     */
    private function hasServiceTypeInMonth($contractId, $month, $serviceType, $db)
    {
        $targetMonthStart = $month . '-01';
        $targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
        
        $sql = "SELECT COUNT(*) as count
                FROM service_records
                WHERE contract_id = :contract_id
                AND service_date >= :start_date
                AND service_date <= :end_date
                AND service_type = :service_type";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'start_date' => $targetMonthStart,
            'end_date' => $targetMonthEnd,
            'service_type' => $serviceType
        ]);
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * システムで利用可能な役務種別の一覧を取得
     */
    private function getAvailableServiceTypes($db, $userType, $userId)
    {
        // 役務種別の日本語名マッピング
        $typeMapping = [
            'regular' => '定期訪問',
            'emergency' => '臨時訪問',
            'extension' => '延長',
            'document' => '書面作成',
            'remote_consultation' => '遠隔相談',
            'spot' => 'スポット',
            'other' => 'その他'
        ];
        
        $types = [];
        
        if ($userType === 'admin') {
            // 管理者: すべての役務種別
            $sql = "SELECT DISTINCT service_type 
                    FROM service_records 
                    WHERE service_type IS NOT NULL 
                    AND service_type != ''
                    ORDER BY service_type";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            
            while ($row = $stmt->fetch()) {
                $englishType = $row['service_type'];
                $japaneseType = $typeMapping[$englishType] ?? $englishType;
                $types[$englishType] = $japaneseType;
            }
            
        } elseif ($userType === 'doctor') {
            // 産業医: 自分の契約設定に基づいて役務種別を判定
            $sql = "SELECT 
                        MAX(CASE WHEN visit_frequency IN ('monthly', 'bimonthly', 'weekly') THEN 1 ELSE 0 END) as has_regular,
                        MAX(CASE WHEN use_remote_consultation = 1 THEN 1 ELSE 0 END) as has_remote,
                        MAX(CASE WHEN use_document_creation = 1 THEN 1 ELSE 0 END) as has_document,
                        MAX(CASE WHEN visit_frequency = 'spot' THEN 1 ELSE 0 END) as has_spot
                    FROM contracts
                    WHERE doctor_id = :user_id
                    AND contract_status = 'active'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch();
            
            if ($result) {
                // 定期訪問と臨時訪問（毎月・隔月・毎週の契約がある場合）
                if ($result['has_regular']) {
                    $types['regular'] = $typeMapping['regular'];
                    $types['emergency'] = $typeMapping['emergency'];
                }
                
                // 遠隔相談
                if ($result['has_remote']) {
                    $types['remote_consultation'] = $typeMapping['remote_consultation'];
                }
                
                // 書面作成
                if ($result['has_document']) {
                    $types['document'] = $typeMapping['document'];
                }
                
                // スポット対応
                if ($result['has_spot']) {
                    $types['spot'] = $typeMapping['spot'];
                }
                
                // その他は常にリスト
                $types['other'] = $typeMapping['other'];
            }
            
        } elseif ($userType === 'company') {
            // 企業ユーザー: 自分の会社の契約設定に基づいて役務種別を判定
            $sql = "SELECT 
                        MAX(CASE WHEN c.visit_frequency IN ('monthly', 'bimonthly', 'weekly') THEN 1 ELSE 0 END) as has_regular,
                        MAX(CASE WHEN c.use_remote_consultation = 1 THEN 1 ELSE 0 END) as has_remote,
                        MAX(CASE WHEN c.use_document_creation = 1 THEN 1 ELSE 0 END) as has_document,
                        MAX(CASE WHEN c.visit_frequency = 'spot' THEN 1 ELSE 0 END) as has_spot
                    FROM contracts c
                    JOIN branches b ON c.branch_id = b.id
                    JOIN user_branch_mappings ubm ON ubm.branch_id = b.id
                    WHERE ubm.user_id = :user_id
                    AND ubm.is_active = 1
                    AND c.contract_status = 'active'
                    AND b.is_active = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch();
            
            if ($result) {
                // 定期訪問と臨時訪問（毎月・隔月・毎週の契約がある場合）
                if ($result['has_regular']) {
                    $types['regular'] = $typeMapping['regular'];
                    $types['emergency'] = $typeMapping['emergency'];
                }
                
                // 遠隔相談
                if ($result['has_remote']) {
                    $types['remote_consultation'] = $typeMapping['remote_consultation'];
                }
                
                // 書面作成
                if ($result['has_document']) {
                    $types['document'] = $typeMapping['document'];
                }
                
                // スポット対応
                if ($result['has_spot']) {
                    $types['spot'] = $typeMapping['spot'];
                }
                
                // その他は常にリスト
                $types['other'] = $typeMapping['other'];
            }
        }
        
        return $types;
    }

    /**
     * 締め処理シミュレーション画面
     */
    public function simulation($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // パラメータの取得
        if ($contractId === null) {
            $contractId = (int)($_GET['contract_id'] ?? 0);
        } else {
            $contractId = (int)$contractId;
        }
        
        $closingPeriod = $_GET['period'] ?? date('Y-m');
        $regularBillingMethod = $_GET['billing_method'] ?? 'contract_hours';
        
        // 請求方法のバリデーション
        if (!in_array($regularBillingMethod, ['contract_hours', 'actual_hours'])) {
            $regularBillingMethod = 'contract_hours';
        }
        
        if (!$contractId) {
            $this->setFlash('error', '契約IDが指定されていません。');
            redirect('closing');
        }
        
        // 権限チェック
        $contract = $this->getContractWithPermissionCheck($contractId);
        if (!$contract) {
            $this->setFlash('error', 'この契約にはアクセスできません。');
            redirect('closing');
        }
        
        // 未承認の役務記録をチェック
        $unapprovedRecordsCount = $this->getUnapprovedRecordsCount($contractId, $closingPeriod);
        
        // 【追加】未承認の交通費をチェック
        $unapprovedTravelExpensesCount = $this->getUnapprovedTravelExpensesCount($contractId, $closingPeriod);
        
        // 毎週契約の場合の訪問回数計算情報を追加
        if ($contract['visit_frequency'] === 'weekly') {
            list($year, $month) = explode('-', $closingPeriod);
            $db = Database::getInstance()->getConnection();
            $visitCount = $this->calculateWeeklyVisitCount($contractId, (int)$year, (int)$month, $db);
            $singleVisitHours = (float)$contract['regular_visit_hours'];
            $totalContractHours = $visitCount * $singleVisitHours;
            
            $contract['calculated_visit_count'] = $visitCount;
            $contract['single_visit_hours'] = $singleVisitHours;
            $contract['total_contract_hours'] = $totalContractHours;
        }
        
        // シミュレーション実行(選択された請求方法で)
        $simulation = $this->executeSimulation($contractId, $closingPeriod, $regularBillingMethod);
        
        // 既存の締め記録があるかチェック
        $existingRecord = $this->getClosingRecord($contractId, $closingPeriod);
        
        ob_start();
        include __DIR__ . '/../views/closing/simulation.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }



    /**
     * 毎週契約の月間訪問回数を計算
     */
    private function calculateWeeklyVisitCount($contractId, $year, $month, $db)
    {
        // 契約情報を取得
        $contractSql = "SELECT exclude_holidays FROM contracts WHERE id = :contract_id";
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['contract_id' => $contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            return 0;
        }
        
        $excludeHolidays = (bool)$contract['exclude_holidays'];
        
        // 週間スケジュールを取得
        $scheduleSql = "SELECT day_of_week FROM contract_weekly_schedules 
                        WHERE contract_id = :contract_id ORDER BY day_of_week";
        $stmt = $db->prepare($scheduleSql);
        $stmt->execute(['contract_id' => $contractId]);
        $scheduledDays = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($scheduledDays)) {
            return 0;
        }
        
        // 月の日数を取得
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $visitCount = 0;
        
        // 祝日リストを取得
        $holidays = [];
        if ($excludeHolidays) {
            $holidaySql = "SELECT holiday_date FROM japanese_holidays 
                          WHERE YEAR(holiday_date) = :year 
                          AND MONTH(holiday_date) = :month";
            $stmt = $db->prepare($holidaySql);
            $stmt->execute(['year' => $year, 'month' => $month]);
            $holidays = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // 各日をチェック
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dayOfWeek = (int)date('N', strtotime($date)); // 1=月曜日, 7=日曜日
            
            // スケジュールに含まれているか
            if (in_array($dayOfWeek, $scheduledDays)) {
                // 祝日除外設定がある場合は祝日をスキップ
                if ($excludeHolidays && in_array($date, $holidays)) {
                    continue;
                }
                
                $visitCount++;
            }
        }
        
        return $visitCount;
    }

    /**
     * 契約情報を権限チェック付きで取得
     */
    private function getContractWithPermissionCheck($contractId)
    {
        $db = Database::getInstance()->getConnection();
        $userType = Session::get('user_type');
        $userId = Session::get('user_id');
        
        if ($userType === 'doctor') {
            $sql = "SELECT c.*, comp.name as company_name, b.name as branch_name
                    FROM contracts c
                    JOIN companies comp ON c.company_id = comp.id
                    JOIN branches b ON c.branch_id = b.id
                    WHERE c.id = :contract_id AND c.doctor_id = :doctor_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'contract_id' => $contractId,
                'doctor_id' => $userId
            ]);
            
        } elseif ($userType === 'company') {
            $sql = "SELECT c.*, comp.name as company_name, b.name as branch_name
                    FROM contracts c
                    JOIN companies comp ON c.company_id = comp.id
                    JOIN branches b ON c.branch_id = b.id
                    JOIN user_branch_mappings ubm ON ubm.branch_id = b.id
                    WHERE c.id = :contract_id 
                    AND ubm.user_id = :user_id
                    AND ubm.is_active = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'contract_id' => $contractId,
                'user_id' => $userId
            ]);
            
        } elseif ($userType === 'admin') {
            $sql = "SELECT c.*, comp.name as company_name, b.name as branch_name
                    FROM contracts c
                    JOIN companies comp ON c.company_id = comp.id
                    JOIN branches b ON c.branch_id = b.id
                    WHERE c.id = :contract_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['contract_id' => $contractId]);
            
        } else {
            return null;
        }
        
        return $stmt->fetch();
    }

    /**
     * 締め記録を取得
     */
    private function getClosingRecord($contractId, $closingPeriod)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM monthly_closing_records 
                WHERE contract_id = :contract_id 
                AND closing_period = :closing_period";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'closing_period' => $closingPeriod
        ]);
        
        return $stmt->fetch();
    }

    /**
     * 未締めの役務記録数を取得
     */
    private function getUnClosedRecordsCount($contractId, $closingPeriod)
    {
        $targetMonthStart = $closingPeriod . '-01';
        $targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
        
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) as count
                FROM service_records
                WHERE contract_id = :contract_id
                AND service_date >= :start_date
                AND service_date <= :end_date
                AND is_closed = 0
                AND status = 'approved'";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'start_date' => $targetMonthStart,
            'end_date' => $targetMonthEnd
        ]);
        
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * 未承認の役務記録数を取得
     */
    private function getUnapprovedRecordsCount($contractId, $closingPeriod)
    {
        $targetMonthStart = $closingPeriod . '-01';
        $targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
        
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) as count
                FROM service_records
                WHERE contract_id = :contract_id
                AND service_date >= :start_date
                AND service_date <= :end_date
                AND status = 'pending'";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'start_date' => $targetMonthStart,
            'end_date' => $targetMonthEnd
        ]);
        
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * 未承認の交通費数を取得
     */
    private function getUnapprovedTravelExpensesCount($contractId, $closingPeriod)
    {
        $targetMonthStart = $closingPeriod . '-01';
        $targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
        
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) as count
                FROM travel_expenses te
                JOIN service_records sr ON te.service_record_id = sr.id
                WHERE sr.contract_id = :contract_id
                AND sr.service_date >= :start_date
                AND sr.service_date <= :end_date
                AND te.status = 'pending'";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'start_date' => $targetMonthStart,
            'end_date' => $targetMonthEnd
        ]);
        
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * 締め処理が可能かチェック
     */
    private function canProcessClosing($contractId, $closingPeriod, $unapprovedRecordsCount, $userType, $closingRecord, $unapprovedTravelExpensesCount)
    {
        // 企業ユーザーの場合
        if ($userType === 'company') {
            // 締め記録が存在し、確定済みで、企業承認待ちまたは差戻しの場合のみ処理可能
            if ($closingRecord && 
                $closingRecord['status'] === 'finalized' && 
                $closingRecord['company_approved'] == 0) {
                return true;
            }
            return false;
        }
        
        // 産業医・管理者の場合
        // 未承認の役務記録または交通費がある場合は処理不可
        if ($unapprovedRecordsCount > 0 || $unapprovedTravelExpensesCount > 0) {
            return false;
        }
        
        // 既に企業承認済みの場合は処理不可
        if ($closingRecord && $closingRecord['company_approved'] == 1) {
            return false;
        }
        
        return true;
    }

    /**
     * シミュレーション実行
     */
    private function executeSimulation($contractId, $closingPeriod, $regularBillingMethod = 'contract_hours')
    {
        $db = Database::getInstance()->getConnection();
        
        // 契約情報を取得
        $contract = $this->getContractWithPermissionCheck($contractId);
        if (!$contract) {
            return null;
        }
        
        $targetMonthStart = $closingPeriod . '-01';
        $targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
        
        // 承認済みの役務記録を取得
        $sql = "SELECT * FROM service_records
                WHERE contract_id = :contract_id
                AND service_date >= :start_date
                AND service_date <= :end_date
                AND status = 'approved'
                ORDER BY service_date, id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'start_date' => $targetMonthStart,
            'end_date' => $targetMonthEnd
        ]);
        
        $serviceRecords = $stmt->fetchAll();
        
        // 各役務記録の交通費を取得
        foreach ($serviceRecords as &$record) {
            $travelSql = "SELECT * FROM travel_expenses
                         WHERE service_record_id = :service_record_id
                         AND status = 'approved'";
            
            $travelStmt = $db->prepare($travelSql);
            $travelStmt->execute(['service_record_id' => $record['id']]);
            $record['travel_expenses'] = $travelStmt->fetchAll();
        }
        
        // シミュレーション計算
        $simulation = $this->calculateSimulation($contract, $serviceRecords, $closingPeriod, $regularBillingMethod);
        
        return $simulation;
    }

    /**
     * シミュレーション計算
     */
    private function calculateSimulation($contract, $serviceRecords, $closingPeriod, $regularBillingMethod)
    {
        $simulation = [
            'contract' => $contract,
            'service_records' => $serviceRecords,
            'regular_amount' => 0,
            'overtime_amount' => 0,
            'travel_expense_total' => 0,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount_with_tax' => 0,
            'details' => []
        ];
        
        // 定期訪問の時間集計
        $regularHours = 0;
        $overtimeHours = 0;
        
        // 役務種別ごとの集計
        $serviceTypeTotals = [];
        
        foreach ($serviceRecords as $record) {
            // スポット契約かどうかチェック
            $isSpot = ($record['is_spot_contract'] == 1);
            
            if ($isSpot) {
                // スポット契約の場合
                $hours = (float)$record['actual_hours'];
                $rate = (float)$record['hourly_rate'];
                $amount = $hours * $rate;
                
                $serviceType = $record['service_type'] ?? 'その他';
                if (!isset($serviceTypeTotals[$serviceType])) {
                    $serviceTypeTotals[$serviceType] = [
                        'hours' => 0,
                        'amount' => 0,
                        'count' => 0
                    ];
                }
                
                $serviceTypeTotals[$serviceType]['hours'] += $hours;
                $serviceTypeTotals[$serviceType]['amount'] += $amount;
                $serviceTypeTotals[$serviceType]['count']++;
                
            } else {
                // 定期契約の場合
                $serviceType = $record['service_type'] ?? '定期訪問';
                $hours = (float)$record['actual_hours'];
                
                if (!isset($serviceTypeTotals[$serviceType])) {
                    $serviceTypeTotals[$serviceType] = [
                        'hours' => 0,
                        'amount' => 0,
                        'count' => 0
                    ];
                }
                
                $serviceTypeTotals[$serviceType]['hours'] += $hours;
                $serviceTypeTotals[$serviceType]['count']++;
                $regularHours += $hours;
            }
            
            // 交通費の集計
            if (!empty($record['travel_expenses'])) {
                foreach ($record['travel_expenses'] as $travel) {
                    $simulation['travel_expense_total'] += (float)$travel['amount'];
                }
            }
        }
        
        // 定期訪問の請求金額計算
        if ($regularBillingMethod === 'contract_hours') {
            // 契約時間で請求
            if ($contract['visit_frequency'] === 'weekly') {
                // 毎週契約の場合
                $contractHours = (float)($contract['total_contract_hours'] ?? 0);
            } else {
                // 月間・隔月契約の場合
                $contractHours = (float)$contract['regular_visit_hours'];
            }
            
            $simulation['regular_amount'] = $contractHours * (float)$contract['hourly_rate'];
            
        } else {
            // 実績時間で請求
            $contractHours = (float)$contract['regular_visit_hours'];
            
            if ($regularHours <= $contractHours) {
                $simulation['regular_amount'] = $regularHours * (float)$contract['hourly_rate'];
            } else {
                $simulation['regular_amount'] = $contractHours * (float)$contract['hourly_rate'];
                $overtimeHours = $regularHours - $contractHours;
                $simulation['overtime_amount'] = $overtimeHours * (float)$contract['overtime_rate'];
            }
        }
        
        // 役務種別ごとの金額を詳細に追加
        foreach ($serviceTypeTotals as $type => $data) {
            if ($type !== '定期訪問' && $data['amount'] > 0) {
                $simulation['details'][] = [
                    'service_type' => $type,
                    'hours' => $data['hours'],
                    'amount' => $data['amount'],
                    'count' => $data['count']
                ];
            }
        }
        
        // スポット契約の合計金額を追加
        $spotTotal = 0;
        foreach ($serviceTypeTotals as $type => $data) {
            if ($type !== '定期訪問') {
                $spotTotal += $data['amount'];
            }
        }
        
        // 小計計算
        $simulation['subtotal'] = $simulation['regular_amount'] + 
                                 $simulation['overtime_amount'] + 
                                 $spotTotal + 
                                 $simulation['travel_expense_total'];
        
        // 消費税計算
        $taxRate = (float)($contract['tax_rate'] ?? 0.1);
        $simulation['tax_amount'] = floor($simulation['subtotal'] * $taxRate);
        
        // 税込合計
        $simulation['total_amount_with_tax'] = $simulation['subtotal'] + $simulation['tax_amount'];
        
        return $simulation;
    }

    /**
     * 締め処理の保存(下書きまたは確定)
     */
    public function save($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 産業医または管理者のみ実行可能
        if ($userType === 'company') {
            $this->setFlash('error', '締め処理の保存は産業医または管理者のみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            redirect('closing');
        }
        
        if ($contractId === null) {
            $contractId = (int)($_POST['contract_id'] ?? 0);
        } else {
            $contractId = (int)$contractId;
        }
        
        $closingPeriod = $_POST['closing_period'] ?? '';
        $status = $_POST['status'] ?? 'draft'; // 'draft' or 'finalized'
        $regularBillingMethod = $_POST['regular_billing_method'] ?? 'contract_hours';
        
        if (!$contractId || !$closingPeriod) {
            $this->setFlash('error', '必要なパラメータが不足しています。');
            redirect('closing');
        }
        
        // 権限チェック
        $contract = $this->getContractWithPermissionCheck($contractId);
        if (!$contract) {
            $this->setFlash('error', 'この契約にはアクセスできません。');
            redirect('closing');
        }
        
        // 未承認の役務記録をチェック
        $unapprovedRecordsCount = $this->getUnapprovedRecordsCount($contractId, $closingPeriod);
        $unapprovedTravelExpensesCount = $this->getUnapprovedTravelExpensesCount($contractId, $closingPeriod);
        
        if ($status === 'finalized' && ($unapprovedRecordsCount > 0 || $unapprovedTravelExpensesCount > 0)) {
            $this->setFlash('error', '未承認の役務記録または交通費があるため、確定できません。');
            redirect('closing/simulation/' . $contractId . '?period=' . $closingPeriod);
        }
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            
            // シミュレーション実行
            $simulation = $this->executeSimulation($contractId, $closingPeriod, $regularBillingMethod);
            
            if (!$simulation) {
                throw new Exception('シミュレーションの実行に失敗しました。');
            }
            
            // 既存の締め記録をチェック
            $existingRecord = $this->getClosingRecord($contractId, $closingPeriod);
            
            if ($existingRecord) {
                // 更新
                if ($existingRecord['company_approved'] == 1) {
                    throw new Exception('企業承認済みの締め処理は編集できません。');
                }
                
                $sql = "UPDATE monthly_closing_records SET
                        regular_amount = :regular_amount,
                        overtime_amount = :overtime_amount,
                        travel_expense_total = :travel_expense_total,
                        total_amount = :total_amount,
                        tax_rate = :tax_rate,
                        tax_amount = :tax_amount,
                        total_amount_with_tax = :total_amount_with_tax,
                        status = :status,
                        finalized_at = :finalized_at,
                        regular_billing_method = :regular_billing_method,
                        company_rejected_at = NULL,
                        rejection_reason = NULL,
                        updated_at = NOW()
                        WHERE id = :id";
                
                $params = [
                    'id' => $existingRecord['id'],
                    'regular_amount' => $simulation['regular_amount'],
                    'overtime_amount' => $simulation['overtime_amount'],
                    'travel_expense_total' => $simulation['travel_expense_total'],
                    'total_amount' => $simulation['subtotal'],
                    'tax_rate' => $contract['tax_rate'] ?? 0.1,
                    'tax_amount' => $simulation['tax_amount'],
                    'total_amount_with_tax' => $simulation['total_amount_with_tax'],
                    'status' => $status,
                    'finalized_at' => ($status === 'finalized') ? date('Y-m-d H:i:s') : null,
                    'regular_billing_method' => $regularBillingMethod
                ];
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute($params);
                
                if (!$result) {
                    throw new Exception('締め記録の更新に失敗しました。');
                }
                
                $closingRecordId = $existingRecord['id'];
                
                // 既存の明細を削除
                $deleteSql = "DELETE FROM monthly_closing_details WHERE closing_record_id = :closing_record_id";
                $stmt = $db->prepare($deleteSql);
                $stmt->execute(['closing_record_id' => $closingRecordId]);
                
                // 履歴記録
                $action = ($status === 'finalized') ? 'update_finalize' : 'update_draft';
                $this->createClosingHistory($closingRecordId, $action, $userId, '締め処理を更新しました');
                
            } else {
                // 新規作成
                $sql = "INSERT INTO monthly_closing_records (
                            contract_id, doctor_id, closing_period,
                            regular_amount, overtime_amount, travel_expense_total,
                            total_amount, tax_rate, tax_amount, total_amount_with_tax,
                            status, finalized_at, regular_billing_method,
                            created_at, updated_at
                        ) VALUES (
                            :contract_id, :doctor_id, :closing_period,
                            :regular_amount, :overtime_amount, :travel_expense_total,
                            :total_amount, :tax_rate, :tax_amount, :total_amount_with_tax,
                            :status, :finalized_at, :regular_billing_method,
                            NOW(), NOW()
                        )";
                
                $params = [
                    'contract_id' => $contractId,
                    'doctor_id' => $contract['doctor_id'],
                    'closing_period' => $closingPeriod,
                    'regular_amount' => $simulation['regular_amount'],
                    'overtime_amount' => $simulation['overtime_amount'],
                    'travel_expense_total' => $simulation['travel_expense_total'],
                    'total_amount' => $simulation['subtotal'],
                    'tax_rate' => $contract['tax_rate'] ?? 0.1,
                    'tax_amount' => $simulation['tax_amount'],
                    'total_amount_with_tax' => $simulation['total_amount_with_tax'],
                    'status' => $status,
                    'finalized_at' => ($status === 'finalized') ? date('Y-m-d H:i:s') : null,
                    'regular_billing_method' => $regularBillingMethod
                ];
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute($params);
                
                if (!$result) {
                    throw new Exception('締め記録の作成に失敗しました。');
                }
                
                $closingRecordId = $db->lastInsertId();
                
                // 履歴記録
                $action = ($status === 'finalized') ? 'finalize' : 'create_draft';
                $this->createClosingHistory($closingRecordId, $action, $userId, '締め処理を作成しました');
            }
            
            // 明細を保存
            foreach ($simulation['service_records'] as $record) {
                $detailSql = "INSERT INTO monthly_closing_details (
                                closing_record_id, service_record_id,
                                service_date, service_type, actual_hours,
                                hourly_rate, amount, travel_expense_amount,
                                created_at
                              ) VALUES (
                                :closing_record_id, :service_record_id,
                                :service_date, :service_type, :actual_hours,
                                :hourly_rate, :amount, :travel_expense_amount,
                                NOW()
                              )";
                
                $travelExpenseAmount = 0;
                if (!empty($record['travel_expenses'])) {
                    foreach ($record['travel_expenses'] as $travel) {
                        $travelExpenseAmount += (float)$travel['amount'];
                    }
                }
                
                $isSpot = ($record['is_spot_contract'] == 1);
                $hours = (float)$record['actual_hours'];
                $rate = $isSpot ? (float)$record['hourly_rate'] : (float)$contract['hourly_rate'];
                $amount = $isSpot ? ($hours * $rate) : 0; // 定期契約は個別の金額を持たない
                
                $detailParams = [
                    'closing_record_id' => $closingRecordId,
                    'service_record_id' => $record['id'],
                    'service_date' => $record['service_date'],
                    'service_type' => $record['service_type'],
                    'actual_hours' => $hours,
                    'hourly_rate' => $rate,
                    'amount' => $amount,
                    'travel_expense_amount' => $travelExpenseAmount
                ];
                
                $stmt = $db->prepare($detailSql);
                $stmt->execute($detailParams);
            }
            
            // 役務記録の締め状態を更新(確定の場合のみ)
            if ($status === 'finalized') {
                $targetMonthStart = $closingPeriod . '-01';
                $targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
                
                $updateSql = "UPDATE service_records SET
                             is_closed = 1,
                             closed_at = NOW()
                             WHERE contract_id = :contract_id
                             AND service_date >= :start_date
                             AND service_date <= :end_date
                             AND status = 'approved'
                             AND is_closed = 0";
                
                $stmt = $db->prepare($updateSql);
                $stmt->execute([
                    'contract_id' => $contractId,
                    'start_date' => $targetMonthStart,
                    'end_date' => $targetMonthEnd
                ]);
            }
            
            $db->commit();
            
            if ($status === 'finalized') {
                $this->setFlash('success', '締め処理を確定しました。企業の承認をお待ちください。');
            } else {
                $this->setFlash('success', '締め処理を下書き保存しました。');
            }
            
            redirect('closing?month=' . $closingPeriod);
            
        } catch (Exception $e) {
            $db->rollback();
            $this->setFlash('error', '締め処理の保存に失敗しました: ' . $e->getMessage());
            redirect('closing/simulation/' . $contractId . '?period=' . $closingPeriod);
        }
    }

    /**
     * 締め処理の削除(下書きのみ)
     */
    public function delete($closingRecordId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 産業医または管理者のみ実行可能
        if ($userType === 'company') {
            $this->setFlash('error', '締め処理の削除は産業医または管理者のみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            redirect('closing');
        }
        
        if ($closingRecordId === null) {
            $closingRecordId = (int)($_POST['closing_record_id'] ?? 0);
        } else {
            $closingRecordId = (int)$closingRecordId;
        }
        
        if (!$closingRecordId) {
            $this->setFlash('error', '締め処理IDが指定されていません。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            
            // 締め記録を取得
            $sql = "SELECT mcr.*, c.doctor_id 
                    FROM monthly_closing_records mcr
                    JOIN contracts c ON mcr.contract_id = c.id
                    WHERE mcr.id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $closingRecordId]);
            $record = $stmt->fetch();
            
            if (!$record) {
                throw new Exception('締め記録が見つかりません。');
            }
            
            // 権限チェック(産業医の場合は自分の記録のみ)
            if ($userType === 'doctor' && $record['doctor_id'] != $userId) {
                throw new Exception('この締め処理を削除する権限がありません。');
            }
            
            // 下書きのみ削除可能
            if ($record['status'] !== 'draft') {
                throw new Exception('下書きの締め処理のみ削除できます。');
            }
            
            // 明細を削除
            $deleteSql = "DELETE FROM monthly_closing_details WHERE closing_record_id = :closing_record_id";
            $stmt = $db->prepare($deleteSql);
            $stmt->execute(['closing_record_id' => $closingRecordId]);
            
            // 締め記録を削除
            $deleteSql = "DELETE FROM monthly_closing_records WHERE id = :id";
            $stmt = $db->prepare($deleteSql);
            $result = $stmt->execute(['id' => $closingRecordId]);
            
            if (!$result) {
                throw new Exception('締め記録の削除に失敗しました。');
            }
            
            $db->commit();
            
            $this->setFlash('success', '締め処理(下書き)を削除しました。');
            redirect('closing?month=' . $record['closing_period']);
            
        } catch (Exception $e) {
            $db->rollback();
            $this->setFlash('error', '締め処理の削除に失敗しました: ' . $e->getMessage());
            redirect('closing');
        }
    }

    /**
     * 企業承認処理
     */
    public function approve($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 企業ユーザーのみ実行可能
        if ($userType !== 'company') {
            $this->setFlash('error', '企業承認は企業ユーザーのみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            redirect('closing');
        }
        
        if ($contractId === null) {
            $contractId = (int)($_POST['contract_id'] ?? 0);
        } else {
            $contractId = (int)$contractId;
        }
        
        $closingPeriod = $_POST['closing_period'] ?? '';
        
        if (!$contractId || !$closingPeriod) {
            $this->setFlash('error', '必要なパラメータが不足しています。');
            redirect('closing');
        }
        
        // 権限チェック
        $contract = $this->getContractWithPermissionCheck($contractId);
        if (!$contract) {
            $this->setFlash('error', 'この契約にはアクセスできません。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            
            // 締め記録を取得
            $closingRecord = $this->getClosingRecord($contractId, $closingPeriod);
            if (!$closingRecord) {
                throw new Exception('締め記録が見つかりません。');
            }
            
            if ($closingRecord['status'] !== 'finalized') {
                throw new Exception('確定済みの締め処理のみ承認できます。');
            }
            
            if ($closingRecord['company_approved'] == 1) {
                throw new Exception('既に承認済みです。');
            }
            
            // 企業承認を実行
            $sql = "UPDATE monthly_closing_records 
                    SET company_approved = 1,
                        company_approved_at = NOW(),
                        company_approved_by = :approved_by,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                'id' => $closingRecord['id'],
                'approved_by' => $userId
            ]);
            
            if (!$result) {
                throw new Exception('データベースの更新に失敗しました。');
            }
            
            // 履歴記録
            $this->createClosingHistory($closingRecord['id'], 'company_approve', $userId, '企業が承認しました');
            
            // 請求書PDF生成(別途実装)
            // $this->generateInvoicePdf($closingRecord['id']);
            
            $db->commit();
            
            $this->setFlash('success', '締め処理を承認しました。');
            redirect('closing?month=' . $closingPeriod);
            
        } catch (Exception $e) {
            $db->rollback();
            $this->setFlash('error', '締め処理の承認に失敗しました: ' . $e->getMessage());
            redirect('closing?month=' . $closingPeriod);
        }
    }

    /**
     * 企業差戻し処理
     */
    public function reject($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 企業ユーザーのみ実行可能
        if ($userType !== 'company') {
            $this->setFlash('error', '差戻しは企業ユーザーのみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            redirect('closing');
        }
        
        if ($contractId === null) {
            $contractId = (int)($_POST['contract_id'] ?? 0);
        } else {
            $contractId = (int)$contractId;
        }
        
        $closingPeriod = $_POST['closing_period'] ?? '';
        $rejectReason = trim($_POST['reject_reason'] ?? '');
        
        if (!$contractId || !$closingPeriod || !$rejectReason) {
            $this->setFlash('error', '必要なパラメータが不足しています。差戻し理由は必須です。');
            redirect('closing?month=' . $closingPeriod);
        }
        
        // 権限チェック
        $contract = $this->getContractWithPermissionCheck($contractId);
        if (!$contract) {
            $this->setFlash('error', 'この契約にはアクセスできません。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            
            // 締め記録を取得
            $closingRecord = $this->getClosingRecord($contractId, $closingPeriod);
            if (!$closingRecord) {
                throw new Exception('締め記録が見つかりません。');
            }
            
            if ($closingRecord['status'] !== 'finalized') {
                throw new Exception('確定済みの締め処理のみ差戻しできます。');
            }
            
            if ($closingRecord['company_approved'] == 1) {
                throw new Exception('承認済みの締め処理は差戻しできません。');
            }
            
            // 差戻しを実行(ステータスはfinalized維持、差戻しフラグを立てる)
            $sql = "UPDATE monthly_closing_records 
                    SET company_rejected_at = NOW(),
                        rejection_reason = :rejection_reason,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                'id' => $closingRecord['id'],
                'rejection_reason' => $rejectReason
            ]);
            
            if (!$result) {
                throw new Exception('データベースの更新に失敗しました。');
            }
            
            // 役務記録の締め状態を解除
            $targetMonthStart = $closingPeriod . '-01';
            $targetMonthEnd = date('Y-m-t', strtotime($targetMonthStart));
            
            $updateSql = "UPDATE service_records SET
                         is_closed = 0,
                         closed_at = NULL
                         WHERE contract_id = :contract_id
                         AND service_date >= :start_date
                         AND service_date <= :end_date
                         AND is_closed = 1";
            
            $stmt = $db->prepare($updateSql);
            $stmt->execute([
                'contract_id' => $contractId,
                'start_date' => $targetMonthStart,
                'end_date' => $targetMonthEnd
            ]);
            
            // 履歴記録
            $this->createClosingHistory($closingRecord['id'], 'company_reject', $userId, '企業が差戻しました: ' . $rejectReason);
            
            $db->commit();
            
            $this->setFlash('success', '締め処理を差戻しました。産業医に修正を依頼します。');
            redirect('closing?month=' . $closingPeriod);
            
        } catch (Exception $e) {
            $db->rollback();
            $this->setFlash('error', '締め処理の差戻しに失敗しました: ' . $e->getMessage());
            redirect('closing?month=' . $closingPeriod);
        }
    }

    /**
     * 一括承認処理(管理者専用)
     */
    public function bulkApprove()
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 管理者のみ実行可能
        if ($userType !== 'admin') {
            $this->setFlash('error', '一括承認は管理者のみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            redirect('closing');
        }
        
        $closingPeriod = $_POST['closing_period'] ?? '';
        
        if (!$closingPeriod) {
            $this->setFlash('error', '対象月が指定されていません。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            
            // 対象月の全ての確定済み・未承認の締め記録を取得
            $sql = "SELECT * FROM monthly_closing_records
                    WHERE closing_period = :closing_period
                    AND status = 'finalized'
                    AND company_approved = 0
                    AND company_rejected_at IS NULL";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['closing_period' => $closingPeriod]);
            $records = $stmt->fetchAll();
            
            if (empty($records)) {
                throw new Exception('承認対象の締め処理がありません。');
            }
            
            $count = 0;
            foreach ($records as $record) {
                // 企業承認を実行
                $updateSql = "UPDATE monthly_closing_records 
                            SET company_approved = 1,
                                company_approved_at = NOW(),
                                company_approved_by = :approved_by,
                                updated_at = NOW()
                            WHERE id = :id";
                
                $stmt = $db->prepare($updateSql);
                $stmt->execute([
                    'id' => $record['id'],
                    'approved_by' => $userId
                ]);
                
                // 履歴記録
                $this->createClosingHistory($record['id'], 'bulk_approve', $userId, '管理者が一括承認しました');
                
                $count++;
            }
            
            $db->commit();
            
            $this->setFlash('success', "{$count}件の締め処理を一括承認しました。");
            redirect('closing?month=' . $closingPeriod);
            
        } catch (Exception $e) {
            $db->rollback();
            $this->setFlash('error', '一括承認処理に失敗しました: ' . $e->getMessage());
            redirect('closing?month=' . $closingPeriod);
        }
    }

    /**
     * 一括承認確認画面
     */
    public function bulkApproveConfirm()
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 管理者のみアクセス可能
        if ($userType !== 'admin') {
            $this->setFlash('error', '一括承認は管理者のみが実行できます。');
            redirect('closing');
        }
        
        $closingPeriod = $_GET['period'] ?? date('Y-m');
        
        $db = Database::getInstance()->getConnection();
        
        // 対象月の全ての確定済み・未承認の締め記録を取得
        $sql = "SELECT mcr.*, c.*, comp.name as company_name, b.name as branch_name, u.name as doctor_name
                FROM monthly_closing_records mcr
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                JOIN users u ON mcr.doctor_id = u.id
                WHERE mcr.closing_period = :closing_period
                AND mcr.status = 'finalized'
                AND mcr.company_approved = 0
                AND mcr.company_rejected_at IS NULL
                ORDER BY comp.name, b.name, u.name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['closing_period' => $closingPeriod]);
        $records = $stmt->fetchAll();
        
        ob_start();
        include __DIR__ . '/../views/closing/bulk_approve_confirm.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    /**
     * 一括承認API(JSON レスポンス)
     */
    public function bulkApproveApi()
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 管理者のみ実行可能
        if ($userType !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '一括承認は管理者のみが実行できます。'
            ]);
            exit;
        }
        
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'CSRFトークンが無効です。'
            ]);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $closingPeriod = $input['closing_period'] ?? '';
        
        if (!$closingPeriod) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '対象月が指定されていません。'
            ]);
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            
            // 対象月の全ての確定済み・未承認の締め記録を取得
            $sql = "SELECT * FROM monthly_closing_records
                    WHERE closing_period = :closing_period
                    AND status = 'finalized'
                    AND company_approved = 0
                    AND company_rejected_at IS NULL";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['closing_period' => $closingPeriod]);
            $records = $stmt->fetchAll();
            
            if (empty($records)) {
                throw new Exception('承認対象の締め処理がありません。');
            }
            
            $count = 0;
            foreach ($records as $record) {
                // 企業承認を実行
                $updateSql = "UPDATE monthly_closing_records 
                            SET company_approved = 1,
                                company_approved_at = NOW(),
                                company_approved_by = :approved_by,
                                updated_at = NOW()
                            WHERE id = :id";
                
                $stmt = $db->prepare($updateSql);
                $stmt->execute([
                    'id' => $record['id'],
                    'approved_by' => $userId
                ]);
                
                // 履歴記録
                $this->createClosingHistory($record['id'], 'bulk_approve', $userId, '管理者が一括承認しました');
                
                $count++;
            }
            
            $db->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "{$count}件の締め処理を一括承認しました。",
                'count' => $count
            ]);
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '一括承認処理に失敗しました: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * 管理者による企業承認の取り消し
     */
    public function revokeCompanyApproval($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 管理者のみ実行可能
        if ($userType !== 'admin') {
            $this->setFlash('error', '企業承認の取り消しは管理者のみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            redirect('closing');
        }
        
        if ($contractId === null) {
            $contractId = (int)($_POST['contract_id'] ?? 0);
        } else {
            $contractId = (int)$contractId;
        }
        
        $closingPeriod = $_POST['closing_period'] ?? '';
        
        if (!$contractId || !$closingPeriod) {
            $this->setFlash('error', '必要なパラメータが不足しています。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            
            // 締め記録を取得
            $closingRecord = $this->getClosingRecord($contractId, $closingPeriod);
            if (!$closingRecord) {
                throw new Exception('締め記録が見つかりません。');
            }
            
            if ($closingRecord['status'] !== 'finalized') {
                throw new Exception('確定済みの締め記録のみ取り消しできます。');
            }
            
            if ($closingRecord['company_approved'] != 1) {
                throw new Exception('企業承認されていない締め処理は取り消しできません。');
            }
            
            // 請求書PDFファイルのパスを保存(削除用)
            $pdfPath = $closingRecord['invoice_pdf_path'] ?? null;
            
            // 企業承認を取り消し
            $sql = "UPDATE monthly_closing_records 
                    SET company_approved = 0,
                        company_approved_at = NULL,
                        company_approved_by = NULL,
                        invoice_pdf_path = NULL,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute(['id' => $closingRecord['id']]);
            
            if (!$result) {
                throw new Exception('データベースの更新に失敗しました。');
            }
            
            // 履歴記録
            $this->createClosingHistory($closingRecord['id'], 'revoke_approval', $userId, '管理者が企業承認を取り消しました');
            
            $db->commit();
            
            // トランザクション完了後に請求書PDFを削除
            if ($pdfPath && file_exists($pdfPath)) {
                try {
                    if (unlink($pdfPath)) {
                        error_log("Invoice PDF deleted successfully: {$pdfPath}");
                    } else {
                        error_log("Failed to delete invoice PDF: {$pdfPath}");
                    }
                } catch (Exception $e) {
                    error_log("Error deleting invoice PDF: " . $e->getMessage());
                }
            }
            
            $this->setFlash('success', '企業承認を取り消しました。請求書PDFも削除されました。');
            redirect('closing');
            
        } catch (Exception $e) {
            $db->rollback();
            $this->setFlash('error', '企業承認の取り消しに失敗しました: ' . $e->getMessage());
            redirect('closing');
        }
    }

    /**
     * 締め処理明細のCSV出力
     */
    public function exportCsv($closingRecordId = null)
    {
        Session::requireLogin();
        
        if ($closingRecordId === null) {
            $closingRecordId = (int)($_GET['id'] ?? 0);
        } else {
            $closingRecordId = (int)$closingRecordId;
        }
        
        if (!$closingRecordId) {
            $this->setFlash('error', '締め処理IDが指定されていません。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        
        // 締め記録を取得
        $sql = "SELECT mcr.*, c.company_id, c.branch_id, 
                       comp.name as company_name, b.name as branch_name,
                       u.name as doctor_name
                FROM monthly_closing_records mcr
                JOIN contracts c ON mcr.contract_id = c.id
                JOIN companies comp ON c.company_id = comp.id
                JOIN branches b ON c.branch_id = b.id
                JOIN users u ON mcr.doctor_id = u.id
                WHERE mcr.id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $closingRecordId]);
        $record = $stmt->fetch();
        
        if (!$record) {
            $this->setFlash('error', '締め処理記録が見つかりません。');
            redirect('closing');
        }
        
        // 権限チェック
        $userType = Session::get('user_type');
        $userId = Session::get('user_id');
        
        if ($userType === 'doctor' && $record['doctor_id'] != $userId) {
            $this->setFlash('error', 'この締め処理にはアクセスできません。');
            redirect('closing');
        }
        
        if ($userType === 'company') {
            // 企業ユーザーは自分の会社の締め処理のみ
            $sql = "SELECT COUNT(*) as count
                    FROM user_branch_mappings
                    WHERE user_id = :user_id
                    AND branch_id = :branch_id
                    AND is_active = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'branch_id' => $record['branch_id']
            ]);
            
            $result = $stmt->fetch();
            if ($result['count'] == 0) {
                $this->setFlash('error', 'この締め処理にはアクセスできません。');
                redirect('closing');
            }
        }
        
        // CSV出力
        $csvData = MonthlyClosingDetail::exportToCsv($closingRecordId, $db);
        
        if (empty($csvData)) {
            $this->setFlash('error', '出力するデータがありません。');
            redirect('closing');
        }
        
        // ファイル名生成
        $fileName = sprintf(
            '締め処理明細_%s_%s_%s.csv',
            $record['company_name'],
            $record['branch_name'],
            $record['closing_period']
        );
        
        // CSVヘッダー出力
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        
        // BOM出力(Excel対応)
        echo "\xEF\xBB\xBF";
        
        // CSV出力
        $output = fopen('php://output', 'w');
        
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    /**
     * 締め処理履歴を記録
     */
    private function createClosingHistory($closingRecordId, $action, $userId, $notes = null)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO closing_process_history (
                    closing_record_id, action, user_id, notes, created_at
                ) VALUES (
                    :closing_record_id, :action, :user_id, :notes, NOW()
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'closing_record_id' => $closingRecordId,
            'action' => $action,
            'user_id' => $userId,
            'notes' => $notes
        ]);
    }
}
?>