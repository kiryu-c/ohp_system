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
        
        // 役務種別の利用可能性をチェック
        $availableServiceTypes = $this->getAvailableServiceTypes($contracts);
        
        // フィルターパラメータを取得
        $filterServiceType = $_GET['service_type'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $filterSearch = $_GET['search'] ?? '';
        
        // 各契約の締め状況を取得
        $closingData = [];
        foreach ($contracts as $contract) {
            $closingRecord = $this->getClosingRecord($contract['id'], $currentMonth);
            $unClosedRecordsCount = $this->getUnClosedRecordsCount($contract['id'], $currentMonth);
            $unapprovedRecordsCount = $this->getUnapprovedRecordsCount($contract['id'], $currentMonth);
            $unapprovedTravelExpensesCount = $this->getUnapprovedTravelExpensesCount($contract['id'], $currentMonth);
            
            // フィルタリング処理
            // 1. 役務種別フィルター
            if (!empty($filterServiceType)) {
                $hasServiceType = $this->hasServiceTypeInMonth($contract['id'], $currentMonth, $filterServiceType, $db);
                if (!$hasServiceType) {
                    continue; // この契約はスキップ
                }
            }
            
            // 2. 締め状況フィルター
            if (!empty($filterStatus)) {
                $contractStatus = $this->getContractStatus($closingRecord);
                if ($contractStatus !== $filterStatus) {
                    continue; // この契約はスキップ
                }
            }
            
            // 3. 検索フリーテキストフィルター
            if (!empty($filterSearch)) {
                $searchMatch = $this->matchesSearchText($contract, $filterSearch, $userType);
                if (!$searchMatch) {
                    continue; // この契約はスキップ
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
        
        ob_start();
        include __DIR__ . '/../views/closing/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
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
        $weeklyDays = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($weeklyDays)) {
            return 0;
        }
        
        // 祝日リストを取得（exclude_holidaysが1の場合のみ）
        $holidays = [];
        if ($excludeHolidays) {
            $holidays = $this->getJapaneseHolidays($year);
        }
        
        // 非訪問日を取得
        $nonVisitDays = $this->getContractNonVisitDays($contractId, $year, $month, $db);
        
        $totalVisits = 0;
        
        // 各曜日について、その月の訪問可能日数を計算
        foreach ($weeklyDays as $dayOfWeek) {
            $visitCount = $this->countWeekdaysExcludingHolidaysAndNonVisitDays(
                $year, $month, $dayOfWeek, $holidays, $nonVisitDays
            );
            $totalVisits += $visitCount;
        }
        
        return $totalVisits;
    }

    /**
     * 日本の祝日を取得
     */
    private function getJapaneseHolidays($year)
    {
        // helpers.phpのget_japanese_holidays関数を使用
        return get_japanese_holidays($year);
    }

    /**
     * 契約別非訪問日を取得
     */
    private function getContractNonVisitDays($contractId, $year, $month, $db)
    {
        $sql = "SELECT non_visit_date, is_recurring, recurring_month, recurring_day, year
                FROM contract_non_visit_days 
                WHERE contract_id = :contract_id 
                AND is_active = 1
                AND (
                    (year = :year AND MONTH(non_visit_date) = :month)
                    OR 
                    (is_recurring = 1 AND year < :year2 AND recurring_month = :month2)
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'year2' => $year,
            'month' => $month,
            'month2' => $month
        ]);
        
        $nonVisitDays = [];
        
        while ($row = $stmt->fetch()) {
            if ($row['is_recurring'] && $row['year'] < $year) {
                // 繰り返し設定の場合、今年の日付に読み替え
                $nonVisitDate = sprintf('%04d-%02d-%02d', $year, $row['recurring_month'], $row['recurring_day']);
            } else {
                $nonVisitDate = $row['non_visit_date'];
            }
            
            $nonVisitDays[] = $nonVisitDate;
        }
        
        return $nonVisitDays;
    }

    /**
     * 指定した年月の特定の曜日が祝日・非訪問日を除いて何回あるかを計算
     */
    private function countWeekdaysExcludingHolidaysAndNonVisitDays($year, $month, $dayOfWeek, $holidays, $nonVisitDays)
    {
        // 月の最初の日と最後の日を取得
        $firstDay = new DateTime("{$year}-{$month}-01");
        $lastDay = new DateTime($firstDay->format('Y-m-t'));
        
        $count = 0;
        $currentDate = clone $firstDay;
        
        // 月の各日をチェック
        while ($currentDate <= $lastDay) {
            // 指定された曜日かチェック（PHPの曜日: 1=月曜日, 7=日曜日）
            if ($currentDate->format('N') == $dayOfWeek) {
                $dateStr = $currentDate->format('Y-m-d');
                
                // 祝日でも非訪問日でもない場合のみカウント
                if (!isset($holidays[$dateStr]) && !in_array($dateStr, $nonVisitDays)) {
                    $count++;
                }
            }
            $currentDate->add(new DateInterval('P1D'));
        }
        
        return $count;
    }

    /**
     * シミュレーション実行
     */
    private function executeSimulation($contractId, $closingPeriod, $regularBillingMethod = 'contract_hours')
    {
        $db = Database::getInstance()->getConnection();
        
        // 既存の締め記録があるか確認
        $existingRecord = $this->getClosingRecord($contractId, $closingPeriod);
        $isFinalized = ($existingRecord && $existingRecord['status'] === 'finalized');
        
        // 【追加】確定済みの場合は保存された契約情報を使用
        if ($isFinalized && !empty($existingRecord['contract_snapshot'])) {
            $contract = json_decode($existingRecord['contract_snapshot'], true);
            // 会社名・支店名は最新のものを取得
            $currentInfoSql = "SELECT comp.name as company_name, b.name as branch_name
                            FROM contracts c
                            JOIN companies comp ON c.company_id = comp.id
                            JOIN branches b ON c.branch_id = b.id
                            WHERE c.id = :contract_id";
            $stmt = $db->prepare($currentInfoSql);
            $stmt->execute(['contract_id' => $contractId]);
            $currentInfo = $stmt->fetch();
            if ($currentInfo) {
                $contract['company_name'] = $currentInfo['company_name'];
                $contract['branch_name'] = $currentInfo['branch_name'];
            }
        } else {
            // 未確定の場合は現在の契約情報を取得
            $contractSql = "SELECT c.*, comp.name as company_name, b.name as branch_name
                            FROM contracts c
                            JOIN companies comp ON c.company_id = comp.id
                            JOIN branches b ON c.branch_id = b.id
                            WHERE c.id = :contract_id";
            $stmt = $db->prepare($contractSql);
            $stmt->execute(['contract_id' => $contractId]);
            $contract = $stmt->fetch();
        }
        
        if (!$contract) {
            return [
                'service_breakdown' => [],
                'summary' => $this->getEmptySummary(),
                'total_amount' => 0,
                'tax_amount' => 0,
                'taxable_amount' => 0,
                'taxable_amount_with_tax' => 0,
                'total_with_tax' => 0,
                'travel_expenses' => [],
                'travel_expense_total' => 0,
                'regular_billing_method' => $regularBillingMethod
            ];
        }

        // 確定済みの場合、既存の請求方法を使用
        if ($isFinalized && $existingRecord['regular_billing_method']) {
            $regularBillingMethod = $existingRecord['regular_billing_method'];
        }
        
        // 確定済みの場合はis_closedの条件を変更
        $serviceClosedCondition = $isFinalized ? "1=1" : "(sr.is_closed = 0 OR sr.is_closed IS NULL)";
        $travelClosedCondition = $isFinalized ? "1=1" : "(te.is_closed = 0 OR te.is_closed IS NULL)";

        // 【変更】月の訪問時間を計算(確定済みの場合は保存された値を使用)
        if ($isFinalized && isset($existingRecord['contract_hours'])) {
            $contractHours = (float)$existingRecord['contract_hours'];
        } else {
            $contractHours = $this->calculateMonthlyContractHours($contract, $closingPeriod, $db);
        }
        
        // 【修正】承認済み「または」未承認の役務記録を日付順で取得
        // 確定済みの場合は承認済みのみ、未確定の場合は未承認も含める
        if ($isFinalized) {
            $serviceStatusCondition = "sr.status IN ('approved', 'finalized')";
        } else {
            $serviceStatusCondition = "sr.status IN ('pending', 'approved', 'finalized')";
        }
        
        $serviceRecordsSql = "SELECT * FROM service_records sr
                            WHERE sr.contract_id = :contract_id
                            AND DATE_FORMAT(sr.service_date, '%Y-%m') = :closing_period
                            AND {$serviceStatusCondition}
                            AND {$serviceClosedCondition}
                            ORDER BY sr.service_date, sr.created_at";
        
        $stmt = $db->prepare($serviceRecordsSql);
        $stmt->execute([
            'contract_id' => $contractId,
            'closing_period' => $closingPeriod
        ]);
        $serviceRecords = $stmt->fetchAll();
        
        // 【修正】承認済み「または」未承認の交通費を取得
        if ($isFinalized) {
            $travelStatusCondition = "te.status IN ('approved', 'finalized')";
        } else {
            $travelStatusCondition = "te.status IN ('pending', 'approved', 'finalized')";
        }
        
        $travelExpensesSql = "SELECT te.*, sr.service_date
                            FROM travel_expenses te
                            JOIN service_records sr ON te.service_record_id = sr.id
                            WHERE sr.contract_id = :contract_id
                            AND DATE_FORMAT(sr.service_date, '%Y-%m') = :closing_period
                            AND {$travelStatusCondition}
                            AND {$travelClosedCondition}
                            ORDER BY sr.service_date, te.created_at";
        
        $stmt = $db->prepare($travelExpensesSql);
        $stmt->execute([
            'contract_id' => $contractId,
            'closing_period' => $closingPeriod
        ]);
        $travelExpenses = $stmt->fetchAll();
        
        
        
        // 専属登録料の取得
        $exclusiveRegistrationFee = 0;
        if (!empty($contract['exclusive_registration_fee']) && (float)$contract['exclusive_registration_fee'] > 0) {
            $exclusiveRegistrationFee = (float)$contract['exclusive_registration_fee'];
        }
        
        // 役務がなく、交通費もなく、専属登録料もない場合は0円で返す
        if (empty($serviceRecords) && empty($travelExpenses) && $exclusiveRegistrationFee == 0) {
            return [
                'service_breakdown' => [],
                'summary' => $this->getEmptySummary(),
                'total_amount' => 0,
                'tax_rate' => 0.10,
                'tax_amount' => 0,
                'total_with_tax' => 0,
                'taxable_amount' => 0,
                'taxable_amount_with_tax' => 0,
                'contract_hours' => $contractHours,
                'used_regular_hours' => 0,
                'actual_regular_hours' => 0,
                'travel_expenses' => [],
                'travel_expense_total' => 0,
                'exclusive_registration_fee' => 0,
                'regular_billing_method' => $regularBillingMethod
            ];
        }
        
        // 15分単位切り上げ関数
        $ceilToQuarter = function($hours) {
            return ceil($hours * 4) / 4;
        };
        
        $breakdown = [];
        $summary = [
            'regular_hours' => 0,
            'regular_amount' => 0,
            'regular_extension_hours' => 0,
            'regular_extension_amount' => 0,
            'emergency_hours' => 0,
            'emergency_amount' => 0,
            'document_count' => 0,
            'document_amount' => 0,
            'remote_consultation_count' => 0,
            'remote_consultation_amount' => 0,
            'spot_hours' => 0,
            'spot_amount' => 0,
            'other_count' => 0,
            'other_amount' => 0
        ];
        
        if (!empty($serviceRecords)) {
            // スポット契約かどうかをチェック
            $isSpotContract = ($contract['visit_frequency'] === 'spot');
            
            // 定期訪問の実働時間（スポット契約では0）
            $totalRegularActualHours = 0;
            
            if ($isSpotContract) {
                // スポット役務の時間を合算
                $totalSpotHours = 0;
                $spotRecords = [];
                
                foreach ($serviceRecords as $record) {
                    if ($record['service_type'] === 'spot') {
                        $spotRecords[] = $record;
                        $totalSpotHours += (float)$record['service_hours'];
                    }
                }
                
                // スポット役務の記録を追加
                foreach ($spotRecords as $record) {
                    $breakdown[] = [
                        'service_record' => $record,
                        'original_hours' => (float)$record['service_hours'],
                        'billing_hours' => 0,
                        'billing_service_type' => 'spot',
                        'billing_amount' => 0
                    ];
                }
                
                // 合算した時間を15分単位で切り上げて請求
                if ($totalSpotHours > 0) {
                    $spotBillingHours = $ceilToQuarter($totalSpotHours);
                    $spotUnits = ceil($spotBillingHours / 0.25);
                    $spotAmount = $spotUnits * (float)$contract['spot_rate'];
                    
                    $summary['spot_hours'] = $spotBillingHours;
                    $summary['spot_amount'] = $spotAmount;
                }
            } else {
            // 定期訪問の実働時間合計を計算
            $regularRecords = [];

            foreach ($serviceRecords as $record) {
                if ($record['service_type'] === 'regular') {
                    $regularRecords[] = $record;
                    $totalRegularActualHours += (float)$record['service_hours'];
                }
            }

            // 定期訪問の請求時間を計算
            if (empty($regularRecords)) {
                // 定期訪問の役務がない場合は請求時間を0とする
                $regularBillingHours = 0;
                $extensionBillingHours = 0;
            } else {
                // 請求方法に応じて請求時間を決定
                if ($regularBillingMethod === 'actual_hours') {
                    // 実働時間で請求
                    if ($totalRegularActualHours <= $contractHours) {
                        $regularBillingHours = $totalRegularActualHours;
                        $extensionBillingHours = 0;
                    } else {
                        $regularBillingHours = $contractHours;
                        $overHours = $totalRegularActualHours - $contractHours;
                        $extensionBillingHours = $ceilToQuarter($overHours);
                    }
                } else {
                    // 契約時間で請求(デフォルト)
                    if ($totalRegularActualHours < $contractHours) {
                        $regularBillingHours = $contractHours;
                        $extensionBillingHours = 0;
                    } else {
                        $regularBillingHours = $contractHours;
                        $overHours = $totalRegularActualHours - $contractHours;
                        $extensionBillingHours = $ceilToQuarter($overHours);
                    }
                }
            }
            
            // 定期訪問の役務記録を追加 (実働時間を表示用に保存)
            foreach ($regularRecords as $record) {
                $breakdown[] = [
                    'service_record' => $record,
                    'original_hours' => (float)$record['service_hours'],
                    'billing_hours' => 0,
                    'billing_service_type' => 'regular',
                    'billing_amount' => 0
                ];
            }
            
            // 定期訪問の請求情報を追加
            if ($regularBillingHours > 0) {
                $regularAmount = $regularBillingHours * (float)$contract['regular_visit_rate'];
                $summary['regular_hours'] = $regularBillingHours;
                $summary['regular_amount'] = $regularAmount;
            }
            
            // 定期延長の請求情報を追加
            if ($extensionBillingHours > 0) {
                $extensionUnits = ceil($extensionBillingHours / 0.25);
                $extensionAmount = $extensionUnits * (float)$contract['regular_extension_rate'];
                $summary['regular_extension_hours'] = $extensionBillingHours;
                $summary['regular_extension_amount'] = $extensionAmount;
            }
            
            }
            
            // その他の役務記録を処理
            foreach ($serviceRecords as $record) {
                $originalHours = (float)$record['service_hours'];
                
                switch ($record['service_type']) {
                    case 'regular':
                        // 既に上で処理済み
                        break;
                    
                    case 'spot':
                        // スポット契約の場合は既に上で処理済み
                        break;
                        
                    case 'emergency':
                        $billingHours = $ceilToQuarter($originalHours);
                        $extensionUnits = ceil($billingHours / 0.25);
                        $billingAmount = $extensionUnits * (float)$contract['emergency_visit_rate'];
                        $summary['emergency_hours'] += $billingHours;
                        $summary['emergency_amount'] += $billingAmount;
                        
                        $breakdown[] = [
                            'service_record' => $record,
                            'original_hours' => $originalHours,
                            'billing_hours' => $billingHours,
                            'billing_service_type' => 'emergency',
                            'billing_amount' => $billingAmount
                        ];
                        break;

                    case 'document':
                        $billingAmount = (float)$contract['document_consultation_rate'];
                        $summary['document_count'] += 1;
                        $summary['document_amount'] += $billingAmount;
                        
                        $breakdown[] = [
                            'service_record' => $record,
                            'original_hours' => 0,
                            'billing_hours' => 0,
                            'billing_service_type' => 'document',
                            'billing_amount' => $billingAmount
                        ];
                        break;
                        
                    case 'remote_consultation':
                        $billingAmount = (float)$contract['document_consultation_rate'];
                        $summary['remote_consultation_count'] += 1;
                        $summary['remote_consultation_amount'] += $billingAmount;
                        
                        $breakdown[] = [
                            'service_record' => $record,
                            'original_hours' => 0,
                            'billing_hours' => 0,
                            'billing_service_type' => 'remote_consultation',
                            'billing_amount' => $billingAmount
                        ];
                        break;
                    
                    case 'other':
                        // その他役務はdirect_billing_amountを使用
                        $billingAmount = (float)$record['direct_billing_amount'];
                        $summary['other_count'] += 1;
                        $summary['other_amount'] += $billingAmount;
                        
                        $breakdown[] = [
                            'service_record' => $record,
                            'original_hours' => 0,
                            'billing_hours' => 0,
                            'billing_service_type' => 'other',
                            'billing_amount' => $billingAmount
                        ];
                        break;
                }
            }
        }
        
        // 交通費の処理
        $travelExpenseTotal = 0;
        foreach ($travelExpenses as $expense) {
            $travelExpenseTotal += (float)$expense['amount'];
        }
        
        
        $totalAmount = array_sum([
            $summary['regular_amount'],
            $summary['regular_extension_amount'],
            $summary['emergency_amount'],
            $summary['document_amount'],
            $summary['remote_consultation_amount'],
            $summary['spot_amount'],
            $summary['other_amount'],
            $exclusiveRegistrationFee,
            $travelExpenseTotal
        ]);
        
        // 消費税計算
        // invoice_settingsから消費税率を取得
        $settingsSql = "SELECT tax_rate FROM invoice_settings LIMIT 1";
        $stmt = $db->prepare($settingsSql);
        $stmt->execute();
        $settingsRow = $stmt->fetch();
        $taxRate = $settingsRow ? (float)$settingsRow['tax_rate'] : 0.10;

        $taxAmount = 0;
        $totalWithTax = $totalAmount;

        // 課税対象金額(交通費を除く)
        $taxableAmount = array_sum([
            $summary['regular_amount'],
            $summary['regular_extension_amount'],
            $summary['emergency_amount'],
            $summary['document_amount'],
            $summary['remote_consultation_amount'],
            $summary['spot_amount'],
            $summary['other_amount'],
            $exclusiveRegistrationFee
        ]);

        if ($contract['tax_type'] === 'exclusive') {
            // 外税の場合:税抜金額に消費税を加算
            $taxAmount = floor($taxableAmount * $taxRate);
            $totalWithTax = $totalAmount + $taxAmount;
            // 外税の場合、10%対象には消費税を含める
            $taxableAmountWithTax = $taxableAmount + $taxAmount;
        } else {
            // 内税の場合:税込金額から消費税を逆算
            $taxAmount = floor($taxableAmount * $taxRate / (1 + $taxRate));
            $totalWithTax = $totalAmount;
            $taxableAmountWithTax = $taxableAmount;
        }
        
        return [
            'service_breakdown' => $breakdown,
            'summary' => $summary,
            'total_amount' => $totalAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_with_tax' => $totalWithTax,
            'taxable_amount' => $taxableAmount,
            'taxable_amount_with_tax' => $taxableAmountWithTax,
            'contract_hours' => $contractHours,
            'used_regular_hours' => $totalRegularActualHours ?? 0,
            'actual_regular_hours' => $totalRegularActualHours ?? 0,
            'travel_expenses' => $travelExpenses ?? [],
            'travel_expense_total' => $travelExpenseTotal ?? 0,
            'regular_billing_method' => $regularBillingMethod,
            'exclusive_registration_fee' => $exclusiveRegistrationFee,
            'can_choose_billing_method' => (!$isFinalized && $totalRegularActualHours < $contractHours && $totalRegularActualHours > 0)
        ];
    }

    /**
     * 未承認の交通費数を取得
     */
    private function getUnapprovedTravelExpensesCount($contractId, $closingPeriod)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) FROM travel_expenses te
                JOIN service_records sr ON te.service_record_id = sr.id
                WHERE sr.contract_id = :contract_id
                AND DATE_FORMAT(sr.service_date, '%Y-%m') = :closing_period
                AND te.status IN ('pending', 'rejected')
                AND (te.is_closed = 0 OR te.is_closed IS NULL)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId, 'closing_period' => $closingPeriod]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * 月の訪問時間を計算
     */
    private function calculateMonthlyContractHours($contract, $closingPeriod, $db)
    {
        if ($contract['visit_frequency'] === 'weekly') {
            // 毎週契約の場合、月間訪問回数を計算
            list($year, $month) = explode('-', $closingPeriod);
            $visitCount = $this->calculateWeeklyVisitCount($contract['id'], (int)$year, (int)$month, $db);
            $singleVisitHours = (float)$contract['regular_visit_hours'];
            return $visitCount * $singleVisitHours;
        } else {
            // 毎月または隔月契約の場合、契約時間をそのまま使用
            return (float)$contract['regular_visit_hours'];
        }
    }

    /**
     * 締め処理確定(産業医が実行、請求書は未作成)
     */
    public function finalize($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 産業医または管理者のみ実行可能
        if ($userType === 'company') {
            $this->setFlash('error', '締め処理の確定は産業医または管理者のみが実行できます。');
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
        $regularBillingMethod = $_POST['regular_billing_method'] ?? 'contract_hours';
        $doctorComment = trim($_POST['doctor_comment'] ?? '');
        $extensionReason = trim($_POST['extension_reason'] ?? '');
        
        if (!$contractId || !$closingPeriod) {
            $this->setFlash('error', '必要なパラメータが不足しています。');
            redirect('closing');
        }
        
        // 請求方法のバリデーション
        if (!in_array($regularBillingMethod, ['contract_hours', 'actual_hours'])) {
            $regularBillingMethod = 'contract_hours';
        }
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            
            // 権限チェック
            $contract = $this->getContractWithPermissionCheck($contractId);
            if (!$contract) {
                throw new Exception('この契約の締め処理を実行する権限がありません。');
            }
            
            // 未承認の役務記録がないかチェック
            $unapprovedRecordsCount = $this->getUnapprovedRecordsCount($contractId, $closingPeriod);
            if ($unapprovedRecordsCount > 0) {
                throw new Exception("未承認の役務記録が{$unapprovedRecordsCount}件あります。全ての役務記録を承認してから締め処理を実行してください。");
            }
            
            // 【追加】未承認の交通費がないかチェック
            $unapprovedTravelExpensesCount = $this->getUnapprovedTravelExpensesCount($contractId, $closingPeriod);
            if ($unapprovedTravelExpensesCount > 0) {
                throw new Exception("未承認の交通費が{$unapprovedTravelExpensesCount}件あります。全ての交通費を承認してから締め処理を実行してください。");
            }
            
            // 既存の締め記録をチェック
            $existingRecord = $this->getClosingRecord($contractId, $closingPeriod);
            if ($existingRecord && $existingRecord['company_approved'] == 1) {
                throw new Exception('既に企業承認済みの締め処理は変更できません。');
            }
            
            // シミュレーション再実行(選択された請求方法で)
            $simulation = $this->executeSimulation($contractId, $closingPeriod, $regularBillingMethod);
            
            // 月次締め記録の作成または更新(請求書PDFは未作成)
            $closingRecord = $this->createOrUpdateClosingRecord($contractId, $closingPeriod, $contract, $simulation, $userId, false, $doctorComment, $extensionReason);
            
            // 役務記録と交通費の更新
            $this->updateServiceRecordsAndTravelExpenses($simulation, $closingPeriod, $userId);

            // 履歴記録
            $historyComment = '締め処理を確定しました(企業承認待ち)';
            if ($doctorComment) {
                $historyComment .= " - コメント: " . mb_substr($doctorComment, 0, 50);
            }
            $this->createClosingHistory($closingRecord['id'], 'finalize', $userId, $historyComment, null, $simulation);
            
            $db->commit();
            
            $this->setFlash('success', '締め処理を確定しました。企業の承認をお待ちください。');
            redirect('closing');
            
        } catch (Exception $e) {
            $db->rollback();
            $this->setFlash('error', '締め処理に失敗しました: ' . $e->getMessage());
            redirect("closing/simulation/{$contractId}?period={$closingPeriod}");
        }
    }
    
    /**
     * 締め処理の取り消し
     */
    public function reopen($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 産業医または管理者のみ実行可能
        if ($userType === 'company') {
            $this->setFlash('error', '締め処理の取り消しは産業医または管理者のみが実行できます。');
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
            
            // 権限チェック
            $contract = $this->getContractWithPermissionCheck($contractId);
            if (!$contract) {
                throw new Exception('この契約の締め処理を実行する権限がありません。');
            }
            
            // 既存の締め記録を取得
            $closingRecord = $this->getClosingRecord($contractId, $closingPeriod);
            if (!$closingRecord) {
                throw new Exception('締め記録が見つかりません。');
            }
            
            if ($closingRecord['status'] !== 'finalized') {
                throw new Exception('確定済みの締め記録のみ取り消し可能です。');
            }
            
            if ($closingRecord['company_approved'] == 1) {
                throw new Exception('企業承認済みの締め処理は取り消しできません。');
            }
            
            // 請求書PDFファイルのパスを保存(削除用)
            $pdfPath = $closingRecord['invoice_pdf_path'] ?? null;
            
            // 履歴記録(削除前に記録)
            $this->createClosingHistory($closingRecord['id'], 'reopen', $userId, '締め処理を取り消し、未処理状態に戻しました');
            
            // 締め記録を完全に削除(未処理状態にするため)
            $deleteClosingSql = "DELETE FROM monthly_closing_records WHERE id = :id";
            $stmt = $db->prepare($deleteClosingSql);
            $stmt->execute(['id' => $closingRecord['id']]);
            
            // 関連する役務記録の締めフラグを解除
            $updateServiceSql = "UPDATE service_records 
                                SET is_closed = 0, 
                                    closed_at = NULL, 
                                    closed_by = NULL,
                                    billing_service_type = NULL,
                                    billing_hours = NULL,
                                    billing_amount = NULL,
                                    closing_period = NULL,
                                    status = 'approved'
                                WHERE contract_id = :contract_id 
                                AND DATE_FORMAT(service_date, '%Y-%m') = :closing_period";
            
            $stmt = $db->prepare($updateServiceSql);
            $stmt->execute([
                'contract_id' => $contractId,
                'closing_period' => $closingPeriod
            ]);
            
            // 関連する交通費の締めフラグを解除
            $updateTravelSql = "UPDATE travel_expenses te
                            JOIN service_records sr ON te.service_record_id = sr.id
                            SET te.is_closed = 0,
                                te.closed_at = NULL,
                                te.closed_by = NULL,
                                te.closing_period = NULL,
                                te.status = 'approved'
                            WHERE sr.contract_id = :contract_id 
                            AND DATE_FORMAT(sr.service_date, '%Y-%m') = :closing_period";
            
            $stmt = $db->prepare($updateTravelSql);
            $stmt->execute([
                'contract_id' => $contractId,
                'closing_period' => $closingPeriod
            ]);
            
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
            
            $this->setFlash('success', '締め処理を取り消しました。対象期間は未処理状態に戻りました。');
            redirect('closing');
            
        } catch (Exception $e) {
            $db->rollback();
            $this->setFlash('error', '締め処理の取り消しに失敗しました:' . $e->getMessage());
            redirect('closing');
        }
    }
    
    /**
     * 企業による締め処理の承認
     */
    public function companyApprove($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 企業ユーザーのみ実行可能
        if ($userType !== 'company') {
            if ($this->isAjaxRequest()) {
                $this->errorResponse('締め処理の承認は企業ユーザーのみが実行できます。', 403);
            }
            $this->setFlash('error', '締め処理の承認は企業ユーザーのみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            if ($this->isAjaxRequest()) {
                $this->errorResponse('セキュリティトークンが無効です。ページを再読み込みしてください。', 403);
            }
            redirect('closing');
        }
        
        // closing_idから締め記録を取得する方法と、contract_id + closing_periodの両方に対応
        $closingId = (int)($_POST['closing_id'] ?? 0);
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            $closingRecord = null;
            
            if ($closingId > 0) {
                // closing_idが指定されている場合(企業ダッシュボードからの呼び出し)
                // 締め記録を取得
                $sql = "SELECT mcr.*, c.company_id, c.branch_id 
                        FROM monthly_closing_records mcr
                        JOIN contracts c ON mcr.contract_id = c.id
                        WHERE mcr.id = :closing_id";
                $stmt = $db->prepare($sql);
                $stmt->execute(['closing_id' => $closingId]);
                $closingRecord = $stmt->fetch();
                
                if (!$closingRecord) {
                    throw new Exception('締め記録が見つかりません。');
                }
                
                // 権限チェック(企業ユーザーが自分の会社の拠点に紐づく契約か確認)
                $permissionSql = "SELECT COUNT(*) as cnt 
                                  FROM user_branch_mappings ubm
                                  WHERE ubm.user_id = :user_id 
                                  AND ubm.branch_id = :branch_id
                                  AND ubm.is_active = 1";
                $permissionStmt = $db->prepare($permissionSql);
                $permissionStmt->execute([
                    'user_id' => $userId,
                    'branch_id' => $closingRecord['branch_id']
                ]);
                $permission = $permissionStmt->fetch();
                
                if (!$permission || $permission['cnt'] == 0) {
                    throw new Exception('この契約の締め処理を承認する権限がありません。');
                }
                
            } else {
                // 従来の方式(contract_id + closing_period)
                if ($contractId === null) {
                    $contractId = (int)($_POST['contract_id'] ?? 0);
                } else {
                    $contractId = (int)$contractId;
                }
                
                $closingPeriod = $_POST['closing_period'] ?? '';
                
                if (!$contractId || !$closingPeriod) {
                    if ($this->isAjaxRequest()) {
                        $this->errorResponse('必要なパラメータが不足しています。', 400);
                    }
                    $this->setFlash('error', '必要なパラメータが不足しています。');
                    $db->rollback();
                    redirect('closing');
                }
                
                // 権限チェック
                $contract = $this->getContractWithPermissionCheck($contractId);
                if (!$contract) {
                    throw new Exception('この契約の締め処理を承認する権限がありません。');
                }
                
                // 締め記録を取得
                $closingRecord = $this->getClosingRecord($contractId, $closingPeriod);
                if (!$closingRecord) {
                    throw new Exception('締め記録が見つかりません。');
                }
            }
            
            if ($closingRecord['status'] !== 'finalized') {
                throw new Exception('確定済みの締め記録のみ承認できます。');
            }
            
            if ($closingRecord['company_approved'] == 1) {
                throw new Exception('既に承認済みの締め処理です。');
            }
            
            // 企業承認フラグを更新
            $sql = "UPDATE monthly_closing_records 
                    SET company_approved = 1,
                        company_approved_at = NOW(),
                        company_approved_by = :approved_by,
                        company_rejected_at = NULL,
                        company_rejected_by = NULL,
                        company_rejection_reason = NULL,
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
            $this->createClosingHistory($closingRecord['id'], 'company_approve', $userId, '企業が締め処理を承認しました');
            
            $db->commit();
            
            // トランザクション完了後に請求書PDF自動生成
            try {
                require_once __DIR__ . '/InvoiceController.php';
                $invoiceController = new InvoiceController();
                $pdfPath = $invoiceController->generate($closingRecord['id']);
                
                // PDFパスをデータベースに保存(別トランザクション)
                if ($pdfPath) {
                    $updatePdfSql = "UPDATE monthly_closing_records 
                                    SET invoice_pdf_path = :pdf_path,
                                        updated_at = NOW()
                                    WHERE id = :id";
                    $stmt = $db->prepare($updatePdfSql);
                    $stmt->execute([
                        'id' => $closingRecord['id'],
                        'pdf_path' => $pdfPath
                    ]);
                    
                    error_log("Invoice PDF generated and path saved successfully: {$pdfPath}");
                }
                
            } catch (Exception $e) {
                // PDFエラーは致命的でないのでログのみ
                error_log("Invoice PDF generation failed: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            // AJAXリクエストの場合はJSON応答
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => '締め処理を承認しました。請求書が自動生成されました。'
                ]);
                exit;
            }
            
            $this->setFlash('success', '締め処理を承認しました。請求書が自動生成されました。');
            redirect('closing');
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Company approve failed: " . $e->getMessage());
            
            // AJAXリクエストの場合はJSON応答
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => '締め処理の承認に失敗しました: ' . $e->getMessage()
                ]);
                exit;
            }
            
            $this->setFlash('error', '締め処理の承認に失敗しました: ' . $e->getMessage());
            redirect('closing');
        }
    }

    /**
     * 企業による締め処理の差戻し
     */
    public function companyReject($contractId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 企業ユーザーのみ実行可能
        if ($userType !== 'company') {
            if ($this->isAjaxRequest()) {
                $this->errorResponse('締め処理の差戻しは企業ユーザーのみが実行できます。', 403);
            }
            $this->setFlash('error', '締め処理の差戻しは企業ユーザーのみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            if ($this->isAjaxRequest()) {
                $this->errorResponse('セキュリティトークンが無効です。ページを再読み込みしてください。', 403);
            }
            redirect('closing');
        }
        
        // closing_idから締め記録を取得する方法と、contract_id + closing_periodの両方に対応
        $closingId = (int)($_POST['closing_id'] ?? 0);
        $rejectionReason = trim($_POST['rejection_reason'] ?? trim($_POST['reject_reason'] ?? ''));
        
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            $userId = Session::get('user_id');
            $closingRecord = null;
            
            if ($closingId > 0) {
                // closing_idが指定されている場合(企業ダッシュボードからの呼び出し)
                // 締め記録を取得
                $sql = "SELECT mcr.*, c.company_id, c.branch_id 
                        FROM monthly_closing_records mcr
                        JOIN contracts c ON mcr.contract_id = c.id
                        WHERE mcr.id = :closing_id";
                $stmt = $db->prepare($sql);
                $stmt->execute(['closing_id' => $closingId]);
                $closingRecord = $stmt->fetch();
                
                if (!$closingRecord) {
                    throw new Exception('締め記録が見つかりません。');
                }
                
                // 権限チェック(企業ユーザーが自分の会社の拠点に紐づく契約か確認)
                $permissionSql = "SELECT COUNT(*) as cnt 
                                  FROM user_branch_mappings ubm
                                  WHERE ubm.user_id = :user_id 
                                  AND ubm.branch_id = :branch_id
                                  AND ubm.is_active = 1";
                $permissionStmt = $db->prepare($permissionSql);
                $permissionStmt->execute([
                    'user_id' => $userId,
                    'branch_id' => $closingRecord['branch_id']
                ]);
                $permission = $permissionStmt->fetch();
                
                if (!$permission || $permission['cnt'] == 0) {
                    throw new Exception('この契約の締め処理を差戻す権限がありません。');
                }
                
            } else {
                // 従来の方式(contract_id + closing_period)
                if ($contractId === null) {
                    $contractId = (int)($_POST['contract_id'] ?? 0);
                } else {
                    $contractId = (int)$contractId;
                }
                
                $closingPeriod = $_POST['closing_period'] ?? '';
                
                if (!$contractId || !$closingPeriod) {
                    if ($this->isAjaxRequest()) {
                        $this->errorResponse('必要なパラメータが不足しています。', 400);
                    }
                    $this->setFlash('error', '必要なパラメータが不足しています。');
                    $db->rollback();
                    redirect('closing');
                }
                
                // 権限チェック
                $contract = $this->getContractWithPermissionCheck($contractId);
                if (!$contract) {
                    throw new Exception('この契約の締め処理を差戻す権限がありません。');
                }
                
                // 締め記録を取得
                $closingRecord = $this->getClosingRecord($contractId, $closingPeriod);
                if (!$closingRecord) {
                    throw new Exception('締め記録が見つかりません。');
                }
            }
            
            if (empty($rejectionReason)) {
                if ($this->isAjaxRequest()) {
                    $this->errorResponse('差戻し理由を入力してください。', 400);
                }
                $this->setFlash('error', '差戻し理由を入力してください。');
                $db->rollback();
                redirect('closing');
            }
            
            if ($closingRecord['status'] !== 'finalized') {
                throw new Exception('確定済みの締め記録のみ差戻しできます。');
            }
            
            if ($closingRecord['company_approved'] == 1) {
                throw new Exception('承認済みの締め処理は差戻しできません。');
            }
            
            // 差戻し情報を記録
            $sql = "UPDATE monthly_closing_records 
                    SET company_rejected_at = NOW(),
                        company_rejected_by = :rejected_by,
                        company_rejection_reason = :rejection_reason,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'id' => $closingRecord['id'],
                'rejected_by' => $userId,
                'rejection_reason' => $rejectionReason
            ]);
            
            // 履歴記録
            $this->createClosingHistory($closingRecord['id'], 'company_reject', $userId, '企業が締め処理を差戻しました: ' . $rejectionReason);
            
            $db->commit();
            
            // AJAXリクエストの場合はJSON応答
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => '締め処理を差戻しました。産業医に修正を依頼してください。'
                ]);
                exit;
            }
            
            $this->setFlash('success', '締め処理を差戻しました。産業医に修正を依頼してください。');
            redirect('closing');
            
        } catch (Exception $e) {
            $db->rollback();
            
            // AJAXリクエストの場合はJSON応答
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => '締め処理の差戻しに失敗しました: ' . $e->getMessage()
                ]);
                exit;
            }
            
            $this->setFlash('error', '締め処理の差戻しに失敗しました: ' . $e->getMessage());
            redirect('closing');
        }
    }


    /**
     * 締め処理詳細表示
     */
    public function detail($closingRecordId = null)
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 企業ユーザーはアクセス不可
        if ($userType === 'company') {
            $this->setFlash('error', 'この機能は産業医または管理者のみが利用できます。');
            redirect('closing');
        }
        
        if ($closingRecordId === null) {
            $closingRecordId = (int)($_GET['id'] ?? 0);
        } else {
            $closingRecordId = (int)$closingRecordId;
        }
        
        if (!$closingRecordId) {
            $this->setFlash('error', '締め記録IDが指定されていません。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        
        // 締め記録から契約IDと期間を取得
        $sql = "SELECT contract_id, closing_period FROM monthly_closing_records WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $closingRecordId]);
        $closingRecord = $stmt->fetch();
        
        if (!$closingRecord) {
            $this->setFlash('error', '締め記録が見つかりません。');
            redirect('closing');
        }
        
        // 権限チェック
        $userId = Session::get('user_id');
        $contract = $this->getContractWithPermissionCheck($closingRecord['contract_id']);
        
        if (!$contract) {
            $this->setFlash('error', 'この締め記録を表示する権限がありません。');
            redirect('closing');
        }
        
        // シミュレーション画面にリダイレクト（確定済みなので確定ボタンは表示されない）
        redirect("closing/simulation/{$closingRecord['contract_id']}?period={$closingRecord['closing_period']}");
    }
    
    // ヘルパーメソッド
    
    /**
     * 権限チェック付きで契約を取得
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
                    WHERE c.id = :contract_id AND c.doctor_id = :user_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['contract_id' => $contractId, 'user_id' => $userId]);
            
        } elseif ($userType === 'company') {
            // 企業ユーザーは自分の支店の契約のみアクセス可能
            $sql = "SELECT c.*, comp.name as company_name, b.name as branch_name
                    FROM contracts c
                    JOIN companies comp ON c.company_id = comp.id
                    JOIN branches b ON c.branch_id = b.id
                    JOIN user_branch_mappings ubm ON ubm.branch_id = b.id
                    WHERE c.id = :contract_id 
                    AND ubm.user_id = :user_id
                    AND ubm.is_active = 1
                    AND b.is_active = 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['contract_id' => $contractId, 'user_id' => $userId]);
            
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
                WHERE contract_id = :contract_id AND closing_period = :closing_period";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId, 'closing_period' => $closingPeriod]);
        
        return $stmt->fetch();
    }

    /**
     * 未締めの役務記録数を取得
     */
    private function getUnClosedRecordsCount($contractId, $closingPeriod)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) FROM service_records 
                WHERE contract_id = :contract_id
                AND DATE_FORMAT(service_date, '%Y-%m') = :closing_period
                AND status = 'approved'
                AND (is_closed = 0 OR is_closed IS NULL)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId, 'closing_period' => $closingPeriod]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * 未承認の役務記録数を取得
     */
    private function getUnapprovedRecordsCount($contractId, $closingPeriod)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) FROM service_records 
                WHERE contract_id = :contract_id
                AND DATE_FORMAT(service_date, '%Y-%m') = :closing_period
                AND status IN ('pending', 'rejected')
                AND (is_closed = 0 OR is_closed IS NULL)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['contract_id' => $contractId, 'closing_period' => $closingPeriod]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * 締め処理が実行可能かどうかを判定
     */
    private function canProcessClosing($contractId, $closingPeriod, $unapprovedRecordsCount = null, $userType = null, $closingRecord = null, $unapprovedTravelExpensesCount = null)
    {
        if ($closingRecord === null) {
            $closingRecord = $this->getClosingRecord($contractId, $closingPeriod);
        }
        
        if ($userType === null) {
            $userType = Session::get('user_type');
        }
        
        // 企業承認済みの場合は誰も処理不可
        if ($closingRecord && $closingRecord['company_approved'] == 1) {
            return false;
        }
        
        // 産業医・管理者の場合
        if ($userType === 'doctor' || $userType === 'admin') {
            // 【修正】未承認の役務または交通費がある場合は確定不可
            if ($unapprovedRecordsCount === null) {
                $unapprovedRecordsCount = $this->getUnapprovedRecordsCount($contractId, $closingPeriod);
            }
            if ($unapprovedTravelExpensesCount === null) {
                $unapprovedTravelExpensesCount = $this->getUnapprovedTravelExpensesCount($contractId, $closingPeriod);
            }
            
            // 未承認の役務または交通費があれば確定不可
            if ($unapprovedRecordsCount > 0 || $unapprovedTravelExpensesCount > 0) {
                return false;
            }
            
            // 未処理または差戻し状態の場合は処理可能
            return true;
        }
        
        // 企業ユーザーの場合
        if ($userType === 'company') {
            // 確定済みで未承認の場合のみ承認/差戻し可能
            return ($closingRecord && $closingRecord['status'] === 'finalized' && $closingRecord['company_approved'] == 0);
        }
        
        return false;
    }
    
    /**
     * 月次締め記録の作成または更新
     */
    private function createOrUpdateClosingRecord($contractId, $closingPeriod, $contract, $simulation, $userId, $generatePdf = false, $doctorComment = null, $extensionReason = null)
    {
        $db = Database::getInstance()->getConnection();
        
        // 【追加】契約情報のスナップショットを作成
        $contractSnapshot = [
            'id' => $contract['id'],
            'doctor_id' => $contract['doctor_id'],
            'company_id' => $contract['company_id'],
            'branch_id' => $contract['branch_id'],
            'regular_visit_hours' => $contract['regular_visit_hours'],
            'visit_frequency' => $contract['visit_frequency'],
            'exclude_holidays' => $contract['exclude_holidays'],
            'taxi_allowed' => $contract['taxi_allowed'],
            'tax_type' => $contract['tax_type'],
            'regular_visit_rate' => $contract['regular_visit_rate'],
            'regular_extension_rate' => $contract['regular_extension_rate'],
            'emergency_visit_rate' => $contract['emergency_visit_rate'],
            'document_consultation_rate' => $contract['document_consultation_rate'],
            'spot_rate' => $contract['spot_rate'] ?? null,
            // 毎週契約の場合の追加情報
            'exclusive_registration_fee' => $contract['exclusive_registration_fee'] ?? null,
            'calculated_visit_count' => $contract['calculated_visit_count'] ?? null,
            'single_visit_hours' => $contract['single_visit_hours'] ?? null,
            'total_contract_hours' => $contract['total_contract_hours'] ?? null
        ];
        
        $existingRecord = $this->getClosingRecord($contractId, $closingPeriod);
        
        if ($existingRecord) {
            // 更新
            $sql = "UPDATE monthly_closing_records SET
                        doctor_id = :doctor_id,
                        contract_hours = :contract_hours,
                        total_approved_hours = :total_approved_hours,
                        regular_hours = :regular_hours,
                        regular_amount = :regular_amount,
                        regular_billing_method = :regular_billing_method,
                        actual_regular_hours = :actual_regular_hours,
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
                        travel_expense_count = :travel_expense_count,
                        travel_expense_amount = :travel_expense_amount,
                        total_amount = :total_amount,
                        tax_rate = :tax_rate,
                        tax_amount = :tax_amount,
                        total_amount_with_tax = :total_amount_with_tax,
                        status = 'finalized',
                        simulation_data = :simulation_data,
                        contract_snapshot = :contract_snapshot,
                        doctor_comment = :doctor_comment,
                        extension_reason = :extension_reason,
                        finalized_at = NOW(),
                        finalized_by = :finalized_by,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $data = $this->getClosingRecordData($contract, $simulation, $userId, $doctorComment);
            $data['id'] = $existingRecord['id'];
            $data['contract_snapshot'] = json_encode($contractSnapshot);
            
            $stmt = $db->prepare($sql);
            $stmt->execute($data);
            
            // ★★★ 明細テーブルにデータを保存（既存の明細は削除して再作成） ★★★
            $detailsCreated = MonthlyClosingDetail::createDetails(
                $existingRecord['id'],
                $simulation,
                $contract,
                $db
            );
            
            if (!$detailsCreated) {
                throw new Exception('締め処理明細の保存に失敗しました。');
            }
            // ★★★ 追加ここまで ★★★
            
            return ['id' => $existingRecord['id']];
        } else {
            // 新規作成
            $sql = "INSERT INTO monthly_closing_records (
                        contract_id, doctor_id, closing_period, contract_hours,
                        total_approved_hours, regular_hours, regular_amount,
                        regular_billing_method, actual_regular_hours,
                        regular_extension_hours, regular_extension_amount,
                        emergency_hours, emergency_amount, document_count, document_amount,
                        remote_consultation_count, remote_consultation_amount,
                        spot_hours, spot_amount, spot_count,
                        other_count, other_amount,
                        travel_expense_count, travel_expense_amount,
                        total_amount, tax_rate, tax_amount, total_amount_with_tax,
                        status, simulation_data, contract_snapshot, doctor_comment, extension_reason, 
                        finalized_at, finalized_by, company_approved, created_at, updated_at
                    ) VALUES (
                        :contract_id, :doctor_id, :closing_period, :contract_hours,
                        :total_approved_hours, :regular_hours, :regular_amount,
                        :regular_billing_method, :actual_regular_hours,
                        :regular_extension_hours, :regular_extension_amount,
                        :emergency_hours, :emergency_amount, :document_count, :document_amount,
                        :remote_consultation_count, :remote_consultation_amount,
                        :spot_hours, :spot_amount, :spot_count,
                        :other_count, :other_amount,
                        :travel_expense_count, :travel_expense_amount,
                        :total_amount, :tax_rate, :tax_amount, :total_amount_with_tax,
                        'finalized', :simulation_data, :contract_snapshot, :doctor_comment, :extension_reason,
                        NOW(), :finalized_by, 0, NOW(), NOW()
                    )";
            
            $data = $this->getClosingRecordData($contract, $simulation, $userId, $doctorComment, $extensionReason);
            $data['contract_id'] = $contractId;
            $data['closing_period'] = $closingPeriod;
            $data['contract_snapshot'] = json_encode($contractSnapshot);
            
            $stmt = $db->prepare($sql);
            $stmt->execute($data);
            
            $closingRecordId = $db->lastInsertId();
            
            // ★★★ 明細テーブルにデータを保存 ★★★
            $detailsCreated = MonthlyClosingDetail::createDetails(
                $closingRecordId,
                $simulation,
                $contract,
                $db
            );
            
            if (!$detailsCreated) {
                throw new Exception('締め処理明細の保存に失敗しました。');
            }
            // ★★★ 追加ここまで ★★★
            
            return ['id' => $closingRecordId];
        }
    }
    
    /**
     * 締め記録データの準備
     */
    private function getClosingRecordData($contract, $simulation, $userId, $doctorComment = null, $extensionReason = null)
    {
        return [
            'doctor_id' => $contract['doctor_id'],
            'contract_hours' => $simulation['contract_hours'],
            'total_approved_hours' => array_sum([
                $simulation['summary']['regular_hours'],
                $simulation['summary']['regular_extension_hours'],
                $simulation['summary']['emergency_hours'],
                $simulation['summary']['spot_hours']
            ]),
            'regular_hours' => $simulation['summary']['regular_hours'],
            'regular_amount' => $simulation['summary']['regular_amount'],
            'regular_billing_method' => $simulation['regular_billing_method'] ?? 'contract_hours',
            'actual_regular_hours' => $simulation['actual_regular_hours'] ?? 0,
            'regular_extension_hours' => $simulation['summary']['regular_extension_hours'],
            'regular_extension_amount' => $simulation['summary']['regular_extension_amount'],
            'emergency_hours' => $simulation['summary']['emergency_hours'],
            'emergency_amount' => $simulation['summary']['emergency_amount'],
            'document_count' => $simulation['summary']['document_count'],
            'document_amount' => $simulation['summary']['document_amount'],
            'remote_consultation_count' => $simulation['summary']['remote_consultation_count'],
            'remote_consultation_amount' => $simulation['summary']['remote_consultation_amount'],
            'spot_hours' => $simulation['summary']['spot_hours'],
            'spot_amount' => $simulation['summary']['spot_amount'],
            'spot_count' => $this->countSpotRecords($simulation['service_breakdown']),
            'other_count' => $simulation['summary']['other_count'] ?? 0,
            'other_amount' => $simulation['summary']['other_amount'] ?? 0,
            'travel_expense_count' => count($simulation['travel_expenses']),
            'travel_expense_amount' => $simulation['travel_expense_total'],
            'total_amount' => $simulation['total_amount'],
            'tax_rate' => $simulation['tax_rate'],
            'tax_amount' => $simulation['tax_amount'],
            'total_amount_with_tax' => $simulation['total_with_tax'],
            'simulation_data' => json_encode($simulation),
            'doctor_comment' => $doctorComment,
            'extension_reason' => $extensionReason,
            'finalized_by' => $userId
        ];
    }

    /**
     * スポット役務の件数をカウント
     */
    private function countSpotRecords($serviceBreakdown)
    {
        $count = 0;
        foreach ($serviceBreakdown as $item) {
            if ($item['billing_service_type'] === 'spot') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 役務記録と交通費の更新
     */
    private function updateServiceRecordsAndTravelExpenses($simulation, $closingPeriod, $userId)
    {
        $db = Database::getInstance()->getConnection();
        
        // 役務記録の更新
        foreach ($simulation['service_breakdown'] as $item) {
            $serviceRecord = $item['service_record'];
            
            if ($serviceRecord) {
                $sql = "UPDATE service_records SET
                            status = 'finalized',
                            billing_service_type = :billing_service_type,
                            billing_hours = :billing_hours,
                            billing_amount = :billing_amount,
                            closing_period = :closing_period,
                            is_closed = 1,
                            closed_at = NOW(),
                            closed_by = :closed_by,
                            updated_at = NOW()
                        WHERE id = :id";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'id' => $serviceRecord['id'],
                    'billing_service_type' => $item['billing_service_type'],
                    'billing_hours' => $item['billing_hours'],
                    'billing_amount' => $item['billing_amount'],
                    'closing_period' => $closingPeriod,
                    'closed_by' => $userId
                ]);
            }
        }
        
        // 交通費の更新
        foreach ($simulation['travel_expenses'] as $expense) {
            $sql = "UPDATE travel_expenses SET
                        status = 'finalized',
                        closing_period = :closing_period,
                        is_closed = 1,
                        closed_at = NOW(),
                        closed_by = :closed_by,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'id' => $expense['id'],
                'closing_period' => $closingPeriod,
                'closed_by' => $userId
            ]);
        }
    }
    
    /**
     * 役務記録の更新
     */
    private function updateServiceRecords($simulation, $closingPeriod, $userId)
    {
        $db = Database::getInstance()->getConnection();
        
        foreach ($simulation['service_breakdown'] as $item) {
            $serviceRecord = $item['service_record'];
            
            // 役務記録のステータスを'finalized'（締め済み）に更新
            $sql = "UPDATE service_records SET
                        status = 'finalized',
                        billing_service_type = :billing_service_type,
                        billing_hours = :billing_hours,
                        billing_amount = :billing_amount,
                        closing_period = :closing_period,
                        is_closed = 1,
                        closed_at = NOW(),
                        closed_by = :closed_by,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'id' => $serviceRecord['id'],
                'billing_service_type' => $item['billing_service_type'],
                'billing_hours' => $item['billing_hours'],
                'billing_amount' => $item['billing_amount'],
                'closing_period' => $closingPeriod,
                'closed_by' => $userId
            ]);
        }
    }
    
    /**
     * 締め処理履歴の作成
     */
    private function createClosingHistory($closingRecordId, $actionType, $userId, $comment = null, $beforeData = null, $afterData = null)
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO closing_process_history (
                    monthly_closing_record_id, action_type, action_by, 
                    comment, before_data, after_data, action_at
                ) VALUES (
                    :monthly_closing_record_id, :action_type, :action_by,
                    :comment, :before_data, :after_data, NOW()
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'monthly_closing_record_id' => $closingRecordId,
            'action_type' => $actionType,
            'action_by' => $userId,
            'comment' => $comment,
            'before_data' => $beforeData ? json_encode($beforeData) : null,
            'after_data' => $afterData ? json_encode($afterData) : null
        ]);
    }
    
    /**
     * 空のサマリーデータを返す
     */
    private function getEmptySummary()
    {
        return [
            'regular_hours' => 0,
            'regular_amount' => 0,
            'regular_extension_hours' => 0,
            'regular_extension_amount' => 0,
            'emergency_hours' => 0,
            'emergency_amount' => 0,
            'document_count' => 0,
            'document_amount' => 0,
            'remote_consultation_count' => 0,
            'remote_consultation_amount' => 0,
            'spot_hours' => 0,
            'spot_amount' => 0,
            'other_count' => 0,
            'other_amount' => 0
        ];
    }

    /**
     * 企業による締め処理の一括承認
     */
    public function bulkCompanyApprove()
    {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 企業ユーザーのみ実行可能
        if ($userType !== 'company') {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '締め処理の承認は企業ユーザーのみが実行できます。']);
                exit;
            }
            $this->setFlash('error', '締め処理の承認は企業ユーザーのみが実行できます。');
            redirect('closing');
        }
        
        if (!$this->validateCsrf()) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRFトークンが無効です。']);
                exit;
            }
            redirect('closing');
        }
        
        $closingItems = $_POST['closing_items'] ?? [];
        
        if (empty($closingItems)) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '承認する締め処理が選択されていません。']);
                exit;
            }
            $this->setFlash('error', '承認する締め処理が選択されていません。');
            redirect('closing');
        }
        
        $db = Database::getInstance()->getConnection();
        $userId = Session::get('user_id');
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        try {
            $db->beginTransaction();
            
            foreach ($closingItems as $itemJson) {
                $item = json_decode($itemJson, true);
                
                if (!$item || !isset($item['contract_id']) || !isset($item['closing_period'])) {
                    $errorCount++;
                    $errors[] = '無効なデータ形式です';
                    continue;
                }
                
                $contractId = (int)$item['contract_id'];
                $closingPeriod = $item['closing_period'];
                
                try {
                    // 権限チェック
                    $contract = $this->getContractWithPermissionCheck($contractId);
                    if (!$contract) {
                        $errorCount++;
                        $errors[] = "{$closingPeriod} - 権限がありません";
                        continue;
                    }
                    
                    // 締め記録を取得
                    $closingRecord = $this->getClosingRecord($contractId, $closingPeriod);
                    if (!$closingRecord) {
                        $errorCount++;
                        $errors[] = "{$closingPeriod} - 締め記録が見つかりません";
                        continue;
                    }
                    
                    if ($closingRecord['status'] !== 'finalized') {
                        $errorCount++;
                        $errors[] = "{$closingPeriod} - 確定済みの締め記録のみ承認できます";
                        continue;
                    }
                    
                    if ($closingRecord['company_approved'] == 1) {
                        $errorCount++;
                        $errors[] = "{$closingPeriod} - 既に承認済みです";
                        continue;
                    }
                    
                    // 企業承認フラグを更新
                    $sql = "UPDATE monthly_closing_records 
                            SET company_approved = 1,
                                company_approved_at = NOW(),
                                company_approved_by = :approved_by,
                                company_rejected_at = NULL,
                                company_rejected_by = NULL,
                                company_rejection_reason = NULL,
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
                    $this->createClosingHistory($closingRecord['id'], 'company_approve', $userId, '企業が締め処理を承認しました（一括承認）');
                    
                    // トランザクション完了後に請求書PDF自動生成（個別に処理）
                    try {
                        require_once __DIR__ . '/InvoiceController.php';
                        $invoiceController = new InvoiceController();
                        $pdfPath = $invoiceController->generate($closingRecord['id']);
                        
                        // PDFパスをデータベースに保存
                        if ($pdfPath) {
                            $updatePdfSql = "UPDATE monthly_closing_records 
                                            SET invoice_pdf_path = :pdf_path,
                                                updated_at = NOW()
                                            WHERE id = :id";
                            $stmt = $db->prepare($updatePdfSql);
                            $stmt->execute([
                                'id' => $closingRecord['id'],
                                'pdf_path' => $pdfPath
                            ]);
                            
                            error_log("Invoice PDF generated and path saved successfully: {$pdfPath}");
                        }
                        
                    } catch (Exception $e) {
                        // PDFエラーは致命的でないのでログのみ
                        error_log("Invoice PDF generation failed for closing record {$closingRecord['id']}: " . $e->getMessage());
                    }
                    
                    $successCount++;
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "{$closingPeriod} - " . $e->getMessage();
                    error_log("Bulk approve error for contract {$contractId}, period {$closingPeriod}: " . $e->getMessage());
                }
            }
            
            $db->commit();
            
            $message = "締め処理の一括承認を完了しました。成功: {$successCount}件";
            if ($errorCount > 0) {
                $message .= "、失敗: {$errorCount}件";
            }
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'errors' => $errors
                ]);
                exit;
            }
            
            $this->setFlash('success', $message);
            if (!empty($errors)) {
                $this->setFlash('warning', 'エラー詳細: ' . implode(', ', $errors));
            }
            redirect('closing');
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Bulk company approve failed: " . $e->getMessage());
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '一括承認処理に失敗しました: ' . $e->getMessage()]);
                exit;
            }
            
            $this->setFlash('error', '一括承認処理に失敗しました: ' . $e->getMessage());
            redirect('closing');
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
        
        // BOM出力（Excel対応）
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
     * 契約データから利用可能な役務種別を取得
     * 
     * @param array $contracts 契約データの配列
     * @return array 利用可能な役務種別の配列（キー => 表示名）
     */
    private function getAvailableServiceTypes($contracts)
    {
        $availableTypes = [];
        
        // 各契約をチェック
        $hasRegularVisit = false;
        $hasRemoteConsultation = false;
        $hasDocumentCreation = false;
        $hasSpot = false;
        
        foreach ($contracts as $contract) {
            // 定期訪問: 訪問頻度が「毎月」「隔月」「毎週」の契約
            if (in_array($contract['visit_frequency'], ['monthly', 'bimonthly', 'weekly'])) {
                $hasRegularVisit = true;
            }
            
            // 遠隔相談: use_remote_consultationが1の契約
            if (!empty($contract['use_remote_consultation']) && $contract['use_remote_consultation'] == 1) {
                $hasRemoteConsultation = true;
            }
            
            // 書面作成: use_document_creationが1の契約
            if (!empty($contract['use_document_creation']) && $contract['use_document_creation'] == 1) {
                $hasDocumentCreation = true;
            }
            
            // スポット: 訪問頻度が「スポット」の契約
            if ($contract['visit_frequency'] === 'spot') {
                $hasSpot = true;
            }
        }
        
        // 利用可能な役務種別をリストに追加（キー => 表示名）
        if ($hasRegularVisit) {
            $availableTypes['regular'] = '定期訪問';
            $availableTypes['emergency'] = '臨時訪問';
        }
        
        if ($hasRemoteConsultation) {
            $availableTypes['remote_consultation'] = '遠隔相談';
        }
        
        if ($hasDocumentCreation) {
            $availableTypes['document'] = '書面作成';
        }
        
        if ($hasSpot) {
            $availableTypes['spot'] = 'スポット';
        }
        
        // 「その他」は常に追加
        $availableTypes['other'] = 'その他';
        
        return $availableTypes;
    }

    /**
     * 指定された月に特定の役務種別の記録があるかチェック
     * 
     * @param int $contractId 契約ID
     * @param string $month 対象月(Y-m形式)
     * @param string $serviceType 役務種別
     * @param PDO $db データベース接続
     * @return bool 該当する記録がある場合true
     */
    private function hasServiceTypeInMonth($contractId, $month, $serviceType, $db)
    {
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        
        $sql = "SELECT COUNT(*) as count
                FROM service_records
                WHERE contract_id = :contract_id
                AND service_date >= :month_start
                AND service_date <= :month_end
                AND service_type = :service_type";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
            'service_type' => $serviceType
        ]);
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * 締め記録から契約の状態を取得
     * 
     * @param array|null $closingRecord 締め記録
     * @return string 状態('not_processed', 'pending_approval', 'approved', 'rejected')
     */
    private function getContractStatus($closingRecord)
    {
        if (!$closingRecord) {
            return 'not_processed'; // 未処理（締め記録なし）
        }
        
        // 企業承認済み
        if (!empty($closingRecord['company_approved']) && $closingRecord['company_approved'] == 1) {
            return 'approved';
        }
        
        // 差戻し（企業が差し戻した）
        if (!empty($closingRecord['company_rejected_at'])) {
            return 'rejected';
        }
        
        // 承認待ち（確定済みで企業承認待ち）
        if ($closingRecord['status'] === 'finalized') {
            return 'pending_approval';
        }
        
        // 下書きまたはその他未処理
        return 'not_processed';
    }

    /**
     * 検索テキストが契約にマッチするかチェック
     * 
     * @param array $contract 契約データ
     * @param string $searchText 検索テキスト
     * @param string $userType ユーザータイプ
     * @return bool マッチする場合true
     */
    private function matchesSearchText($contract, $searchText, $userType)
    {
        $searchText = mb_strtolower(trim($searchText));
        if (empty($searchText)) {
            return true;
        }
        
        $searchFields = [];
        
        // ユーザータイプに応じて検索対象フィールドを設定
        if ($userType === 'admin') {
            // 管理者: 企業・拠点・産業医
            $searchFields[] = mb_strtolower($contract['company_name'] ?? '');
            $searchFields[] = mb_strtolower($contract['branch_name'] ?? '');
            $searchFields[] = mb_strtolower($contract['doctor_name'] ?? '');
        } elseif ($userType === 'doctor') {
            // 産業医: 企業・拠点
            $searchFields[] = mb_strtolower($contract['company_name'] ?? '');
            $searchFields[] = mb_strtolower($contract['branch_name'] ?? '');
        } elseif ($userType === 'company') {
            // 企業: 拠点・産業医
            $searchFields[] = mb_strtolower($contract['branch_name'] ?? '');
            $searchFields[] = mb_strtolower($contract['doctor_name'] ?? '');
        }
        
        // いずれかのフィールドに検索テキストが含まれているかチェック
        foreach ($searchFields as $field) {
            if (mb_strpos($field, $searchText) !== false) {
                return true;
            }
        }
        
        return false;
    }

}
?>