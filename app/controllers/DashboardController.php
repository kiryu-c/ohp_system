<?php
// app/controllers/DashboardController.php - 非訪問月でも実績表示対応版
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/ServiceRecord.php';

class DashboardController extends BaseController {
    public function index() {
        // セッションを開始
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // ログインチェック
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            header('Location: ' . $this->getAppUrl() . '/login');
            exit;
        }
        
        $userType = $_SESSION['user_type'];
        
        switch ($userType) {
            case 'doctor':
                $this->doctorDashboard();
                break;
            case 'company':
                $this->companyDashboard();
                break;
            case 'admin':
                $this->adminDashboard();
                break;
            default:
                header('Location: ' . $this->getAppUrl() . '/login');
                exit;
        }
    }
    
    private function getAppUrl() {
        $config = require __DIR__ . '/../../config/app.php';
        return rtrim($config['app_url'], '/');
    }
    
    /**
     * ログインユーザーがアクセス可能な拠点IDを取得
     */
    private function getAccessibleBranchIds($userId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT branch_id FROM user_branch_mappings 
                WHERE user_id = :user_id AND is_active = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        $branchIds = [];
        while ($row = $stmt->fetch()) {
            $branchIds[] = $row['branch_id'];
        }
        
        return $branchIds;
    }
    
    /**
     * 拠点IDの配列をIN句用の文字列に変換
     */
    private function createInClause($branchIds) {
        if (empty($branchIds)) {
            return '0'; // アクセス可能な拠点がない場合は0を返す（該当なし）
        }
        return implode(',', array_map('intval', $branchIds));
    }

    /**
     * ページネーション情報を取得・計算
     */
    private function getPaginationInfo($type = 'contracts') {
        // デフォルトのページサイズ設定
        $defaultPageSizes = [
            'contracts' => 10,
            'pending_records' => 15,
            'service_records' => 15,
            'recent_services' => 15,
            'pending_travel_expenses' => 10
        ];
        
        // URLパラメータから値を取得（存在チェック付き）
        $pageKey = $type . '_page';
        $pageSizeKey = $type . '_page_size';
        
        $page = max(1, intval($_GET[$pageKey] ?? 1));
        $pageSize = intval($_GET[$pageSizeKey] ?? ($defaultPageSizes[$type] ?? 10));
        
        // ページサイズの制限（5-50の範囲）
        $pageSize = max(5, min(50, $pageSize));
        
        $offset = ($page - 1) * $pageSize;
        
        return [
            'page' => $page,
            'page_size' => $pageSize,
            'offset' => $offset
        ];
    }
    
    /**
     * 総件数からページネーション情報を計算
     */
    private function calculatePagination($totalCount, $currentPage, $pageSize) {
        $totalPages = max(1, ceil($totalCount / $pageSize));
        $currentPage = max(1, min($totalPages, $currentPage));
        
        return [
            'total_count' => $totalCount,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'page_size' => $pageSize,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'start_record' => $totalCount > 0 ? (($currentPage - 1) * $pageSize) + 1 : 0,
            'end_record' => min($totalCount, $currentPage * $pageSize)
        ];
    }
    
    private function doctorDashboard() {
        $doctorId = $_SESSION['user_id'];
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // ページネーション用パラメータを取得
        $contractsPaginationParams = $this->getPaginationInfo('contracts');
        $serviceRecordsPaginationParams = $this->getPaginationInfo('service_records');
        
        // ユーザーの二要素認証状態を取得
        $userSql = "SELECT two_factor_enabled FROM users WHERE id = :user_id";
        $stmt = $db->prepare($userSql);
        $stmt->execute(['user_id' => $doctorId]);
        $user = $stmt->fetch();
        $twoFactorEnabled = $user ? (bool)$user['two_factor_enabled'] : false;

        // 契約一覧の総件数を取得
        $contractsCountSql = "SELECT COUNT(*) as total
                            FROM contracts co
                            JOIN branches b ON co.branch_id = b.id
                            JOIN companies c ON co.company_id = c.id
                            WHERE co.doctor_id = :doctor_id 
                            AND co.contract_status = 'active'
                            AND co.effective_date <= CURDATE()
                            AND (co.effective_end_date IS NULL OR co.effective_end_date >= CURDATE())
                            AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))";

        $stmt = $db->prepare($contractsCountSql);
        $stmt->execute(['doctor_id' => $doctorId]);
        $contractsTotalCount = $stmt->fetchColumn();

        // 契約一覧(ページネーション適用)
        $contractsSql = "SELECT co.*, c.name as company_name, c.address as company_address,
                        b.name as branch_name, b.address as branch_address, 
                        b.phone as branch_phone, b.email as branch_email,
                        CONCAT(c.name, ' - ', b.name) as company_branch_name
                        FROM contracts co
                        JOIN branches b ON co.branch_id = b.id
                        JOIN companies c ON co.company_id = c.id
                        WHERE co.doctor_id = :doctor_id 
                        AND co.contract_status = 'active'
                        AND co.effective_date <= CURDATE()
                        AND (co.effective_end_date IS NULL OR co.effective_end_date >= CURDATE())
                        AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))
                        ORDER BY c.name ASC, b.name ASC
                        LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($contractsSql);
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $contractsPaginationParams['page_size'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $contractsPaginationParams['offset'], PDO::PARAM_INT);
        $stmt->execute();
        $contracts = $stmt->fetchAll();
        
        // 今月の役務記録統計（各契約について）
        $currentMonth = date('n');
        $currentYear = date('Y');
        
        // 各契約の今月の役務統計を取得
        foreach ($contracts as &$contract) {
            $statsSql = "SELECT 
                            SUM(CASE WHEN service_type = 'regular' OR service_type IS NULL THEN service_hours ELSE 0 END) as regular_hours,
                            SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                            SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                            COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                            COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                            COUNT(CASE WHEN service_type = 'other' THEN 1 END) as other_count,
                            SUM(service_hours) as total_hours,
                            SUM(CASE WHEN status = 'pending' THEN service_hours ELSE 0 END) as pending_hours
                        FROM service_records 
                        WHERE contract_id = :contract_id 
                        AND YEAR(service_date) = :year 
                        AND MONTH(service_date) = :month";
            
            $stmt = $db->prepare($statsSql);
            $stmt->execute([
                'contract_id' => $contract['id'],
                'year' => $currentYear,
                'month' => $currentMonth
            ]);
            
            $stats = $stmt->fetch() ?: [
                'regular_hours' => 0,
                'emergency_hours' => 0,
                'extension_hours' => 0,
                'document_count' => 0,
                'remote_consultation_count' => 0,
                'other_count' => 0,
                'total_hours' => 0,
                'pending_hours' => 0
            ];

            // 月締め状態をチェック（今月分）
            $contract['is_current_month_closed'] = $this->isMonthClosed(
                $contract['id'], 
                $currentYear, 
                $currentMonth
            );
            
            // 契約に統計情報を追加
            $contract['regular_hours'] = $stats['regular_hours'];
            $contract['emergency_hours'] = $stats['emergency_hours'];
            $contract['extension_hours'] = $stats['extension_hours'];
            $contract['document_count'] = $stats['document_count'];
            $contract['remote_consultation_count'] = $stats['remote_consultation_count'];
            $contract['other_count'] = $stats['other_count'];
            $contract['total_hours'] = $stats['total_hours'];
            $contract['pending_hours'] = $stats['pending_hours'];
            
            // 訪問頻度に応じた月間時間の計算
            $frequency = $contract['visit_frequency'] ?? 'monthly';
            
            if ($frequency === 'weekly') {
                // 毎週契約の場合：exclude_holidaysとnon_visit_daysを考慮
                $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                    $contract['id'], 
                    $contract['regular_visit_hours'] ?? 0, 
                    $currentYear, 
                    $currentMonth, 
                    $db
                );
                
                // 残り時間と使用率を計算
                $contract['remaining_regular_hours'] = max(0, $monthlyHours - ($stats['regular_hours'] ?? 0));
                $contract['regular_usage_percentage'] = $monthlyHours > 0 ? 
                    (($stats['regular_hours'] ?? 0) / $monthlyHours) * 100 : 0;
                
                // 互換性のため
                $contract['this_month_hours'] = $stats['total_hours'];
                $contract['remaining_hours'] = $contract['remaining_regular_hours'];
                $contract['usage_percentage'] = $contract['regular_usage_percentage'];
                
            } elseif ($frequency === 'bimonthly') {
                // 隔月契約の場合：既存のis_visit_month判定を使用
                $isVisitMonth = is_visit_month($contract, $currentYear, $currentMonth);
                
                if ($isVisitMonth) {
                    $contract['remaining_regular_hours'] = max(0, $contract['regular_visit_hours'] - ($stats['regular_hours'] ?? 0));
                    $contract['regular_usage_percentage'] = ($contract['regular_visit_hours'] > 0) ? 
                        (($stats['regular_hours'] ?? 0) / $contract['regular_visit_hours']) * 100 : 0;
                } else {
                    $contract['remaining_regular_hours'] = 0;
                    $contract['regular_usage_percentage'] = 0;
                }
                
                // 互換性のため
                $contract['this_month_hours'] = $stats['total_hours'];
                $contract['remaining_hours'] = $contract['remaining_regular_hours'];
                $contract['usage_percentage'] = $contract['regular_usage_percentage'];
                
            } else {
                // 毎月契約の場合：従来通り
                $contract['remaining_regular_hours'] = max(0, $contract['regular_visit_hours'] - ($stats['regular_hours'] ?? 0));
                $contract['regular_usage_percentage'] = ($contract['regular_visit_hours'] > 0) ? 
                    (($stats['regular_hours'] ?? 0) / $contract['regular_visit_hours']) * 100 : 0;
                
                // 互換性のため
                $contract['this_month_hours'] = $stats['total_hours'];
                $contract['remaining_hours'] = $contract['remaining_regular_hours'];
                $contract['usage_percentage'] = $contract['regular_usage_percentage'];
            }
        }
        unset($contract); // 参照を解除（重要）
        
        // 今月の役務記録の総件数を取得
        $serviceRecordsCountSql = "SELECT COUNT(*) as total
                                FROM service_records sr
                                JOIN contracts co ON sr.contract_id = co.id
                                JOIN branches b ON co.branch_id = b.id
                                JOIN companies c ON co.company_id = c.id
                                WHERE sr.doctor_id = :doctor_id 
                                AND YEAR(sr.service_date) = :year 
                                AND MONTH(sr.service_date) = :month
                                AND co.contract_status = 'active'
                                AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))";
        
        $stmt = $db->prepare($serviceRecordsCountSql);
        $stmt->execute([
            'doctor_id' => $doctorId,
            'year' => $currentYear,
            'month' => $currentMonth
        ]);
        $serviceRecordsTotalCount = $stmt->fetchColumn();
        
        // 今月の役務記録（ページネーション適用）
        $serviceRecordsSql = "SELECT sr.*, c.name as company_name, b.name as branch_name
                            FROM service_records sr
                            JOIN contracts co ON sr.contract_id = co.id
                            JOIN branches b ON co.branch_id = b.id
                            JOIN companies c ON co.company_id = c.id
                            WHERE sr.doctor_id = :doctor_id 
                            AND YEAR(sr.service_date) = :year 
                            AND MONTH(sr.service_date) = :month
                            AND co.contract_status = 'active'
                            AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))
                            ORDER BY sr.service_date DESC, sr.created_at DESC
                            LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($serviceRecordsSql);
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        $stmt->bindValue(':year', $currentYear, PDO::PARAM_INT);
        $stmt->bindValue(':month', $currentMonth, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $serviceRecordsPaginationParams['page_size'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $serviceRecordsPaginationParams['offset'], PDO::PARAM_INT);
        $stmt->execute();
        $serviceRecords = $stmt->fetchAll();
        
        // 進行中の役務があるかチェック
        $hasActiveService = false;
        $activeServiceSql = "SELECT id FROM service_records 
                            WHERE doctor_id = :doctor_id 
                            AND service_hours = 0 
                            AND start_time = end_time
                            AND service_date = CURDATE() 
                            LIMIT 1";
        
        $stmt = $db->prepare($activeServiceSql);
        $stmt->execute(['doctor_id' => $doctorId]);
        if ($stmt->fetch()) {
            $hasActiveService = true;
        }
        
        // ページネーション情報を計算
        $contractsPaginationInfo = $this->calculatePagination(
            $contractsTotalCount,
            $contractsPaginationParams['page'],
            $contractsPaginationParams['page_size']
        );
        
        $serviceRecordsPaginationInfo = $this->calculatePagination(
            $serviceRecordsTotalCount,
            $serviceRecordsPaginationParams['page'],
            $serviceRecordsPaginationParams['page_size']
        );
        
        // CSRFトークンの生成（存在しない場合）
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
                
        // ビューに渡すデータ
        $data = [
            'contracts' => $contracts,
            'serviceRecords' => $serviceRecords,
            'hasActiveService' => $hasActiveService,
            'contractsPaginationInfo' => $contractsPaginationInfo,
            'serviceRecordsPaginationInfo' => $serviceRecordsPaginationInfo,
            'twoFactorEnabled' => $twoFactorEnabled,
            'csrf_token' => $_SESSION['csrf_token']
        ];
        
        // 変数をビューで直接使えるようにする
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/dashboard/doctor.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    
    private function companyDashboard() {
        $companyId = $_SESSION['company_id'];
        $userId = $_SESSION['user_id'];
        
        require_once __DIR__ . '/../core/Database.php';
        require_once __DIR__ . '/../core/helpers.php';
        $db = Database::getInstance()->getConnection();
        
        // ユーザーの二要素認証状態を取得
        $userSql = "SELECT two_factor_enabled FROM users WHERE id = :user_id";
        $stmt = $db->prepare($userSql);
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();
        $twoFactorEnabled = $user ? (bool)$user['two_factor_enabled'] : false;

        // ログインユーザーがアクセス可能な拠点IDを取得
        $accessibleBranchIds = $this->getAccessibleBranchIds($userId);
        
        if (empty($accessibleBranchIds)) {
            // アクセス可能な拠点がない場合の処理
            $contracts = [];
            $pendingRecords = [];
            $contractsPaginationInfo = $this->calculatePagination(0, 1, 10);
            $pendingRecordsPaginationInfo = $this->calculatePagination(0, 1, 15);
        } else {
            $branchInClause = $this->createInClause($accessibleBranchIds);
            
            // ページネーション用パラメータを取得
            $contractsPaginationParams = $this->getPaginationInfo('contracts');
            $pendingRecordsPaginationParams = $this->getPaginationInfo('pending_records');
            
            // 契約一覧の総件数を取得
            $contractsCountSql = "SELECT COUNT(*) as total
                                FROM contracts co
                                JOIN branches b ON co.branch_id = b.id
                                JOIN users u ON co.doctor_id = u.id
                                WHERE co.company_id = :company_id 
                                AND co.contract_status = 'active'
                                AND co.branch_id IN ($branchInClause)
                                AND co.effective_date <= CURDATE()
                                AND (co.effective_end_date IS NULL OR co.effective_end_date >= CURDATE())
                                AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))";

            $stmt = $db->prepare($contractsCountSql);
            $stmt->execute(['company_id' => $companyId]);
            $contractsTotalCount = $stmt->fetchColumn();

            // 表示用契約データ(ページネーション適用)- 週間スケジュール情報も取得
            $contractsSql = "SELECT co.*, u.name as doctor_name, b.name as branch_name
                        FROM contracts co
                        JOIN branches b ON co.branch_id = b.id
                        JOIN users u ON co.doctor_id = u.id
                        WHERE co.company_id = :company_id 
                        AND co.contract_status = 'active'
                        AND co.branch_id IN ($branchInClause)
                        AND co.effective_date <= CURDATE()
                        AND (co.effective_end_date IS NULL OR co.effective_end_date >= CURDATE())
                        AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))
                        ORDER BY b.name ASC, u.name ASC
                        LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($contractsSql);
            $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $contractsPaginationParams['page_size'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $contractsPaginationParams['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $contracts = $stmt->fetchAll();
            
            // 各契約に対して週間スケジュール情報を取得
            foreach ($contracts as &$contract) {
                if ($contract['visit_frequency'] === 'weekly') {
                    // 週間スケジュール情報を取得
                    $weeklyScheduleSql = "SELECT day_of_week FROM contract_weekly_schedules 
                                        WHERE contract_id = :contract_id 
                                        ORDER BY day_of_week";
                    
                    $stmt = $db->prepare($weeklyScheduleSql);
                    $stmt->execute(['contract_id' => $contract['id']]);
                    $weeklyDays = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // weekly_daysフィールドとして設定
                    $contract['weekly_days'] = $weeklyDays;
                    
                    // 曜日名の文字列も生成
                    $dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                    $weeklyScheduleNames = [];
                    foreach ($weeklyDays as $day) {
                        if (isset($dayNames[$day])) {
                            $weeklyScheduleNames[] = $dayNames[$day];
                        }
                    }
                    $contract['weekly_schedule'] = implode('・', $weeklyScheduleNames);
                } else {
                    $contract['weekly_days'] = [];
                    $contract['weekly_schedule'] = '';
                }
            }
            unset($contract);
            
            // 今月の各契約の役務統計を取得（表示用契約のみ）
            $currentMonth = date('n');
            $currentYear = date('Y');
            
            foreach ($contracts as &$contract) {
                // まず実際の統計データを取得（訪問月かどうかに関係なく）
                $statsSql = "SELECT 
                                SUM(CASE WHEN service_type = 'regular' OR service_type IS NULL THEN service_hours ELSE 0 END) as regular_hours,
                                SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                                SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                                COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                                COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                                COUNT(CASE WHEN service_type = 'other' THEN 1 END) as other_count,
                                SUM(service_hours) as total_hours
                            FROM service_records 
                            WHERE contract_id = :contract_id 
                            AND YEAR(service_date) = :year 
                            AND MONTH(service_date) = :month";
                
                $stmt = $db->prepare($statsSql);
                $stmt->execute([
                    'contract_id' => $contract['id'],
                    'year' => $currentYear,
                    'month' => $currentMonth
                ]);
                
                $stats = $stmt->fetch() ?: [
                    'regular_hours' => 0,
                    'emergency_hours' => 0,
                    'extension_hours' => 0,
                    'document_count' => 0,
                    'remote_consultation_count' => 0,
                    'other_count' => 0,
                    'total_hours' => 0
                ];
                
                // 隔月契約・スポット契約の場合、今月が訪問月かどうかを判定
                $isVisitMonth = true; // デフォルトは訪問月とする
                
                if (($contract['visit_frequency'] ?? 'monthly') === 'bimonthly') {
                    $isVisitMonth = is_visit_month($contract, $currentYear, $currentMonth);
                } elseif (($contract['visit_frequency'] ?? 'monthly') === 'spot') {
                    // スポット契約は訪問月の概念がない
                    $isVisitMonth = false;
                }
                
                // 実際の統計情報は常に設定（非訪問月でも実績があれば表示）
                foreach ($stats as $key => $value) {
                    $contract[$key] = $value;
                }
                
                // 訪問月判定フラグを設定
                $contract['is_visit_month'] = $isVisitMonth;
                
                // 残り時間と使用率の計算
                $regularVisitHours = $contract['regular_visit_hours'] ?? 0;
                $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
                $totalUsedHours = $stats['total_hours'] ?? 0;
                
                if ($visitFrequency === 'weekly') {
                    // 毎週契約の月間時間計算（祝日非訪問・非訪問日考慮）
                    $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                        $contract['id'], 
                        $regularVisitHours, 
                        $currentYear, 
                        $currentMonth, 
                        $db
                    );
                    
                    // 定期訪問時間ベースで使用率を計算
                    $contract['remaining_hours'] = $monthlyHours - ($stats['regular_hours'] ?? 0);
                    $contract['usage_percentage'] = $monthlyHours > 0 ? 
                        (($stats['regular_hours'] ?? 0) / $monthlyHours) * 100 : 0;
                        
                    // 定期役務のみの情報も保持（別用途で必要な場合）
                    $contract['remaining_regular_hours'] = max(0, $monthlyHours - ($stats['regular_hours'] ?? 0));
                    $contract['regular_usage_percentage'] = $monthlyHours > 0 ? 
                        (($stats['regular_hours'] ?? 0) / $monthlyHours) * 100 : 0;
                        
                    $contract['this_month_hours'] = $stats['total_hours'];
                    
                    // 進捗表示用の分母
                    $contract['monthly_hours'] = $monthlyHours;
                    
                } elseif ($visitFrequency === 'bimonthly') {
                    // 隔月契約の場合
                    if ($isVisitMonth) {
                        // 訪問月の場合：通常の計算
                        $contract['remaining_hours'] = $regularVisitHours - ($stats['regular_hours'] ?? 0);
                        $contract['usage_percentage'] = $regularVisitHours > 0 ? 
                            (($stats['regular_hours'] ?? 0) / $regularVisitHours) * 100 : 0;
                    } else {
                        // 非訪問月だが実績がある場合：実績ベースで計算
                        if ($totalUsedHours > 0) {
                            // 実績があるので使用率は100%以上も許可（臨時訪問等のため）
                            $contract['remaining_hours'] = $regularVisitHours - ($stats['regular_hours'] ?? 0);
                            $contract['usage_percentage'] = $regularVisitHours > 0 ? 
                                (($stats['regular_hours'] ?? 0) / $regularVisitHours) * 100 : 
                                (($stats['regular_hours'] ?? 0) > 0 ? 100 : 0); // 定期訪問実績があれば最低100%として表示
                        } else {
                            // 非訪問月で実績もない場合
                            $contract['remaining_hours'] = 0;
                            $contract['usage_percentage'] = 0;
                        }
                    }
                        
                    // 定期役務のみの情報も保持
                    $contract['remaining_regular_hours'] = max(0, $regularVisitHours - ($stats['regular_hours'] ?? 0));
                    $contract['regular_usage_percentage'] = $regularVisitHours > 0 ? 
                        (($stats['regular_hours'] ?? 0) / $regularVisitHours) * 100 : 0;
                        
                    $contract['this_month_hours'] = $stats['total_hours'];
                    
                    // 進捗表示用の分母
                    $contract['monthly_hours'] = $regularVisitHours;
                    
                } elseif ($visitFrequency === 'spot') {
                    // スポット契約の場合：残り時間・使用率は計算しない
                    $contract['remaining_hours'] = 0;
                    $contract['usage_percentage'] = 0;
                    $contract['remaining_regular_hours'] = 0;
                    $contract['regular_usage_percentage'] = 0;
                    $contract['this_month_hours'] = $stats['total_hours'];
                    $contract['monthly_hours'] = 0;
                } else {
                    // 毎月契約の場合
                    // 定期訪問時間ベースで使用率を計算
                    $contract['remaining_hours'] = $regularVisitHours - ($stats['regular_hours'] ?? 0);
                    $contract['usage_percentage'] = $regularVisitHours > 0 ? 
                        (($stats['regular_hours'] ?? 0) / $regularVisitHours) * 100 : 0;
                        
                    // 定期役務のみの情報も保持
                    $contract['remaining_regular_hours'] = max(0, $regularVisitHours - ($stats['regular_hours'] ?? 0));
                    $contract['regular_usage_percentage'] = $regularVisitHours > 0 ? 
                        (($stats['regular_hours'] ?? 0) / $regularVisitHours) * 100 : 0;
                        
                    $contract['this_month_hours'] = $stats['total_hours'];
                    
                    // 進捗表示用の分母
                    $contract['monthly_hours'] = $regularVisitHours;
                }
            }
            unset($contract); // 参照を解除
            
            // 承認待ちの役務記録の総件数を取得
            $pendingRecordsCountSql = "SELECT COUNT(*) as total
                                    FROM service_records sr
                                    JOIN contracts co ON sr.contract_id = co.id
                                    JOIN branches b ON co.branch_id = b.id
                                    JOIN users u ON sr.doctor_id = u.id
                                    WHERE co.company_id = :company_id 
                                    AND sr.status = 'pending'
                                    AND co.branch_id IN ($branchInClause)
                                    AND co.contract_status = 'active'
                                    AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))";
            
            $stmt = $db->prepare($pendingRecordsCountSql);
            $stmt->execute(['company_id' => $companyId]);
            $pendingRecordsTotalCount = $stmt->fetchColumn();
            
            // 承認待ちの役務記録（ページネーション適用）
            $pendingSql = "SELECT sr.*, 
                                u.name as doctor_name,
                                b.name as branch_name,
                                co.regular_visit_hours
                        FROM service_records sr
                        JOIN contracts co ON sr.contract_id = co.id
                        JOIN branches b ON co.branch_id = b.id
                        JOIN users u ON sr.doctor_id = u.id
                        WHERE co.company_id = :company_id 
                        AND sr.status = 'pending'
                        AND co.branch_id IN ($branchInClause)
                        AND co.contract_status = 'active'
                        AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))
                        ORDER BY b.name ASC, u.name ASC, sr.service_date DESC, sr.start_time DESC
                        LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($pendingSql);
            $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $pendingRecordsPaginationParams['page_size'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pendingRecordsPaginationParams['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $pendingRecords = $stmt->fetchAll();


            // 承認待ちの締め処理のページネーション設定
            $pendingClosingsPaginationParams = $this->getPaginationInfo('pending_closings');

            // 承認待ちの締め処理の総件数を取得
            $pendingClosingsCountSql = "SELECT COUNT(*) as total
                                        FROM monthly_closing_records mcr
                                        JOIN contracts co ON mcr.contract_id = co.id
                                        JOIN branches b ON co.branch_id = b.id
                                        JOIN users u ON co.doctor_id = u.id
                                        WHERE co.company_id = :company_id
                                        AND mcr.status = 'finalized'
                                        AND mcr.company_approved != 1
                                        AND mcr.company_rejected_at IS NULL
                                        AND co.branch_id IN ($branchInClause)";

            $stmt = $db->prepare($pendingClosingsCountSql);
            $stmt->execute(['company_id' => $companyId]);
            $pendingClosingsTotalCount = $stmt->fetchColumn();

            // 承認待ちの締め処理一覧(ページネーション適用)
            $pendingClosingsSql = "SELECT mcr.*,
                                    co.id as contract_id,
                                    b.name as branch_name,
                                    co.visit_frequency,
                                    u.name as doctor_name,
                                    co.regular_visit_hours
                                FROM monthly_closing_records mcr
                                JOIN contracts co ON mcr.contract_id = co.id
                                JOIN branches b ON co.branch_id = b.id
                                JOIN users u ON co.doctor_id = u.id
                                WHERE co.company_id = :company_id
                                AND mcr.status = 'finalized'
                                AND mcr.company_approved != 1
                                AND mcr.company_rejected_at IS NULL
                                AND co.branch_id IN ($branchInClause)
                                ORDER BY mcr.finalized_at DESC
                                LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($pendingClosingsSql);
            $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $pendingClosingsPaginationParams['page_size'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pendingClosingsPaginationParams['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $pendingClosings = $stmt->fetchAll();
            
            // ページネーション情報を計算
            $contractsPaginationInfo = $this->calculatePagination(
                $contractsTotalCount,
                $contractsPaginationParams['page'],
                $contractsPaginationParams['page_size']
            );
            
            $pendingRecordsPaginationInfo = $this->calculatePagination(
                $pendingRecordsTotalCount,
                $pendingRecordsPaginationParams['page'],
                $pendingRecordsPaginationParams['page_size']
            );

            $pendingClosingsPaginationInfo = $this->calculatePagination(
                $pendingClosingsTotalCount,
                $pendingClosingsPaginationParams['page'],
                $pendingClosingsPaginationParams['page_size']
            );
        }
        
        // CSRFトークンの生成（存在しない場合）
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // ビューに渡すデータ
        $data = [
            'contracts' => $contracts ?? [],
            'pendingRecords' => $pendingRecords ?? [],
            'pendingClosings' => $pendingClosings ?? [],
            'contractsPaginationInfo' => $contractsPaginationInfo,
            'pendingRecordsPaginationInfo' => $pendingRecordsPaginationInfo,
            'pendingClosingsPaginationInfo' => $pendingClosingsPaginationInfo,
            'accessibleBranchIds' => $accessibleBranchIds,
            'hasAccessRestriction' => !empty($accessibleBranchIds),
            'twoFactorEnabled' => $twoFactorEnabled,
            'csrf_token' => $_SESSION['csrf_token']
        ];
        
        // 変数をビューで直接使えるようにする
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/dashboard/company.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    private function adminDashboard() {
        $userModel = new User();
        $companyModel = new Company();
        $contractModel = new Contract();
        $serviceModel = new ServiceRecord();

        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();

        // セッションからユーザーIDを取得
        $userId = $_SESSION['user_id'];
        // ユーザーの二要素認証状態を取得
        $userSql = "SELECT two_factor_enabled FROM users WHERE id = :user_id";
        $stmt = $db->prepare($userSql);
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();
        $twoFactorEnabled = $user ? (bool)$user['two_factor_enabled'] : false;

        // ページネーション用パラメータを取得
        $pendingTravelExpensesPaginationParams = $this->getPaginationInfo('pending_travel_expenses');
        $recentServicesPaginationParams = $this->getPaginationInfo('recent_services');

        // 承認待ち交通費の総件数を取得
        $pendingTravelExpensesCountSql = "SELECT COUNT(*) as total
                                        FROM travel_expenses te
                                        JOIN service_records sr ON te.service_record_id = sr.id
                                        JOIN contracts co ON sr.contract_id = co.id
                                        JOIN users u ON sr.doctor_id = u.id
                                        JOIN branches b ON co.branch_id = b.id
                                        JOIN companies c ON co.company_id = c.id
                                        WHERE te.status = 'pending'";
        
        $stmt = $db->prepare($pendingTravelExpensesCountSql);
        $stmt->execute();
        $pendingTravelExpensesTotalCount = $stmt->fetchColumn();

        // 承認待ち交通費を取得（ページネーション適用）
        $pendingTravelExpensesSql = "SELECT te.*, 
                                        sr.service_date,
                                        u.name as doctor_name,
                                        c.name as company_name,
                                        b.name as branch_name,
                                        co.taxi_allowed
                                    FROM travel_expenses te
                                    JOIN service_records sr ON te.service_record_id = sr.id
                                    JOIN contracts co ON sr.contract_id = co.id
                                    JOIN users u ON sr.doctor_id = u.id
                                    JOIN branches b ON co.branch_id = b.id
                                    JOIN companies c ON co.company_id = c.id
                                    WHERE te.status = 'pending'
                                    ORDER BY te.created_at DESC
                                    LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($pendingTravelExpensesSql);
        $stmt->bindValue(':limit', $pendingTravelExpensesPaginationParams['page_size'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pendingTravelExpensesPaginationParams['offset'], PDO::PARAM_INT);
        $stmt->execute();
        $pendingTravelExpenses = $stmt->fetchAll();

        // 最近のサービス記録の総件数を取得
        $recentServicesCountSql = "SELECT COUNT(*) as total
                                FROM service_records sr
                                LEFT JOIN users u ON sr.doctor_id = u.id
                                LEFT JOIN contracts co ON sr.contract_id = co.id
                                LEFT JOIN branches b ON co.branch_id = b.id
                                LEFT JOIN companies c ON co.company_id = c.id";
        
        $stmt = $db->prepare($recentServicesCountSql);
        $stmt->execute();
        $recentServicesTotalCount = $stmt->fetchColumn();

        // 最近のサービス記録（ページネーション適用）
        $recentServicesSql = "SELECT sr.*, 
                                u.name as doctor_name,
                                c.name as company_name,
                                b.name as branch_name,
                                co.regular_visit_hours,
                                -- 交通費情報を追加
                                te.id as travel_expense_id,
                                te.amount as travel_expense_amount,
                                te.status as travel_expense_status,
                                te.transport_type,
                                te.departure_point,
                                te.arrival_point
                            FROM service_records sr
                            LEFT JOIN users u ON sr.doctor_id = u.id
                            LEFT JOIN contracts co ON sr.contract_id = co.id
                            LEFT JOIN branches b ON co.branch_id = b.id
                            LEFT JOIN companies c ON co.company_id = c.id
                            LEFT JOIN travel_expenses te ON sr.id = te.service_record_id
                            ORDER BY sr.service_date DESC, sr.created_at DESC
                            LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($recentServicesSql);
        $stmt->bindValue(':limit', $recentServicesPaginationParams['page_size'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $recentServicesPaginationParams['offset'], PDO::PARAM_INT);
        $stmt->execute();
        $recentServices = $stmt->fetchAll();

        // 今月の全体統計(役務種別ごと)
        $currentMonth = date('n');
        $currentYear = date('Y');
        
        $monthlyStatsSql = "SELECT 
                            SUM(CASE WHEN service_type = 'regular' OR service_type IS NULL THEN service_hours ELSE 0 END) as regular_hours,
                            SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                            SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                            COUNT(CASE WHEN service_type = 'regular' OR service_type IS NULL THEN 1 END) as regular_count,
                            COUNT(CASE WHEN service_type = 'emergency' THEN 1 END) as emergency_count,
                            COUNT(CASE WHEN service_type = 'extension' THEN 1 END) as extension_count,
                            COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                            COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                            COUNT(CASE WHEN service_type = 'other' THEN 1 END) as other_count,
                            SUM(service_hours) as total_month_hours,
                            COUNT(*) as total_month_records
                        FROM service_records 
                        WHERE YEAR(service_date) = :year 
                        AND MONTH(service_date) = :month";
        
        $stmt = $db->prepare($monthlyStatsSql);
        $stmt->execute([
            'year' => $currentYear,
            'month' => $currentMonth
        ]);
        $monthlyStats = $stmt->fetch() ?: [
            'regular_hours' => 0,
            'emergency_hours' => 0,
            'extension_hours' => 0,
            'regular_count' => 0,
            'emergency_count' => 0,
            'extension_count' => 0,
            'document_count' => 0,
            'remote_consultation_count' => 0,
            'other_count' => 0,
            'total_month_hours' => 0,
            'total_month_records' => 0
        ];

        // 基本統計情報
        $stats = [
            'total_doctors' => count($userModel->findDoctors()),
            'total_companies' => count($companyModel->findActive()),
            'total_contracts' => count($contractModel->findActive()),
            'pending_services' => count($serviceModel->findAll(['status' => 'pending']))
        ];
        
        // 統計情報をマージ
        $stats = array_merge($stats, $monthlyStats);

        // ページネーション情報を計算
        $pendingTravelExpensesPaginationInfo = $this->calculatePagination(
            $pendingTravelExpensesTotalCount,
            $pendingTravelExpensesPaginationParams['page'],
            $pendingTravelExpensesPaginationParams['page_size']
        );
        
        $recentServicesPaginationInfo = $this->calculatePagination(
            $recentServicesTotalCount,
            $recentServicesPaginationParams['page'],
            $recentServicesPaginationParams['page_size']
        );
        
        // CSRFトークンの生成（存在しない場合）
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        $data = [
            'stats' => $stats,
            'recentServices' => $recentServices,
            'pendingTravelExpenses' => $pendingTravelExpenses,
            'pendingTravelExpensesPaginationInfo' => $pendingTravelExpensesPaginationInfo,
            'recentServicesPaginationInfo' => $recentServicesPaginationInfo,
            'twoFactorEnabled' => $twoFactorEnabled,
            'csrf_token' => $_SESSION['csrf_token']
        ];
        
        // 変数をビューで直接使えるようにする
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/dashboard/admin.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    /**
     * 契約の月締め状態をチェック
     */
    private function isMonthClosed($contractId, $year, $month) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // monthly_closing_records テーブルで確定済み（finalized）かどうかをチェック
        $sql = "SELECT COUNT(*) as count FROM monthly_closing_records 
                WHERE contract_id = :contract_id 
                AND closing_period = :closing_period
                AND status = 'finalized'";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'closing_period' => sprintf('%04d-%02d', $year, $month)
        ]);
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}
?>