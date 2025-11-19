<?php
// app/controllers/ContractController.php - タクシー可否対応版
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/ServiceRecord.php';

class ContractController extends BaseController {
    public function index() {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        $contractModel = new Contract();
        
        // ページネーション設定
        $perPageOptions = [10, 20, 50, 100];
        $perPage = (int)($_GET['per_page'] ?? 20);
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 20;
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        
        // 並び替えパラメータの取得と検証
        $sortColumn = $_GET['sort'] ?? 'company_name';
        $sortOrder = $_GET['order'] ?? 'asc';
        
        // 許可されたソートカラムの定義
        $allowedSortColumns = [
            'company_name',
            'service_date',
            'regular_visit_hours',
            'usage_percentage',
            'visit_frequency',
            'taxi_allowed',
            'tax_type',
            'contract_status',
            'effective_date',
            'start_date',
            'end_date'
        ];
        
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'company_name';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        // 産業医ユーザーの場合
        if ($userType === 'doctor') {
            $doctorId = Session::get('user_id');
            
            // フィルター処理
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $year = trim($_GET['year'] ?? '');
            $frequency = trim($_GET['frequency'] ?? '');
            $taxType = trim($_GET['tax_type'] ?? '');
            $showExpired = isset($_GET['show_expired']) ? (bool)$_GET['show_expired'] : false;
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 基本SQLクエリ
            $baseSql = "FROM contracts co
                        JOIN branches b ON co.branch_id = b.id
                        JOIN companies c ON co.company_id = c.id
                        WHERE co.doctor_id = :doctor_id";
            
            $params = ['doctor_id' => $doctorId];

            // 反映日による表示制御（現在の日付が反映日～反映終了日の範囲内の契約のみ）
            $baseSql .= " AND co.effective_date <= CURDATE()";
            $baseSql .= " AND (co.effective_end_date IS NULL OR co.effective_end_date >= CURDATE())";

            // 期限切れ契約の表示制御
            if (!$showExpired) {
                $baseSql .= " AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))";
            }
            
            // フィルター条件
            if (!empty($search)) {
                $baseSql .= " AND (c.name LIKE :search OR b.name LIKE :search2)";
                $params['search'] = "%{$search}%";
                $params['search2'] = "%{$search}%";
            }
            
            if (!empty($status)) {
                $baseSql .= " AND co.contract_status = :status";
                $params['status'] = $status;
            }
            
            if (!empty($year)) {
                $baseSql .= " AND YEAR(co.start_date) = :year";
                $params['year'] = $year;
            }

            if (!empty($frequency)) {
                $baseSql .= " AND co.visit_frequency = :frequency";
                $params['frequency'] = $frequency;
            }
            
            if (!empty($taxType)) {
                $baseSql .= " AND co.tax_type = :tax_type";
                $params['tax_type'] = $taxType;
            }
            
            // 総件数を取得
            $countSql = "SELECT COUNT(*) " . $baseSql;
            $stmt = $db->prepare($countSql);
            $stmt->execute($params);
            $totalCount = $stmt->fetchColumn();
            
            // ページネーション情報
            $totalPages = ceil($totalCount / $perPage);
            $page = min($page, max(1, $totalPages));
            $offset = ($page - 1) * $perPage;
            
            // ソートカラムのマッピング
            $sortColumnMap = [
                'company_name' => 'CONCAT(c.name, b.name)',
                'service_date' => 'co.start_date',
                'regular_visit_hours' => 'co.regular_visit_hours',
                'visit_frequency' => 'co.visit_frequency',
                'taxi_allowed' => 'co.taxi_allowed',
                'tax_type' => 'co.tax_type',
                'contract_status' => 'co.contract_status',
                'start_date' => 'co.start_date',
                'effective_date' => 'co.effective_date',
                'end_date' => 'co.end_date'
            ];
            
            $orderByColumn = $sortColumnMap[$sortColumn] ?? 'c.name';
            
            // データ取得用SQL
            $sql = "SELECT co.*, c.name as company_name, b.name as branch_name,
                    b.address as branch_address, b.phone as branch_phone, b.email as branch_email,
                    CASE 
                        WHEN co.end_date IS NOT NULL AND co.end_date < CURDATE() THEN 1 
                        ELSE 0 
                    END as is_expired
                    " . $baseSql . "
                    ORDER BY 
                        CASE WHEN co.end_date IS NOT NULL AND co.end_date < CURDATE() THEN 1 ELSE 0 END,
                        {$orderByColumn} {$sortOrder},
                        c.name ASC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $contracts = $stmt->fetchAll();
            
            // 契約ごとの今月の役務時間を計算(訪問頻度対応版)
            $serviceModel = new ServiceRecord();
            $currentMonth = date('n');
            $currentYear = date('Y');
            
            foreach ($contracts as &$contract) {
                // 契約終了判定
                $isContractExpired = !empty($contract['end_date']) && strtotime($contract['end_date']) < strtotime('today');
                $contract['is_contract_expired'] = $isContractExpired;
                
                // 訪問頻度情報を追加
                $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
                $contract['is_visit_month'] = is_visit_month($contract, $currentYear, $currentMonth);
                
                // タクシー可否のラベルを追加
                $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
                
                // 請求方法のラベルを追加
                $contract['tax_type_label'] = $contractModel->getTaxTypeLabel($contract['tax_type']);
                
                // 契約期限切れの場合は統計をゼロにして処理をスキップ
                if ($isContractExpired) {
                    $contract['regular_hours'] = 0;
                    $contract['emergency_hours'] = 0;
                    $contract['extension_hours'] = 0;
                    $contract['document_count'] = 0;
                    $contract['remote_consultation_count'] = 0;
                    $contract['other_count'] = 0;
                    $contract['total_hours'] = 0;
                    $contract['approved_hours'] = 0;
                    $contract['pending_hours'] = 0;
                    $contract['approved_no_time_count'] = 0;
                    $contract['pending_no_time_count'] = 0;
                    $contract['monthly_limit'] = 0;
                    $contract['remaining_hours'] = 0;
                    $contract['usage_percentage'] = 0;
                    $contract['this_month_hours'] = 0;
                    $contract['this_month_approved'] = 0;
                    $contract['this_month_pending'] = 0;
                    $contract['regular_hours_only'] = 0;
                    $contract['extension_hours_only'] = 0;
                    $contract['emergency_hours_only'] = 0;
                    continue;
                }
                
                // 今月の役務統計を役務種別ごとに取得
                $statsSql = "SELECT 
                                SUM(CASE WHEN service_type = 'regular' THEN service_hours ELSE 0 END) as regular_hours,
                                SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                                SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                                COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                                COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                                COUNT(CASE WHEN service_type = 'other' THEN 1 END) as other_count,
                                SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as total_hours,
                                SUM(CASE WHEN status = 'approved' AND service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as approved_hours,
                                SUM(CASE WHEN status = 'pending' AND service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as pending_hours,
                                COUNT(CASE WHEN status = 'approved' AND service_type IN ('document', 'remote_consultation', 'other') THEN 1 END) as approved_no_time_count,
                                COUNT(CASE WHEN status = 'pending' AND service_type IN ('document', 'remote_consultation', 'other') THEN 1 END) as pending_no_time_count
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
                    'approved_hours' => 0,
                    'pending_hours' => 0,
                    'approved_no_time_count' => 0,
                    'pending_no_time_count' => 0
                ];
                
                // 契約に統計情報を追加
                $contract = array_merge($contract, $stats);
                
                // 訪問頻度に応じた月間限度時間を計算
                $monthlyUsage = calculate_monthly_usage_by_frequency(
                    $contract['visit_frequency'] ?? 'monthly',
                    $contract['regular_visit_hours'],
                    $currentYear,
                    $currentMonth,
                    $contract,
                    $db
                );
                
                $contract['monthly_limit'] = $monthlyUsage['monthly_limit'];
                $contract['remaining_hours'] = max(0, $monthlyUsage['monthly_limit'] - $stats['regular_hours']);
                $contract['usage_percentage'] = $monthlyUsage['monthly_limit'] > 0 ? 
                    ($stats['regular_hours'] / $monthlyUsage['monthly_limit']) * 100 : 0;
                
                // 定期訪問 + 定期延長の時間を計算
                $regularAndExtensionHours = ($stats['regular_hours'] ?? 0) + ($stats['extension_hours'] ?? 0);

                // 互換性のため(ビューで使用される変数名を維持)
                $contract['this_month_hours'] = $regularAndExtensionHours;
                $contract['this_month_approved'] = $stats['approved_hours'];
                $contract['this_month_pending'] = $stats['pending_hours'];

                // 個別の時間も保持(必要に応じて)
                $contract['regular_hours_only'] = $stats['regular_hours'] ?? 0;
                $contract['extension_hours_only'] = $stats['extension_hours'] ?? 0;
                $contract['emergency_hours_only'] = $stats['emergency_hours'] ?? 0;
            }
            unset($contract);
            
            // usage_percentageで並び替える場合は、PHPで再ソート
            if ($sortColumn === 'usage_percentage') {
                usort($contracts, function($a, $b) use ($sortOrder) {
                    $aValue = $a['usage_percentage'] ?? 0;
                    $bValue = $b['usage_percentage'] ?? 0;
                    
                    if ($sortOrder === 'desc') {
                        return $bValue <=> $aValue;
                    } else {
                        return $aValue <=> $bValue;
                    }
                });
            }
            
            // ページネーション情報をビューに渡す
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'total_pages' => $totalPages,
                'per_page_options' => $perPageOptions
            ];
            
            // ソート情報をビューに渡す
            $sortInfo = [
                'sort_column' => $sortColumn,
                'sort_order' => $sortOrder
            ];
            
            ob_start();
            include __DIR__ . '/../views/contracts/doctor_index.php';
            $content = ob_get_clean();
            
            include __DIR__ . '/../views/layouts/base.php';
            return;
        }
        
        // 企業ユーザーの場合（修正版）
        if ($userType === 'company') {
            $companyId = Session::get('company_id');
            $userId = Session::get('user_id');
            
            // フィルター処理
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $year = trim($_GET['year'] ?? '');
            $frequency = trim($_GET['frequency'] ?? ''); // 訪問頻度フィルターを追加
            $showExpired = isset($_GET['show_expired']) ? (bool)$_GET['show_expired'] : false;
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 基本SQLクエリ
            $baseSql = "FROM contracts co
                        JOIN branches b ON co.branch_id = b.id
                        JOIN companies c ON co.company_id = c.id
                        JOIN users user_info ON co.doctor_id = user_info.id
                        JOIN user_branch_mappings ubm ON (b.id = ubm.branch_id AND ubm.user_id = :user_id AND ubm.is_active = 1)
                        WHERE co.company_id = :company_id
                        AND b.is_active = 1";
            
            $params = [
                'company_id' => $companyId,
                'user_id' => $userId
            ];
            
            // 反映日による表示制御（現在の日付が反映日～反映終了日の範囲内の契約のみ）
            $baseSql .= " AND co.effective_date <= CURDATE()";
            $baseSql .= " AND (co.effective_end_date IS NULL OR co.effective_end_date >= CURDATE())";

            // 期限切れ契約の表示制御
            if (!$showExpired) {
                $baseSql .= " AND (co.end_date IS NULL OR co.end_date >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01'))";
            }
            
            // フィルター条件
            if (!empty($search)) {
                $baseSql .= " AND (user_info.name LIKE :search OR b.name LIKE :search2)";
                $params['search'] = "%{$search}%";
                $params['search2'] = "%{$search}%";
            }
            
            if (!empty($status)) {
                $baseSql .= " AND co.contract_status = :status";
                $params['status'] = $status;
            }
            
            if (!empty($year)) {
                $baseSql .= " AND YEAR(co.start_date) = :year";
                $params['year'] = $year;
            }
            
            // 訪問頻度フィルターを追加
            if (!empty($frequency)) {
                $baseSql .= " AND co.visit_frequency = :frequency";
                $params['frequency'] = $frequency;
            }
            
            // 総件数を取得
            $countSql = "SELECT COUNT(*) " . $baseSql;
            $stmt = $db->prepare($countSql);
            $stmt->execute($params);
            $totalCount = $stmt->fetchColumn();
            
            // ページネーション情報
            $totalPages = ceil($totalCount / $perPage);
            $page = min($page, max(1, $totalPages));
            $offset = ($page - 1) * $perPage;
            
            // 並び替えパラメータの取得と検証（企業ユーザー用）
            $sortColumn = $_GET['sort'] ?? 'company_name';
            $sortOrder = $_GET['order'] ?? 'asc';

            // 許可されたソートカラムの定義
            $allowedSortColumns = [
                'company_name',
                'doctor_name',
                'service_date',
                'regular_visit_hours',
                'usage_percentage',
                'visit_frequency',
                'taxi_allowed',
                'tax_type',
                'contract_status',
                'effective_date',
                'start_date',
                'end_date'
            ];

            if (!in_array($sortColumn, $allowedSortColumns)) {
                $sortColumn = 'company_name';
            }

            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'asc';
            }

            // ソートカラムのマッピング
            $sortColumnMap = [
                'company_name' => 'CONCAT(b.name, user_info.name)',
                'doctor_name' => 'user_info.name',
                'service_date' => 'co.start_date',
                'regular_visit_hours' => 'co.regular_visit_hours',
                'visit_frequency' => 'co.visit_frequency',
                'taxi_allowed' => 'co.taxi_allowed',
                'tax_type' => 'co.tax_type',
                'contract_status' => 'co.contract_status',
                'effective_date' => 'co.effective_date',
                'start_date' => 'co.start_date',
                'end_date' => 'co.end_date'
            ];

            $orderByColumn = $sortColumnMap[$sortColumn] ?? 'b.name';

            // データ取得用SQL
            $sql = "SELECT co.*, user_info.name as doctor_name, b.name as branch_name,
                    c.name as company_name, b.address as branch_address, 
                    b.phone as branch_phone, b.email as branch_email,
                    user_info.email as doctor_email,
                    CASE 
                        WHEN co.end_date IS NOT NULL AND co.end_date < CURDATE() THEN 1 
                        ELSE 0 
                    END as is_expired
                    " . $baseSql . "
                    ORDER BY 
                        CASE WHEN co.end_date IS NOT NULL AND co.end_date < CURDATE() THEN 1 ELSE 0 END,
                        {$orderByColumn} {$sortOrder}, CONCAT(b.name, user_info.name) ASC, co.effective_date ASC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $contracts = $stmt->fetchAll();
            
            // 契約ごとの今月の役務時間を計算（企業用）
            $currentMonth = date('n');
            $currentYear = date('Y');
            
            foreach ($contracts as &$contract) {
                // 契約終了判定
                $isContractExpired = !empty($contract['end_date']) && strtotime($contract['end_date']) < strtotime('today');
                $contract['is_contract_expired'] = $isContractExpired;
                
                // 訪問頻度情報を追加
                $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
                $contract['is_visit_month'] = is_visit_month($contract, $currentYear, $currentMonth);
                
                // タクシー可否のラベルを追加
                $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
                
                // 請求方法のラベルを追加
                $contract['tax_type_label'] = $contractModel->getTaxTypeLabel($contract['tax_type']);
                
                // 週間スケジュール情報を取得（追加）
                if (($contract['visit_frequency'] ?? 'monthly') === 'weekly') {
                    $weeklyDaysSql = "SELECT day_of_week FROM contract_weekly_schedules WHERE contract_id = :contract_id ORDER BY day_of_week";
                    $weeklyStmt = $db->prepare($weeklyDaysSql);
                    $weeklyStmt->execute(['contract_id' => $contract['id']]);
                    $contract['weekly_days'] = array_column($weeklyStmt->fetchAll(), 'day_of_week');
                    
                    // 曜日名の文字列も生成
                    if (!empty($contract['weekly_days'])) {
                        $dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                        $contract['weekly_schedule'] = implode('・', array_map(function($day) use ($dayNames) {
                            return $dayNames[$day] ?? '';
                        }, $contract['weekly_days']));
                    } else {
                        $contract['weekly_schedule'] = '';
                    }

                    // 非訪問日設定の情報を取得
                    $currentYear = date('Y');
                    $nonVisitSql = "SELECT COUNT(*) as count FROM contract_non_visit_days 
                                    WHERE contract_id = :contract_id 
                                    AND year = :year 
                                    AND is_active = 1";
                    $stmt = $db->prepare($nonVisitSql);
                    $stmt->execute(['contract_id' => $contract['id'], 'year' => $currentYear]);
                    $contract['non_visit_count'] = $stmt->fetchColumn();

                } else {
                    $contract['weekly_days'] = [];
                    $contract['weekly_schedule'] = '';
                    $contract['non_visit_count'] = 0;
                }
                
                // 契約期限切れの場合は統計をゼロにして処理をスキップ
                if ($isContractExpired) {
                    $contract['regular_hours'] = 0;
                    $contract['emergency_hours'] = 0;
                    $contract['extension_hours'] = 0;
                    $contract['document_count'] = 0;
                    $contract['remote_consultation_count'] = 0;
                    $contract['other_count'] = 0;
                    $contract['total_hours'] = 0;
                    $contract['approved_hours'] = 0;
                    $contract['pending_hours'] = 0;
                    $contract['rejected_hours'] = 0;
                    $contract['approved_no_time_count'] = 0;
                    $contract['pending_no_time_count'] = 0;
                    $contract['rejected_no_time_count'] = 0;
                    $contract['monthly_limit'] = 0;
                    $contract['remaining_hours'] = 0;
                    $contract['usage_percentage'] = 0;
                    $contract['this_month_hours'] = 0;
                    $contract['this_month_approved'] = 0;
                    $contract['this_month_pending'] = 0;
                    continue;
                }
                
                // 今月の役務統計を役務種別ごとに取得
                $statsSql = "SELECT 
                                SUM(CASE WHEN service_type = 'regular' THEN service_hours ELSE 0 END) as regular_hours,
                                SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                                SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                                COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                                COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                                COUNT(CASE WHEN service_type = 'other' THEN 1 END) as other_count,
                                SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as total_hours,
                                SUM(CASE WHEN status = 'approved' AND service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as approved_hours,
                                SUM(CASE WHEN status = 'pending' AND service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as pending_hours,
                                SUM(CASE WHEN status = 'rejected' AND service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as rejected_hours,
                                COUNT(CASE WHEN status = 'approved' AND service_type IN ('document', 'remote_consultation', 'other') THEN 1 END) as approved_no_time_count,
                                COUNT(CASE WHEN status = 'pending' AND service_type IN ('document', 'remote_consultation', 'other') THEN 1 END) as pending_no_time_count,
                                COUNT(CASE WHEN status = 'rejected' AND service_type IN ('document', 'remote_consultation', 'other') THEN 1 END) as rejected_no_time_count
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
                    'approved_hours' => 0,
                    'pending_hours' => 0,
                    'rejected_hours' => 0,
                    'approved_no_time_count' => 0,
                    'pending_no_time_count' => 0,
                    'rejected_no_time_count' => 0
                ];
                
                // 契約に統計情報を追加
                $contract = array_merge($contract, $stats);
                
                // 訪問頻度に応じた月間限度時間を計算
                $monthlyUsage = calculate_monthly_usage_by_frequency(
                    $contract['visit_frequency'] ?? 'monthly',
                    $contract['regular_visit_hours'],
                    $currentYear,
                    $currentMonth,
                    $contract,
                    $db
                );
                
                $contract['monthly_limit'] = $monthlyUsage['monthly_limit'];
                $contract['remaining_hours'] = max(0, $monthlyUsage['monthly_limit'] - $stats['regular_hours']);
                $contract['usage_percentage'] = $monthlyUsage['monthly_limit'] > 0 ? 
                    ($stats['regular_hours'] / $monthlyUsage['monthly_limit']) * 100 : 0;
                
                // 定期訪問 + 定期延長の時間を計算
                $regularAndExtensionHours = ($stats['regular_hours'] ?? 0) + ($stats['extension_hours'] ?? 0);

                // 互換性のため
                $contract['this_month_hours'] = $regularAndExtensionHours;
                $contract['this_month_approved'] = $stats['approved_hours'];
                $contract['this_month_pending'] = $stats['pending_hours'];
            }
            unset($contract);
            
            // ページネーション情報をビューに渡す
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'total_pages' => $totalPages,
                'per_page_options' => $perPageOptions
            ];
            
            ob_start();
            include __DIR__ . '/../views/contracts/company_index.php';
            $content = ob_get_clean();
            
            include __DIR__ . '/../views/layouts/base.php';
            return;
        }
        
        // 管理者の場合
        Session::requireUserType('admin');

        // 検索パラメータを取得
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $frequency = trim($_GET['frequency'] ?? '');
        $taxType = trim($_GET['tax_type'] ?? '');
        $year = trim($_GET['year'] ?? '');
        $showExpired = isset($_GET['show_expired']) ? (bool)$_GET['show_expired'] : false;

        // 並び替えパラメータの取得と検証
        $sortColumn = $_GET['sort'] ?? 'company_name';
        $sortOrder = $_GET['order'] ?? 'asc';

        // 許可されたソートカラムの定義
        $allowedSortColumns = [
            'company_name',
            'doctor_name',
            'visit_frequency',
            'regular_visit_hours',
            'taxi_allowed',
            'tax_type',
            'contract_status',
            'effective_date',
            'start_date',
            'end_date'
        ];

        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'company_name';
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();

        $baseSql = "FROM contracts c
                    JOIN users u ON c.doctor_id = u.id
                    JOIN companies comp ON c.company_id = comp.id
                    JOIN branches b ON c.branch_id = b.id
                    WHERE 1=1";

        $params = [];

        // 期限切れ契約の表示制御
        if (!$showExpired) {
            $baseSql .= " AND (c.end_date IS NULL OR c.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01'))";
        }

        // 検索条件
        if ($search !== '') {
            $baseSql .= " AND (u.name LIKE :search OR comp.name LIKE :search2 OR b.name LIKE :search3)";
            $params['search'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
        }

        // ステータスフィルター
        if ($status !== '') {
            $baseSql .= " AND c.contract_status = :status";
            $params['status'] = $status;
        }

        // 訪問頻度フィルター
        if ($frequency !== '') {
            $baseSql .= " AND c.visit_frequency = :frequency";
            $params['frequency'] = $frequency;
        }

        // 税種別フィルター
        if ($taxType !== '') {
            $baseSql .= " AND c.tax_type = :tax_type";
            $params['tax_type'] = $taxType;
        }

        // 年フィルター
        if ($year !== '') {
            $baseSql .= " AND YEAR(c.start_date) = :year";
            $params['year'] = $year;
        }

        // 総件数を取得
        $countSql = "SELECT COUNT(*) " . $baseSql;
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $totalCount = $stmt->fetchColumn();

        // ページネーション情報
        $totalPages = ceil($totalCount / $perPage);
        $page = min($page, max(1, $totalPages));
        $offset = ($page - 1) * $perPage;

        // ソートカラムのマッピング
        $sortColumnMap = [
            'company_name' => 'CONCAT(comp.name, b.name)',
            'doctor_name' => 'u.name',
            'visit_frequency' => 'c.visit_frequency',
            'regular_visit_hours' => 'c.regular_visit_hours',
            'taxi_allowed' => 'c.taxi_allowed',
            'tax_type' => 'c.tax_type',
            'contract_status' => 'c.contract_status',
            'effective_date' => 'c.effective_date',
            'start_date' => 'c.start_date',
            'end_date' => 'c.end_date'
        ];

        $orderByColumn = $sortColumnMap[$sortColumn] ?? 'comp.name';

        $sql = "SELECT c.*, 
                u.name as doctor_name,
                comp.name as company_name,
                b.name as branch_name,
                CASE 
                    WHEN c.end_date IS NOT NULL AND c.end_date < CURDATE() THEN 1 
                    ELSE 0 
                END as is_expired
                " . $baseSql . "
                ORDER BY 
                    CASE WHEN c.end_date IS NOT NULL AND c.end_date < CURDATE() THEN 1 ELSE 0 END,
                    {$orderByColumn} {$sortOrder},
                    comp.name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $contracts = $stmt->fetchAll();

        // 各契約に訪問頻度情報とタクシー可否情報、請求方法情報を追加
        foreach ($contracts as &$contract) {
            // 契約終了判定
            $isContractExpired = !empty($contract['end_date']) && strtotime($contract['end_date']) < strtotime('today');
            $contract['is_contract_expired'] = $isContractExpired;
            
            $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            $contract['tax_type_label'] = $contractModel->getTaxTypeLabel($contract['tax_type']);
            
            // 週間スケジュールの情報を追加
            if (($contract['visit_frequency'] ?? 'monthly') === 'weekly') {
                $weeklyDaysSql = "SELECT day_of_week FROM contract_weekly_schedules WHERE contract_id = :contract_id ORDER BY day_of_week";
                $weeklyStmt = $db->prepare($weeklyDaysSql);
                $weeklyStmt->execute(['contract_id' => $contract['id']]);
                $contract['weekly_days'] = array_column($weeklyStmt->fetchAll(), 'day_of_week');
                
                // 曜日名の文字列も生成
                if (!empty($contract['weekly_days'])) {
                    $dayNames = ['', '月', '火', '水', '木', '金', '土', '日'];
                    $contract['weekly_schedule'] = implode('・', array_map(function($day) use ($dayNames) {
                        return $dayNames[$day] ?? '';
                    }, $contract['weekly_days']));
                } else {
                    $contract['weekly_schedule'] = '';
                }

                // 非訪問日設定の情報を取得
                $currentYear = date('Y');
                $nonVisitSql = "SELECT COUNT(*) as count FROM contract_non_visit_days 
                                WHERE contract_id = :contract_id 
                                AND year = :year 
                                AND is_active = 1";
                $stmt = $db->prepare($nonVisitSql);
                $stmt->execute(['contract_id' => $contract['id'], 'year' => $currentYear]);
                $contract['non_visit_count'] = $stmt->fetchColumn();

            } else {
                $contract['weekly_days'] = [];
                $contract['weekly_schedule'] = '';
                $contract['non_visit_count'] = 0;
            }
        }
        unset($contract);

        // ページネーション情報をビューに渡す
        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_count' => $totalCount,
            'total_pages' => $totalPages,
            'per_page_options' => $perPageOptions
        ];

        // ソート情報をビューに渡す
        $sortInfo = [
            'sort_column' => $sortColumn,
            'sort_order' => $sortOrder
        ];

        ob_start();
        include __DIR__ . '/../views/contracts/index.php';
        $content = ob_get_clean();

        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function create() {
        Session::requireUserType('admin');
        
        $userModel = new User();
        $companyModel = new Company();
        
        $doctors = $userModel->findDoctors();
        $companies = $companyModel->findActive();
        
        ob_start();
        include __DIR__ . '/../views/contracts/create.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function store() {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect('contracts/create');
        }
        
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $companyId = (int)($_POST['company_id'] ?? 0);
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $regularVisitHours = (float)($_POST['regular_visit_hours'] ?? 0);
        $visitFrequency = $_POST['visit_frequency'] ?? '';
        $bimonthlyType = $_POST['bimonthly_type'] ?? null; // 月次区分（新規追加）
        $excludeHolidays = (int)($_POST['exclude_holidays'] ?? 1);
        $taxiAllowed = (int)($_POST['taxi_allowed'] ?? 0);
        $taxType = $_POST['tax_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';

        // 料金設定の取得
        $regularVisitRate = (int)($_POST['regular_visit_rate'] ?? 0);
        $regularExtensionRate = (int)($_POST['regular_extension_rate'] ?? 0);
        $emergencyVisitRate = (int)($_POST['emergency_visit_rate'] ?? 0);
        $spotRate = (int)($_POST['spot_rate'] ?? 0);
        
        // 遠隔相談・書面作成フラグの取得
        $useRemoteConsultation = (int)($_POST['use_remote_consultation'] ?? 0);
        $useDocumentCreation = (int)($_POST['use_document_creation'] ?? 0);
        
        // どちらもOFFの場合は料金をNULLに、そうでなければ入力値を使用
        $documentConsultationRate = ($useRemoteConsultation || $useDocumentCreation) 
            ? (int)($_POST['document_consultation_rate'] ?? 0) 
            : null;

        $exclusiveRegistrationFee = (int)($_POST['exclusive_registration_fee'] ?? 0);
        // 週間スケジュールの処理
        $weeklyDays = [];
        if ($visitFrequency === 'weekly') {
            $weeklyDays = $_POST['weekly_days'] ?? [];
            if (!is_array($weeklyDays)) {
                $weeklyDays = [];
            }
            $weeklyDays = array_map('intval', array_filter($weeklyDays));
        }
            
        // バリデーション
        $errors = [];
        
        if (empty($doctorId)) {
            $errors[] = '産業医を選択してください。';
        }
        
        if (empty($companyId)) {
            $errors[] = '企業を選択してください。';
        }
        
        if (empty($branchId)) {
            $errors[] = '拠点を選択してください。';
        }
        
        // スポット契約の場合は定期訪問時間の検証をスキップ
        if ($visitFrequency !== 'spot' && $regularVisitHours <= 0) {
            $errors[] = '定期訪問時間を正しく入力してください。';
        }
        
        if (empty($visitFrequency)) {
            $errors[] = '訪問頻度を選択してください。';
        } elseif (!validate_visit_frequency($visitFrequency)) {
            $errors[] = '訪問頻度が不正です。';
        }
        
        // 隔月訪問の月次区分バリデーション（新規追加）
        if ($visitFrequency === 'bimonthly') {
            if (empty($bimonthlyType)) {
                $errors[] = '隔月訪問では月次区分（偶数月/奇数月）を選択してください。';
            } elseif (!in_array($bimonthlyType, ['even', 'odd'])) {
                $errors[] = '月次区分が不正です。';
            }
        }
        
        // 料金設定のバリデーション
        if ($visitFrequency === 'spot') {
            // スポット契約の場合はスポット料金のみ必須
            if ($spotRate <= 0) {
                $errors[] = 'スポット契約ではスポット料金を入力してください。';
            }
        } else {
            // 通常契約の場合は定期・延長・臨時料金を検証
            if ($regularVisitRate < 0) {
                $errors[] = '定期訪問料金は0以上で入力してください。';
            }

            if ($regularExtensionRate < 0) {
                $errors[] = '定期延長料金は0以上で入力してください。';
            }
            
            if ($emergencyVisitRate < 0) {
                $errors[] = '臨時訪問料金は0以上で入力してください。';
            }
        }
        
        // 遠隔相談・書面作成の料金バリデーション
        if (($useRemoteConsultation || $useDocumentCreation) && $documentConsultationRate < 0) {
            $errors[] = '書面作成・遠隔相談料金は0以上で入力してください。';
        }
        
        // 税種別のバリデーション
        if (empty($taxType)) {
            $errors[] = '税種別を選択してください。';
        } elseif (!in_array($taxType, ['exclusive', 'inclusive'])) {
            $errors[] = '税種別が不正です。';
        }

        // 週間スケジュールのバリデーション
        if ($visitFrequency === 'weekly') {
            require_once __DIR__ . '/../core/helpers.php';
            $scheduleValidation = validate_weekly_schedule($weeklyDays);
            if (!$scheduleValidation['valid']) {
                $errors[] = $scheduleValidation['error'];
            }
        }

        if (empty($startDate)) {
            $errors[] = '開始日を入力してください。';
        } else {
            // 開始日が月初（1日）かチェック
            $startDateTime = new DateTime($startDate);
            if ($startDateTime->format('j') !== '1') {
                $errors[] = '契約開始日は毎月1日を指定してください。';
            }
        }
        
        // 重複チェック
        $contractModel = new Contract();
        if ($contractModel->checkDuplicateContract($doctorId, $branchId)) {
            $errors[] = '選択された産業医と拠点の組み合わせで既に有効な契約が存在します。';
        }
        
        // ファイルアップロード処理
        $fileInfo = null;
        if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
            $fileValidation = $this->validateUploadedFile($_FILES['contract_file']);
            if ($fileValidation['success']) {
                $fileInfo = $this->saveUploadedFile($_FILES['contract_file'], $companyId, $branchId);
                if (!$fileInfo) {
                    $errors[] = 'ファイルのアップロードに失敗しました。';
                }
            } else {
                $errors[] = $fileValidation['error'];
            }
        } elseif (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = $this->getUploadErrorMessage($_FILES['contract_file']['error']);
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            if ($fileInfo && file_exists($fileInfo['file_path'])) {
                unlink($fileInfo['file_path']);
            }
            redirect('contracts/create');
        }
        
        $data = [
            'doctor_id' => $doctorId,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'regular_visit_hours' => $visitFrequency === 'spot' ? null : $regularVisitHours,
            'visit_frequency' => $visitFrequency,
            'bimonthly_type' => $visitFrequency === 'bimonthly' ? $bimonthlyType : null, // 月次区分を追加
            'exclude_holidays' => $excludeHolidays,
            'taxi_allowed' => $taxiAllowed,
            'tax_type' => $taxType,
            'start_date' => $startDate,
            'end_date' => $endDate ?: null,
            'contract_status' => 'active',
            // 料金設定を追加
            'regular_visit_rate' => $visitFrequency !== 'spot' && $regularVisitRate > 0 ? $regularVisitRate : null,
            'regular_extension_rate' => $visitFrequency !== 'spot' && $regularExtensionRate > 0 ? $regularExtensionRate : null,
            'emergency_visit_rate' => $visitFrequency !== 'spot' && $emergencyVisitRate > 0 ? $emergencyVisitRate : null,
            'spot_rate' => $visitFrequency === 'spot' && $spotRate > 0 ? $spotRate : null,
            'document_consultation_rate' => $documentConsultationRate,
            'exclusive_registration_fee' => $exclusiveRegistrationFee > 0 ? $exclusiveRegistrationFee : null,
            // 遠隔相談・書面作成フラグを追加
            'use_remote_consultation' => $useRemoteConsultation,
            'use_document_creation' => $useDocumentCreation
        ];
        
        if ($contractModel->createWithWeeklySchedule($data, $weeklyDays, $fileInfo)) {
            $frequencyLabel = get_visit_frequency_label($visitFrequency);
            $taxiLabel = $taxiAllowed ? 'タクシー利用可' : 'タクシー利用不可';
            $taxLabel = $contractModel->getTaxTypeLabel($taxType);
            $excludeHolidaysLabel = $excludeHolidays ? '祝日非訪問' : '祝日訪問可';
        
            $message = "契約を作成しました。(訪問頻度: {$frequencyLabel}、{$taxiLabel}、請求方法: {$taxLabel}";
            
            // 隔月訪問の場合は月次区分情報も追加
            if ($visitFrequency === 'bimonthly' && $bimonthlyType) {
                $bimonthlyLabel = $bimonthlyType === 'even' ? '偶数月訪問' : '奇数月訪問';
                $message .= "、{$bimonthlyLabel}";
            }
            
            // 週間契約の場合は祝日訪問情報も追加
            if ($visitFrequency === 'weekly') {
                $message .= "、{$excludeHolidaysLabel}";
            }
            
            $message .= ")";
            
            // 料金設定の情報を追加
            if ($regularVisitRate > 0 || $emergencyVisitRate > 0 || $documentConsultationRate > 0) {
                $rateInfo = [];
                if ($spotRate > 0) {
                    $rateInfo[] = "スポット: " . number_format($spotRate) . "円/15分";
                }
                if ($regularVisitRate > 0) {
                    $rateInfo[] = "定期訪問: " . number_format($regularVisitRate) . "円/時";
                }
                if ($regularExtensionRate > 0) {
                    $rateInfo[] = "定期延長: " . number_format($regularExtensionRate) . "円/15分";
                }
                if ($emergencyVisitRate > 0) {
                    $rateInfo[] = "臨時訪問: " . number_format($emergencyVisitRate) . "円/15分";
                }
                if ($documentConsultationRate > 0) {
                    $rateInfo[] = "書面・遠隔: " . number_format($documentConsultationRate) . "円/回";
                }
                if (!empty($rateInfo)) {
                    $message .= " 料金設定: " . implode('、', $rateInfo);
                }
            }
            
            // 週間スケジュールの情報を追加
            if ($visitFrequency === 'weekly' && !empty($weeklyDays)) {
                require_once __DIR__ . '/../core/helpers.php';
                $scheduleText = format_weekly_schedule($weeklyDays);
                $message .= " 週間スケジュール: {$scheduleText}";
            }
            
            $this->setFlash('success', $message);
            redirect('contracts');
        } else {
            $this->setFlash('error', '契約の作成に失敗しました。');
            if ($fileInfo && file_exists($fileInfo['file_path'])) {
                unlink($fileInfo['file_path']);
            }
            redirect('contracts/create');
        }
    }

    /**
     * 契約の月間時間を計算（週間対応版）
     */
    private function calculateMonthlyHours($contract, $year, $month) {
        require_once __DIR__ . '/../core/helpers.php';
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        return calculate_monthly_usage_by_frequency(
            $contract['visit_frequency'] ?? 'monthly',
            $contract['regular_visit_hours'],
            $year,
            $month,
            $contract,
            $db
        );
    }

    /**
     * 週間契約の一覧表示用データを準備
     */
    private function prepareContractDataWithWeekly($contracts) {
        require_once __DIR__ . '/../core/helpers.php';
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        foreach ($contracts as &$contract) {
            // 基本的な訪問頻度情報
            $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
            $contract['taxi_allowed_label'] = $contract['taxi_allowed'] ? '可' : '不可';
            
            // 週間契約の場合は追加情報を取得
            if ($contract['visit_frequency'] === 'weekly') {
                $weeklyDays = get_weekly_schedule($contract['id'], $db);
                $contract['weekly_days'] = $weeklyDays;
                $contract['weekly_schedule'] = format_weekly_schedule($weeklyDays);
                
                // 今月の訪問予定詳細
                $weeklyDetails = get_weekly_contract_details($contract['id'], $currentYear, $currentMonth, $db);
                $contract['weekly_details'] = $weeklyDetails;
                $contract['monthly_visits'] = $weeklyDetails['total_visits'];
            }
            
            // 月間使用量を計算
            $monthlyUsage = $this->calculateMonthlyHours($contract, $currentYear, $currentMonth);
            $contract['monthly_limit'] = $monthlyUsage['monthly_limit'];
            $contract['is_visit_month'] = $monthlyUsage['is_visit_month'];
        }
        unset($contract);
        
        return $contracts;
    }

    public function edit($id) {
        Session::requireUserType('admin');
        
        $contractModel = new Contract();
        $contract = $contractModel->findWithDetails($id);
        
        if (!$contract) {
            $this->setFlash('error', '契約が見つかりません。');
            redirect('contracts');
        }
        
        $userModel = new User();
        $companyModel = new Company();
        
        $doctors = $userModel->findDoctors();
        $companies = $companyModel->findActive();
        
        // 現在の契約の企業に紐づく拠点を取得
        require_once __DIR__ . '/../models/Branch.php';
        $branchModel = new Branch();
        $currentBranches = $branchModel->findByCompany($contract['company_id']);
        
        // 全ての拠点データを取得（JavaScript用）
        $allBranches = $branchModel->findAll();
        
        // 役務記録統計を取得（訪問頻度対応版）
        $serviceModel = new ServiceRecord();
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $statsSql = "SELECT 
                        SUM(CASE WHEN service_type = 'regular' THEN service_hours ELSE 0 END) as regular_hours,
                        SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                        SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                        COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                        COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                        COUNT(CASE WHEN service_type = 'other' THEN 1 END) as other_count,
                        SUM(CASE WHEN service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as total_hours,
                        SUM(CASE WHEN status = 'approved' AND service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as approved_hours,
                        SUM(CASE WHEN status = 'pending' AND service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as pending_hours,
                        SUM(CASE WHEN status = 'rejected' AND service_type IN ('regular', 'emergency', 'extension') THEN service_hours ELSE 0 END) as rejected_hours,
                        COUNT(CASE WHEN status = 'approved' AND service_type IN ('document', 'remote_consultation', 'other') THEN 1 END) as approved_no_time_count,
                        COUNT(CASE WHEN status = 'pending' AND service_type IN ('document', 'remote_consultation', 'other') THEN 1 END) as pending_no_time_count,
                        COUNT(CASE WHEN status = 'rejected' AND service_type IN ('document', 'remote_consultation', 'other') THEN 1 END) as rejected_no_time_count
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
            'approved_hours' => 0,
            'pending_hours' => 0,
            'rejected_hours' => 0,
            'approved_no_time_count' => 0,
            'pending_no_time_count' => 0,
            'rejected_no_time_count' => 0
        ];
        
        // 統計データを整理（訪問頻度対応版）
        $serviceStats = [
            'regular_visit_hours' => $stats['total_hours'],
            'regular_hours' => $stats['regular_hours'],
            'emergency_hours' => $stats['emergency_hours'],
            'extension_hours' => $stats['extension_hours'],
            'document_count' => $stats['document_count'],
            'remote_consultation_count' => $stats['remote_consultation_count'],
            'other_count' => $stats['other_count'],
            'pending_hours' => $stats['pending_hours'],
            'approved_hours' => $stats['approved_hours'],
            'rejected_hours' => $stats['rejected_hours'],
            'approved_no_time_count' => $stats['approved_no_time_count'],
            'pending_no_time_count' => $stats['pending_no_time_count'],
            'rejected_no_time_count' => $stats['rejected_no_time_count']
        ];
        
        // 訪問頻度情報を追加
        $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
        $contract['is_visit_month'] = is_visit_month($contract, $currentYear, $currentMonth);
        
        ob_start();
        include __DIR__ . '/../views/contracts/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    public function update($id) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect("contracts/{$id}/edit");
        }
        
        $contractModel = new Contract();
        $contract = $contractModel->findById($id);
        
        if (!$contract) {
            $this->setFlash('error', '契約が見つかりません。');
            redirect('contracts');
        }
        
        $doctorId = $contract['doctor_id'];
        $companyId = $contract['company_id'];
        $branchId = $contract['branch_id'];

        $regularVisitHours = (float)($_POST['regular_visit_hours'] ?? 0);
        $visitFrequency = $_POST['visit_frequency'] ?? '';
        $bimonthlyType = $_POST['bimonthly_type'] ?? null;
        $excludeHolidays = (int)($_POST['exclude_holidays'] ?? 1);
        $taxiAllowed = (int)($_POST['taxi_allowed'] ?? 0);
        $taxType = $_POST['tax_type'] ?? '';
        $startDate = $contract['start_date']; // 開始日は変更不可
        $endDate = $_POST['end_date'] ?? '';
        $contractStatus = $_POST['contract_status'] ?? 'active';
        $removeFile = (int)($_POST['remove_file'] ?? 0);
        $effectiveDate = $_POST['effective_date'] ?? ''; // 反映日を取得

        // 料金設定の取得
        $regularVisitRate = (int)($_POST['regular_visit_rate'] ?? 0);
        $regularExtensionRate = (int)($_POST['regular_extension_rate'] ?? 0);
        $emergencyVisitRate = (int)($_POST['emergency_visit_rate'] ?? 0);
        $spotRate = (int)($_POST['spot_rate'] ?? 0);
        
        // 遠隔相談・書面作成フラグの取得
        $useRemoteConsultation = (int)($_POST['use_remote_consultation'] ?? 0);
        $useDocumentCreation = (int)($_POST['use_document_creation'] ?? 0);
        
        // どちらもOFFの場合は料金をNULLに、そうでなければ入力値を使用
        $documentConsultationRate = ($useRemoteConsultation || $useDocumentCreation) 
            ? (int)($_POST['document_consultation_rate'] ?? 0) 
            : null;
            
        $exclusiveRegistrationFee = (int)($_POST['exclusive_registration_fee'] ?? 0);

        $weeklyDays = [];
        if ($visitFrequency === 'weekly') {
            $weeklyDays = $_POST['weekly_days'] ?? [];
            if (!is_array($weeklyDays)) {
                $weeklyDays = [];
            }
            $weeklyDays = array_map('intval', array_filter($weeklyDays));
        }

        // バリデーション
        $errors = [];
        
        // スポット契約の場合は定期訪問時間の検証をスキップ
        if ($visitFrequency !== 'spot' && $regularVisitHours <= 0) {
            $errors[] = '定期訪問時間を正しく入力してください。';
        }
        
        if (empty($visitFrequency)) {
            $errors[] = '訪問頻度を選択してください。';
        } elseif (!validate_visit_frequency($visitFrequency)) {
            $errors[] = '訪問頻度が不正です。';
        }
        
        // 隔月訪問の月次区分バリデーション
        if ($visitFrequency === 'bimonthly') {
            if (empty($bimonthlyType)) {
                $errors[] = '隔月訪問では月次区分(偶数月/奇数月)を選択してください。';
            } elseif (!in_array($bimonthlyType, ['even', 'odd'])) {
                $errors[] = '月次区分が不正です。';
            }
        }
        
        // 反映日のバリデーション
        if (empty($effectiveDate)) {
            $errors[] = '反映日を入力してください。';
        } else {
            $effectiveDateObj = new DateTime($effectiveDate);
            if ($effectiveDateObj->format('j') !== '1') {
                $errors[] = '反映日は毎月1日を指定してください。';
            }
            
            $startDateObj = new DateTime($startDate);
            if ($effectiveDateObj < $startDateObj) {
                $errors[] = '反映日は契約開始日以降を指定してください。';
            }
            
            if (!empty($endDate)) {
                $endDateObj = new DateTime($endDate);
                if ($effectiveDateObj >= $endDateObj) {
                    $errors[] = '反映日は契約終了日より前を指定してください。';
                }
            }
        }
        
        // 料金設定のバリデーション
        if ($visitFrequency === 'spot') {
            // スポット契約の場合はスポット料金のみ必須
            if ($spotRate <= 0) {
                $errors[] = 'スポット契約ではスポット料金を入力してください。';
            }
        } else {
            // 通常契約の場合は定期・延長・臨時料金を検証
            if ($regularVisitRate < 0) {
                $errors[] = '定期訪問料金は0以上で入力してください。';
            }

            if ($regularExtensionRate < 0) {
                $errors[] = '定期延長料金は0以上で入力してください。';
            }
            
            if ($emergencyVisitRate < 0) {
                $errors[] = '臨時訪問料金は0以上で入力してください。';
            }
        }
        
        // 遠隔相談・書面作成の料金バリデーション
        if (($useRemoteConsultation || $useDocumentCreation) && $documentConsultationRate < 0) {
            $errors[] = '書面作成・遠隔相談料金は0以上で入力してください。';
        }
        
        // 税種別のバリデーション
        if (empty($taxType)) {
            $errors[] = '税種別を選択してください。';
        } elseif (!in_array($taxType, ['exclusive', 'inclusive'])) {
            $errors[] = '税種別が不正です。';
        }
        
        // 週間スケジュールのバリデーション
        if ($visitFrequency === 'weekly') {
            require_once __DIR__ . '/../core/helpers.php';
            $scheduleValidation = validate_weekly_schedule($weeklyDays);
            if (!$scheduleValidation['valid']) {
                $errors[] = $scheduleValidation['error'];
            }
        }
        
        // ファイルアップロード処理
        $fileInfo = null;
        if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
            $fileValidation = $this->validateUploadedFile($_FILES['contract_file']);
            if ($fileValidation['success']) {
                $fileInfo = $this->saveUploadedFile($_FILES['contract_file'], $companyId, $branchId);
                if (!$fileInfo) {
                    $errors[] = 'ファイルのアップロードに失敗しました。';
                }
            } else {
                $errors[] = $fileValidation['error'];
            }
        } elseif (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = $this->getUploadErrorMessage($_FILES['contract_file']['error']);
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            if ($fileInfo && file_exists($fileInfo['file_path'])) {
                unlink($fileInfo['file_path']);
            }
            redirect("contracts/{$id}/edit");
        }
        
        $data = [
            'doctor_id' => $doctorId,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'regular_visit_hours' => $visitFrequency === 'spot' ? null : $regularVisitHours,
            'visit_frequency' => $visitFrequency,
            'bimonthly_type' => $visitFrequency === 'bimonthly' ? $bimonthlyType : null,
            'exclude_holidays' => $excludeHolidays,
            'taxi_allowed' => $taxiAllowed,
            'tax_type' => $taxType,
            'start_date' => $startDate,
            'end_date' => $endDate ?: null,
            'contract_status' => $contractStatus,
            'effective_date' => $effectiveDate, // 反映日を追加
            'regular_visit_rate' => $visitFrequency !== 'spot' && $regularVisitRate > 0 ? $regularVisitRate : null,
            'regular_extension_rate' => $visitFrequency !== 'spot' && $regularExtensionRate > 0 ? $regularExtensionRate : null,
            'emergency_visit_rate' => $visitFrequency !== 'spot' && $emergencyVisitRate > 0 ? $emergencyVisitRate : null,
            'spot_rate' => $visitFrequency === 'spot' && $spotRate > 0 ? $spotRate : null,
            'document_consultation_rate' => $documentConsultationRate,
            'exclusive_registration_fee' => $exclusiveRegistrationFee > 0 ? $exclusiveRegistrationFee : null,
            // 遠隔相談・書面作成フラグを追加
            'use_remote_consultation' => $useRemoteConsultation,
            'use_document_creation' => $useDocumentCreation
        ];
        
        $result = $contractModel->updateWithWeeklySchedule($id, $data, $weeklyDays, $fileInfo, $removeFile);
        
        if ($result) {
            $frequencyLabel = get_visit_frequency_label($visitFrequency);
            $taxiLabel = $taxiAllowed ? 'タクシー利用可' : 'タクシー利用不可';
            $taxLabel = $contractModel->getTaxTypeLabel($taxType);
            $excludeHolidaysLabel = $excludeHolidays ? '祝日非訪問' : '祝日訪問可';
            
            // 反映日が変更された場合のメッセージ
            $isNewVersion = ($result !== true && $result != $id);
            if ($isNewVersion) {
                // 非訪問日を新しい契約にコピー
                $newContractId = $result;
                $this->copyNonVisitDaysToNewContract($id, $newContractId, $effectiveDate);
                
                $message = "契約の新しいバージョン(Ver." . ($contract['version_number'] + 1) . ")を作成しました。";
                $message .= "(反映日: " . format_date($effectiveDate) . "、訪問頻度: {$frequencyLabel}、{$taxiLabel}、税種別: {$taxLabel}";
            } else {
                $message = "契約を更新しました。(訪問頻度: {$frequencyLabel}、{$taxiLabel}、税種別: {$taxLabel}";
            }
            
            // 隔月訪問の場合は月次区分情報も追加
            if ($visitFrequency === 'bimonthly' && $bimonthlyType) {
                $bimonthlyLabel = $bimonthlyType === 'even' ? '偶数月訪問' : '奇数月訪問';
                $message .= "、{$bimonthlyLabel}";
            }
            
            // 週間契約の場合は祝日訪問情報も追加
            if ($visitFrequency === 'weekly') {
                $message .= "、{$excludeHolidaysLabel}";
            }
            
            $message .= ")";
            
            $this->setFlash('success', $message);
            redirect('contracts');
        } else {
            $this->setFlash('error', '契約の更新に失敗しました。');
            if ($fileInfo && file_exists($fileInfo['file_path'])) {
                unlink($fileInfo['file_path']);
            }
            redirect("contracts/{$id}/edit");
        }
    }

    /**
     * 非訪問日を新しい契約にコピー
     */
    private function copyNonVisitDaysToNewContract($oldContractId, $newContractId, $effectiveDate) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        try {
            // 反映日の年を取得
            $effectiveDateObj = new DateTime($effectiveDate);
            $effectiveYear = (int)$effectiveDateObj->format('Y');
            
            // 元の契約の有効な非訪問日を取得(反映日の年以降のもの)
            $sql = "SELECT 
                        non_visit_date,
                        description,
                        is_recurring,
                        recurring_month,
                        recurring_day,
                        year
                    FROM contract_non_visit_days
                    WHERE contract_id = :old_contract_id
                    AND is_active = 1
                    AND year >= :effective_year
                    ORDER BY non_visit_date";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'old_contract_id' => $oldContractId,
                'effective_year' => $effectiveYear
            ]);
            
            $nonVisitDays = $stmt->fetchAll();
            
            if (empty($nonVisitDays)) {
                return; // コピーする非訪問日がない場合は終了
            }
            
            $copiedCount = 0;
            $userId = Session::get('user_id');
            
            // 各非訪問日をコピー
            foreach ($nonVisitDays as $day) {
                // 新しい契約に同じ日付の非訪問日が既に存在しないかチェック
                $checkSql = "SELECT COUNT(*) FROM contract_non_visit_days
                            WHERE contract_id = :new_contract_id
                            AND non_visit_date = :non_visit_date";
                
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->execute([
                    'new_contract_id' => $newContractId,
                    'non_visit_date' => $day['non_visit_date']
                ]);
                
                if ($checkStmt->fetchColumn() > 0) {
                    continue; // 既に存在する場合はスキップ
                }
                
                // 非訪問日をコピー
                $insertSql = "INSERT INTO contract_non_visit_days 
                            (contract_id, non_visit_date, description, is_recurring, 
                            recurring_month, recurring_day, year, created_by, is_active)
                            VALUES 
                            (:contract_id, :non_visit_date, :description, :is_recurring,
                            :recurring_month, :recurring_day, :year, :created_by, 1)";
                
                $insertStmt = $db->prepare($insertSql);
                $inserted = $insertStmt->execute([
                    'contract_id' => $newContractId,
                    'non_visit_date' => $day['non_visit_date'],
                    'description' => $day['description'],
                    'is_recurring' => $day['is_recurring'],
                    'recurring_month' => $day['recurring_month'],
                    'recurring_day' => $day['recurring_day'],
                    'year' => $day['year'],
                    'created_by' => $userId
                ]);
                
                if ($inserted) {
                    $copiedCount++;
                }
            }
            
            // ログ記録
            if ($copiedCount > 0) {
                $this->logSecurityEvent('non_visit_days_copied', [
                    'old_contract_id' => $oldContractId,
                    'new_contract_id' => $newContractId,
                    'copied_count' => $copiedCount,
                    'effective_date' => $effectiveDate
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Non-visit days copy error: ' . $e->getMessage());
            // エラーが発生してもメイン処理は継続（非訪問日のコピーは補助的な機能）
        }
    }

    public function selectServiceType($contractId = null) {
        Session::requireUserType('doctor');
        
        // パラメータの取得（URL パラメータまたはGETパラメータ）
        if ($contractId === null) {
            $contractId = (int)($_GET['contract_id'] ?? 0);
        } else {
            $contractId = (int)$contractId;
        }
        
        $doctorId = Session::get('user_id');
        
        if (!$contractId) {
            $this->setFlash('error', '契約IDが指定されていません。');
            redirect('contracts');
        }
        
        // 契約の有効性チェック
        if (!$this->isValidContract($contractId, $doctorId)) {
            $this->setFlash('error', '選択された契約は無効です。');
            redirect('contracts');
        }
        
        // 既に進行中の役務がないかチェック（時間管理ありの役務のみ）
        $activeService = $this->getActiveService($doctorId);
        
        if ($activeService) {
            $this->setFlash('error', '既に進行中の役務があります。先に終了してください。');
            redirect('service_records/active');
        }
        
        // 契約詳細を取得（訪問頻度情報含む）
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $contractSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                    FROM contracts co
                    JOIN branches b ON co.branch_id = b.id
                    JOIN companies c ON co.company_id = c.id
                    WHERE co.id = :contract_id AND co.doctor_id = :doctor_id";
        
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['contract_id' => $contractId, 'doctor_id' => $doctorId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            $this->setFlash('error', '契約が見つかりません。');
            redirect('contracts');
        }
        
        // 訪問頻度情報を追加
        $contract['visit_frequency_info'] = get_visit_frequency_info($contract['visit_frequency'] ?? 'monthly');
        $contract['is_visit_month'] = is_visit_month($contract, date('Y'), date('n'));
        
        // 今月の役務統計を取得（訪問頻度対応版）
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        $statsSql = "SELECT 
                        SUM(CASE WHEN service_type = 'regular' THEN service_hours ELSE 0 END) as regular_hours,
                        SUM(CASE WHEN service_type = 'emergency' THEN service_hours ELSE 0 END) as emergency_hours,
                        SUM(CASE WHEN service_type = 'extension' THEN service_hours ELSE 0 END) as extension_hours,
                        COUNT(CASE WHEN service_type = 'document' THEN 1 END) as document_count,
                        COUNT(CASE WHEN service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                        COUNT(CASE WHEN service_type = 'other' THEN 1 END) as other_count
                    FROM service_records 
                    WHERE contract_id = :contract_id 
                    AND YEAR(service_date) = :year 
                    AND MONTH(service_date) = :month";
        
        $stmt = $db->prepare($statsSql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $currentYear,
            'month' => $currentMonth
        ]);
        
        $monthlyStats = $stmt->fetch() ?: [
            'regular_hours' => 0,
            'emergency_hours' => 0,
            'extension_hours' => 0,
            'document_count' => 0,
            'remote_consultation_count' => 0,
            'other_count' => 0
        ];
        
        // 訪問頻度に応じた月間使用量を計算
        $monthlyUsage = calculate_monthly_usage_by_frequency(
            $contract['visit_frequency'] ?? 'monthly',
            $contract['regular_visit_hours'],
            $currentYear,
            $currentMonth,
            $contract,
            $db
        );
        
        // 毎週契約の場合、今月の訪問回数×契約時間で上限を計算
        if (($contract['visit_frequency'] ?? 'monthly') === 'weekly') {
            // 今月の訪問回数を取得（祝日を考慮）
            require_once __DIR__ . '/../core/helpers.php';
            $weeklyDetails = get_weekly_contract_details_excluding_holidays($contractId, $currentYear, $currentMonth, $db);
            $monthlyVisitCount = $weeklyDetails['total_visits'];
            
            // 今月の上限時間 = 契約時間 × 今月の訪問回数（祝日訪問設定）
            $contract['monthly_limit'] = $contract['regular_visit_hours'] * $monthlyVisitCount;
            $contract['monthly_visit_count'] = $monthlyVisitCount; // 表示用
            $contract['weekly_details'] = $weeklyDetails; // 詳細情報も追加（祝日訪問情報含む）
        } else {
            $contract['monthly_limit'] = $monthlyUsage['monthly_limit'];
        }
        
        $contract['usage_message'] = get_frequency_time_message(
            $contract['visit_frequency'] ?? 'monthly',
            $contract['monthly_limit'],
            $monthlyStats['regular_hours'],
            $currentYear,
            $currentMonth,
            $contract
        );
        
        ob_start();
        include __DIR__ . '/../views/service_records/select_service_type.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    public function startService($contractId = null) {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('contracts');
            return;
        }
        
        // パラメータの取得（URL パラメータまたはPOSTパラメータ）
        if ($contractId === null) {
            $contractId = (int)($_POST['contract_id'] ?? 0);
        } else {
            $contractId = (int)$contractId;
        }
        
        $doctorId = Session::get('user_id');
        $serviceType = $_POST['service_type'] ?? '';
        $visitType = $_POST['visit_type'] ?? 'visit';
        
        if (!$contractId) {
            $this->setFlash('error', '契約IDが指定されていません。');
            redirect('contracts');
            return;
        }
        
        // バリデーション（役務種別）
        if (!in_array($serviceType, ['regular', 'emergency', 'extension', 'document', 'remote_consultation', 'spot', 'other'])) {
            $this->setFlash('error', '役務種別を正しく選択してください。');
            redirect("contracts/{$contractId}/select-service-type");
            return;
        }
        
        // 訪問種別のバリデーション
        // 時間管理ありの役務（regular, emergency, extension, spot）は訪問種別が必要
        if (in_array($serviceType, ['regular', 'emergency', 'extension', 'spot'])) {
            if (!in_array($visitType, ['visit', 'online'])) {
                $this->setFlash('error', '訪問種別を正しく選択してください。');
                redirect("contracts/{$contractId}/select-service-type");
                return;
            }
        } else {
            $visitType = null;
        }
        
        // 契約の有効性チェック
        if (!$this->isValidContract($contractId, $doctorId)) {
            $this->setFlash('error', '選択された契約は無効です。');
            redirect('contracts');
            return;
        }
        
        // 書面作成・遠隔相談の場合は直接作成画面へ（時間記録なし）
        if (in_array($serviceType, ['document', 'remote_consultation', 'other'])) {
            $this->setFlash('info', get_service_type_label($serviceType) . 'の記録を作成してください。');
            redirect("service_records/create?contract_id={$contractId}&service_type={$serviceType}");
            return;
        }
        
        // 既に進行中の役務がないかチェック
        $activeService = $this->getActiveService($doctorId);
        
        if ($activeService) {
            $this->setFlash('error', '既に進行中の役務があります。先に終了してください。');
            redirect('service_records/active');
            return;
        }
        
        // 役務記録を作成
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $currentTime = new DateTime();
        $currentTime->setTime($currentTime->format('H'), $currentTime->format('i'), 0); // 秒を00に設定
        $startTime = $currentTime->format('H:i:s');
       
        $sql = "INSERT INTO service_records 
                (contract_id, doctor_id, service_date, start_time, end_time, service_hours, service_type, visit_type, status, description, created_at, updated_at) 
                VALUES 
                (:contract_id, :doctor_id, :service_date, :start_time, :end_time, :service_hours, :service_type, :visit_type, :status, :description, NOW(), NOW())";
        
        $data = [
            'contract_id' => $contractId,
            'doctor_id' => $doctorId,
            'service_date' => date('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $startTime,
            'service_hours' => 0,
            'service_type' => $serviceType,
            'visit_type' => $visitType,
            'status' => 'pending',
            'description' => ''
        ];
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($data);
        
        if ($result) {
            $serviceTypeLabel = get_service_type_label($serviceType);
            $visitTypeLabel = get_visit_type_label($visitType);
            $this->setFlash('success', "{$serviceTypeLabel}({$visitTypeLabel})を開始しました。");
            redirect("service_records/active");
        } else {
            $this->setFlash('error', '役務開始の記録に失敗しました。');
            redirect("contracts/{$contractId}/select-service-type");
        }
    }
    
    public function downloadFile($contractId) {
        Session::requireUserType('admin');
        
        $contractModel = new Contract();
        $contract = $contractModel->findById($contractId);
        
        if (!$contract || empty($contract['contract_file_path'])) {
            $this->setFlash('error', 'ファイルが見つかりません。');
            redirect('contracts');
        }
        
        $filePath = $contract['contract_file_path'];
        $fileName = $contract['contract_file_name'];
        
        if (!file_exists($filePath)) {
            $this->setFlash('error', 'ファイルが存在しません。');
            redirect('contracts');
        }
        
        // ファイルダウンロード
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($filePath);
        exit;
    }
    
    public function fileManagement() {
        Session::requireUserType('admin');
        
        $contractModel = new Contract();
        
        $contractsWithFiles = $contractModel->findContractsWithFiles();
        $totalFileSize = $contractModel->getTotalFileSize();
        $orphanedFiles = $contractModel->findOrphanedFiles();
        
        $stats = [
            'total_contracts' => count($contractsWithFiles),
            'total_file_size' => $totalFileSize,
            'orphaned_count' => count($orphanedFiles)
        ];
        
        ob_start();
        include __DIR__ . '/../views/contracts/file_management.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function cleanupFiles() {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect('contracts/file-management');
        }
        
        $contractModel = new Contract();
        
        try {
            $cleanedCount = $contractModel->cleanupOrphanedFileRecords();
            $this->setFlash('success', "{$cleanedCount}件の孤立ファイルレコードをクリーンアップしました。");
        } catch (Exception $e) {
            $this->setFlash('error', 'クリーンアップに失敗しました: ' . $e->getMessage());
        }
        
        redirect('contracts/file-management');
    }
    
    private function isValidContract($contractId, $doctorId) {
        $contractModel = new Contract();
        return $contractModel->isValidContract($contractId, $doctorId);
    }
    
    private function getActiveService($doctorId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT * FROM service_records 
                WHERE doctor_id = :doctor_id 
                AND service_hours = 0 
                AND start_time = end_time
                AND service_date = CURDATE()
                AND service_type IN ('regular', 'emergency', 'extension')
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        return $stmt->fetch();
    }
    
    private function validateUploadedFile($file) {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        
        // ファイルサイズチェック
        if ($file['size'] > $maxSize) {
            return [
                'success' => false, 
                'error' => 'ファイルサイズが大きすぎます。10MB以下のファイルをアップロードしてください。'
            ];
        }
        
        // MIMEタイプチェック
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return [
                'success' => false, 
                'error' => 'サポートされていないファイル形式です。PDF、Word文書をアップロードしてください。'
            ];
        }
        
        // 拡張子チェック
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return [
                'success' => false, 
                'error' => 'サポートされていないファイル拡張子です。'
            ];
        }
        
        // ファイル内容の検証
        if (!$this->validateFileContent($file['tmp_name'], $extension)) {
            return [
                'success' => false, 
                'error' => 'ファイルの内容が不正です。'
            ];
        }
        
        return ['success' => true];
    }
    
    private function validateFileContent($filePath, $extension) {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 8);
        fclose($handle);
        
        switch ($extension) {
            case 'pdf':
                return strpos($header, '%PDF') === 0;
                
            case 'doc':
                $magic = bin2hex(substr($header, 0, 8));
                return strpos($magic, 'd0cf11e0a1b11ae1') === 0;
                
            case 'docx':
                $magic = bin2hex(substr($header, 0, 4));
                return in_array($magic, ['504b0304', '504b0506', '504b0708']);
                
            default:
                return false;
        }
    }
    
    private function saveUploadedFile($file, $companyId, $branchId) {
        $uploadDir = __DIR__ . '/../../public/uploads/contracts/';
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return null;
            }
            
            $htaccessContent = <<<EOD
# 実行可能ファイルの実行を禁止
<Files *>
    ForceType application/octet-stream
    Header set Content-Disposition attachment
</Files>

# PHPファイルの実行を禁止
<FilesMatch "\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# 直接アクセスを制限
Order Deny,Allow
Deny from all
EOD;
            
            file_put_contents($uploadDir . '.htaccess', $htaccessContent);
            file_put_contents($uploadDir . '.gitkeep', '');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = 'contract_' . $companyId . '_' . $branchId . '_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'file_path' => $filePath,
                'file_name' => $file['name'],
                'file_size' => $file['size']
            ];
        }
        
        return null;
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'ファイルサイズが大きすぎます。';
            case UPLOAD_ERR_PARTIAL:
                return 'ファイルのアップロードが中断されました。';
            case UPLOAD_ERR_NO_FILE:
                return 'ファイルが選択されていません。';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'アップロード用の一時ディレクトリがありません。';
            case UPLOAD_ERR_CANT_WRITE:
                return 'ファイルの書き込みに失敗しました。';
            case UPLOAD_ERR_EXTENSION:
                return 'アップロードがPHPの拡張機能によって停止されました。';
            default:
                return 'ファイルのアップロードに失敗しました。';
        }
    }
    
    public function getBranchesByCompany($companyId) {
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        require_once __DIR__ . '/../models/Branch.php';
        $branchModel = new Branch();
        
        $branches = $branchModel->findByCompany((int)$companyId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $branches]);
    }

    /**
     * 契約終了処理（Ajax対応）
     */
    public function terminate($id) {
        // Ajax リクエストかどうかをチェック
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Content-Type を JSON に設定（Ajaxの場合）
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        try {
            // ログイン確認（管理者権限）
            Session::requireUserType('admin');
            
            // POSTメソッドの確認
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $error = '無効なリクエストメソッドです';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            // CSRFトークンの検証
            if (!$this->validateCsrf()) {
                $error = 'セキュリティトークンが無効です。ページを再読み込みしてください。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            $contractModel = new Contract();
            $contract = $contractModel->findById($id);
            
            if (!$contract) {
                $error = '指定された契約が見つかりません';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            // 既に終了済みかチェック
            if ($contract['contract_status'] === 'terminated') {
                $error = 'この契約は既に終了済みです。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            // 進行中の役務記録があるかチェック
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $activeSql = "SELECT COUNT(*) as count FROM service_records 
                        WHERE contract_id = :contract_id 
                        AND service_hours = 0 
                        AND start_time = end_time 
                        AND service_date = CURDATE()";
            
            $stmt = $db->prepare($activeSql);
            $stmt->execute(['contract_id' => $id]);
            $activeCount = $stmt->fetchColumn();
            
            if ($activeCount > 0) {
                $error = 'この契約には進行中の役務記録があるため終了できません。先に役務を終了してください。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            // 契約終了処理
            $updateData = [
                'contract_status' => 'terminated',
                'terminated_at' => date('Y-m-d H:i:s'),
                'terminated_by' => Session::get('user_id'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($contractModel->update($id, $updateData)) {
                // セキュリティログに記録
                $this->logSecurityEvent('contract_terminated', [
                    'contract_id' => $id,
                    'company_name' => $contract['company_name'] ?? '',
                    'doctor_name' => $contract['doctor_name'] ?? '',
                    'terminated_by' => Session::get('user_id')
                ]);
                
                $successMessage = '契約を正常に終了しました。';
                
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'message' => $successMessage,
                        'contract_id' => $id
                    ]);
                    return;
                } else {
                    $this->setFlash('success', $successMessage);
                    redirect('contracts');
                    return;
                }
            } else {
                $error = '契約終了処理に失敗しました。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
        } catch (Exception $e) {
            error_log('Contract terminate error: ' . $e->getMessage());
            
            $errorMessage = 'システムエラーが発生しました: ' . $e->getMessage();
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                return;
            } else {
                $this->setFlash('error', $errorMessage);
                redirect('contracts');
                return;
            }
        }
    }

    /**
     * 契約再開処理（Ajax対応）
     */
    public function activate($id) {
        // Ajax リクエストかどうかをチェック
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Content-Type を JSON に設定（Ajaxの場合）
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        try {
            // ログイン確認（管理者権限）
            Session::requireUserType('admin');
            
            // POSTメソッドの確認
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $error = '無効なリクエストメソッドです';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            // CSRFトークンの検証
            if (!$this->validateCsrf()) {
                $error = 'セキュリティトークンが無効です。ページを再読み込みしてください。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            $contractModel = new Contract();
            $contract = $contractModel->findById($id);
            
            if (!$contract) {
                $error = '指定された契約が見つかりません';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            // 既に有効かチェック
            if ($contract['contract_status'] === 'active') {
                $error = 'この契約は既に有効です。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
            // 契約再開処理
            $updateData = [
                'contract_status' => 'active',
                'terminated_at' => null,
                'terminated_by' => null,
                'reactivated_at' => date('Y-m-d H:i:s'),
                'reactivated_by' => Session::get('user_id'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($contractModel->update($id, $updateData)) {
                // セキュリティログに記録
                $this->logSecurityEvent('contract_reactivated', [
                    'contract_id' => $id,
                    'company_name' => $contract['company_name'] ?? '',
                    'doctor_name' => $contract['doctor_name'] ?? '',
                    'reactivated_by' => Session::get('user_id')
                ]);
                
                $successMessage = '契約を正常に再開しました。';
                
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'message' => $successMessage,
                        'contract_id' => $id
                    ]);
                    return;
                } else {
                    $this->setFlash('success', $successMessage);
                    redirect('contracts');
                    return;
                }
            } else {
                $error = '契約再開処理に失敗しました。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    redirect('contracts');
                    return;
                }
            }
            
        } catch (Exception $e) {
            error_log('Contract activate error: ' . $e->getMessage());
            
            $errorMessage = 'システムエラーが発生しました: ' . $e->getMessage();
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                return;
            } else {
                $this->setFlash('error', $errorMessage);
                redirect('contracts');
                return;
            }
        }
    }

    /**
     * 非訪問日一覧取得API（産業医用）
     */
    public function getNonVisitDaysApi($contractId) {
        // Ajax リクエストかどうかをチェック
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Content-Type を JSON に設定
        header('Content-Type: application/json; charset=utf-8');

        try {
            // ログイン確認（産業医または企業ユーザー）
            if (!Session::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'ログインが必要です']);
                return;
            }
            
            $userType = Session::get('user_type');
            $userId = Session::get('user_id');
            
            // GETメソッドの確認
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                echo json_encode(['success' => false, 'message' => '無効なリクエストメソッドです']);
                return;
            }
            
            $contractId = (int)$contractId;
            
            if (!$contractId) {
                echo json_encode(['success' => false, 'message' => '契約IDが指定されていません']);
                return;
            }
            
            // 呼び出し元を判別するスコープパラメータ
            $scope = $_GET['scope'] ?? 'all'; // 'current_month' または 'all'（デフォルト）
            
            if (!in_array($scope, ['current_month', 'all'])) {
                echo json_encode(['success' => false, 'message' => '無効なスコープパラメータです']);
                return;
            }
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // ユーザータイプによって契約の有効性チェックを変更
            if ($userType === 'doctor') {
                // 産業医の場合：自分の契約かどうかチェック
                $contractSql = "SELECT id, visit_frequency FROM contracts 
                                WHERE id = :contract_id AND doctor_id = :user_id";
            } else {
                // 企業ユーザーの場合：自分の企業の契約かどうかチェック
                $contractSql = "SELECT c.id, c.visit_frequency 
                                FROM contracts c
                                INNER JOIN branches b ON c.branch_id = b.id
                                INNER JOIN companies comp ON b.company_id = comp.id
                                WHERE c.id = :contract_id AND comp.id = (
                                    SELECT company_id FROM users WHERE id = :user_id
                                )";
            }
            
            $stmt = $db->prepare($contractSql);
            $stmt->execute(['contract_id' => $contractId, 'user_id' => $userId]);
            $contract = $stmt->fetch();
            
            if (!$contract) {
                echo json_encode([
                    'success' => false, 
                    'message' => '指定された契約が見つかりません'
                ]);
                return;
            }
            
            // 毎週契約のみ非訪問日を取得
            if ($contract['visit_frequency'] !== 'weekly') {
                echo json_encode([
                    'success' => false, 
                    'message' => 'この契約は毎週契約ではありません'
                ]);
                return;
            }
            
            // 現在の年月を取得
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('n');
            
            // 非訪問日データを取得（今年以前のデータのみ）
            $nonVisitSql = "SELECT 
                                id,
                                non_visit_date,
                                description,
                                is_recurring,
                                recurring_month,
                                recurring_day,
                                year,
                                is_active,
                                created_at,
                                updated_at
                            FROM contract_non_visit_days 
                            WHERE contract_id = :contract_id 
                            AND year <= :current_year
                            AND is_active = 1
                            ORDER BY non_visit_date";
            
            $stmt = $db->prepare($nonVisitSql);
            $stmt->execute([
                'contract_id' => $contractId, 
                'current_year' => $currentYear
            ]);
            $nonVisitDays = $stmt->fetchAll();
            
            if (empty($nonVisitDays)) {
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'message' => '非訪問日の設定がありません'
                ]);
                return;
            }
            
            // データを整形
            $formattedData = [];
            $processedRecurringDates = []; // 重複防止用
            
            foreach ($nonVisitDays as $day) {
                $originalDate = new DateTime($day['non_visit_date']);
                $originalYear = (int)$originalDate->format('Y');
                
                // 今年のデータはそのまま表示
                if ($originalYear === $currentYear) {
                    $dateData = [
                        'id' => (int)$day['id'],
                        'date' => $day['non_visit_date'],
                        'reason' => $day['description'] ?: '',
                        'year' => $currentYear,
                        'month' => (int)$originalDate->format('n'),
                        'day' => (int)$originalDate->format('j'),
                        'is_active' => (bool)$day['is_active'],
                        'is_recurring' => (bool)$day['is_recurring'],
                        'recurring_month' => $day['recurring_month'] ? (int)$day['recurring_month'] : null,
                        'recurring_day' => $day['recurring_day'] ? (int)$day['recurring_day'] : null,
                        'created_at' => $day['created_at'],
                        'updated_at' => $day['updated_at'],
                        'is_converted_from_past' => false
                    ];
                    
                    // スコープによるフィルタリング
                    if ($scope === 'current_month') {
                        // 当月のみ
                        if ((int)$originalDate->format('n') === $currentMonth) {
                            $formattedData[] = $dateData;
                        }
                    } else {
                        // 全て
                        $formattedData[] = $dateData;
                    }
                }
                // 過去のデータで is_recurring が 1 の場合、今年の日付に読み替え
                elseif ($originalYear < $currentYear && $day['is_recurring']) {
                    $month = (int)$originalDate->format('n');
                    $dayOfMonth = (int)$originalDate->format('j');
                    
                    // スコープによるフィルタリング（読み替え前にチェック）
                    if ($scope === 'current_month' && $month !== $currentMonth) {
                        continue;
                    }
                    
                    // 重複チェック用のキー
                    $recurringKey = $currentYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dayOfMonth, 2, '0', STR_PAD_LEFT);
                    
                    // 既に同じ日付が処理されている場合はスキップ
                    if (in_array($recurringKey, $processedRecurringDates)) {
                        continue;
                    }
                    
                    // 今年に該当する日付が存在するかチェック
                    try {
                        $thisYearDate = new DateTime($currentYear . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dayOfMonth, 2, '0', STR_PAD_LEFT));
                        
                        // 今年の同じ日付のデータが既に存在しないかチェック
                        $duplicateCheck = false;
                        foreach ($formattedData as $existingData) {
                            if ($existingData['date'] === $thisYearDate->format('Y-m-d')) {
                                $duplicateCheck = true;
                                break;
                            }
                        }
                        
                        // 重複がない場合のみ追加
                        if (!$duplicateCheck) {
                            $formattedData[] = [
                                'id' => (int)$day['id'],
                                'date' => $thisYearDate->format('Y-m-d'),
                                'reason' => $day['description'] ?: '',
                                'year' => $currentYear,
                                'month' => $month,
                                'day' => $dayOfMonth,
                                'is_active' => (bool)$day['is_active'],
                                'is_recurring' => (bool)$day['is_recurring'],
                                'recurring_month' => $day['recurring_month'] ? (int)$day['recurring_month'] : null,
                                'recurring_day' => $day['recurring_day'] ? (int)$day['recurring_day'] : null,
                                'created_at' => $day['created_at'],
                                'updated_at' => $day['updated_at'],
                                'is_converted_from_past' => true,
                                'original_year' => $originalYear
                            ];
                            
                            $processedRecurringDates[] = $recurringKey;
                        }
                    } catch (Exception $dateException) {
                        // 無効な日付（例：2月30日など）の場合はスキップ
                        error_log('Invalid recurring date: ' . $currentYear . '-' . $month . '-' . $dayOfMonth . ' - ' . $dateException->getMessage());
                        continue;
                    }
                }
                // 過去のデータで is_recurring が 0 の場合は表示しない
                // 来年以降のデータも表示しない（既にSQLで除外済み）
            }
            
            // 日付順にソート
            usort($formattedData, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
            
            echo json_encode([
                'success' => true,
                'data' => $formattedData,
                'total_count' => count($formattedData),
                'current_year' => $currentYear,
                'current_month' => $currentMonth,
                'scope' => $scope
            ]);
            
        } catch (Exception $e) {
            error_log('Get non-visit days API error: ' . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'システムエラーが発生しました: ' . $e->getMessage()
            ]);
        }
    }

}
?>