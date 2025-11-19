<?php
// app/controllers/ServiceRecordController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ServiceRecord.php';
require_once __DIR__ . '/../models/Contract.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/MonthlySummary.php';

class ServiceRecordController extends BaseController {

    /**
     * 企業ユーザーがアクセス可能な拠点IDの配列を取得
     */
    private function getAccessibleBranchIds($companyId, $userId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // user_branch_mappingsテーブルから企業ユーザーがアクセス可能な拠点を取得
        $sql = "SELECT DISTINCT ubm.branch_id 
                FROM user_branch_mappings ubm
                JOIN branches b ON ubm.branch_id = b.id
                WHERE ubm.user_id = :user_id 
                AND ubm.is_active = 1 
                AND b.company_id = :company_id 
                AND b.is_active = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'company_id' => $companyId
        ]);
        
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $result ?: [];
    }

    /**
     * 拠点制限条件をSQLに追加
     */
    private function addBranchAccessCondition($sql, &$params, $companyId, $userId, $contractTableAlias = 'co') {
        $accessibleBranchIds = $this->getAccessibleBranchIds($companyId, $userId);
        
        if (empty($accessibleBranchIds)) {
            // アクセス可能な拠点がない場合は、結果を空にする
            $sql .= " AND 1=0";
            return $sql;
        }
        
        // 名前付きパラメータでプレースホルダーを作成
        $placeholders = [];
        foreach ($accessibleBranchIds as $index => $branchId) {
            $paramName = "branch_id_{$index}";
            $placeholders[] = ":{$paramName}";
            $params[$paramName] = $branchId;
        }
        
        // アクセス可能な拠点のみに制限
        $placeholderString = implode(',', $placeholders);
        $sql .= " AND {$contractTableAlias}.branch_id IN ({$placeholderString})";
        
        return $sql;
    }

    /**
     * ページネーション用のパラメータを取得・検証
     */
    private function getPaginationParams() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        // 1ページあたりの件数の選択肢を制限
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 20;
        }
        
        $offset = ($page - 1) * $perPage;
        
        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset
        ];
    }

    /**
     * ページネーション情報を計算
     */
    private function calculatePagination($totalRecords, $currentPage, $perPage) {
        $totalPages = max(1, ceil($totalRecords / $perPage));
        $currentPage = min($currentPage, $totalPages);
        
        return [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_page' => max(1, $currentPage - 1),
            'next_page' => min($totalPages, $currentPage + 1),
            'start_record' => ($currentPage - 1) * $perPage + 1,
            'end_record' => min($currentPage * $perPage, $totalRecords)
        ];
    }
    
    public function index() {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        $serviceModel = new ServiceRecord();
        
        if ($userType === 'doctor') {
            $doctorId = Session::get('user_id');
            
            // パラメータを統一して取得
            $yearMonth = $_GET['year_month'] ?? date('Y-m'); 
            $statusFilter = $_GET['status'] ?? '';
            $serviceTypeFilter = $_GET['service_type'] ?? '';
            $companyFilter = $_GET['company'] ?? '';
            $contractFilter = $_GET['contract_id'] ?? '';
            $searchFilter = $_GET['search'] ?? '';
            
            // 並び替えパラメータを取得
            $sortColumn = $_GET['sort'] ?? 'service_date';
            $sortOrder = $_GET['order'] ?? 'desc';
            
            // 許可される並び替え列
            $allowedSorts = ['service_date', 'company_name', 'service_hours', 'service_type', 'status'];
            if (!in_array($sortColumn, $allowedSorts)) {
                $sortColumn = 'service_date';
            }
            
            // 並び替え順序の検証
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            // ページネーションパラメータを取得
            $pagination = $this->getPaginationParams();
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 総件数取得用のSQL(既存のまま)
            $countSql = "SELECT COUNT(*) as total
                        FROM service_records sr
                        JOIN contracts co ON sr.contract_id = co.id
                        JOIN branches b ON co.branch_id = b.id
                        JOIN companies c ON co.company_id = c.id
                        WHERE sr.doctor_id = :doctor_id";
            
            $params = ['doctor_id' => $doctorId];
            
            // フィルター条件(既存のまま)
            if (!empty($yearMonth)) {
                $yearMonthParts = explode('-', $yearMonth);
                if (count($yearMonthParts) === 2) {
                    $year = (int)$yearMonthParts[0];
                    $month = (int)$yearMonthParts[1];
                    
                    $countSql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
                    $params['year'] = $year;
                    $params['month'] = $month;
                }
            }
            
            // その他のフィルター(既存のまま)
            if (!empty($contractFilter)) {
                $countSql .= " AND sr.contract_id = :contract_id";
                $params['contract_id'] = $contractFilter;
            }
            
            if (!empty($statusFilter)) {
                $countSql .= " AND sr.status = :status";
                $params['status'] = $statusFilter;
            }
            
            if (!empty($serviceTypeFilter)) {
                $countSql .= " AND sr.service_type = :service_type";
                $params['service_type'] = $serviceTypeFilter;
            }
            
            if (!empty($companyFilter)) {
                $countSql .= " AND c.name LIKE :company_name";
                $params['company_name'] = "%{$companyFilter}%";
            }
            
            if (!empty($searchFilter)) {
                $countSql .= " AND (c.name LIKE :search OR b.name LIKE :search2)";
                $params['search'] = "%{$searchFilter}%";
                $params['search2'] = "%{$searchFilter}%";
            }
            
            // 総件数を取得
            $stmt = $db->prepare($countSql);
            $stmt->execute($params);
            $totalRecords = $stmt->fetch()['total'];
            
            // ページネーション情報を計算
            $paginationInfo = $this->calculatePagination($totalRecords, $pagination['page'], $pagination['per_page']);
            
            // データ取得用のSQL(並び替え対応版)
            $recordsSql = "SELECT sr.*, c.name as company_name, b.name as branch_name, co.regular_visit_hours
                        FROM service_records sr
                        JOIN contracts co ON sr.contract_id = co.id
                        JOIN branches b ON co.branch_id = b.id
                        JOIN companies c ON co.company_id = c.id
                        WHERE sr.doctor_id = :doctor_id";
            
            // 同じフィルター条件を適用
            if (!empty($yearMonth)) {
                $recordsSql .= " AND YEAR(sr.service_date) = :year AND MONTH(sr.service_date) = :month";
            }
            
            if (!empty($contractFilter)) {
                $recordsSql .= " AND sr.contract_id = :contract_id";
            }
            
            if (!empty($statusFilter)) {
                $recordsSql .= " AND sr.status = :status";
            }
            
            if (!empty($serviceTypeFilter)) {
                $recordsSql .= " AND sr.service_type = :service_type";
            }
            
            if (!empty($companyFilter)) {
                $recordsSql .= " AND c.name LIKE :company_name";
            }
            
            if (!empty($searchFilter)) {
                $recordsSql .= " AND (c.name LIKE :search OR b.name LIKE :search2)";
            }
            
            // 並び替え条件を追加
            $recordsSql .= " ORDER BY ";
            switch ($sortColumn) {
                case 'company_name':
                    $recordsSql .= "c.name {$sortOrder}, b.name {$sortOrder}, sr.service_date DESC";
                    break;
                case 'service_hours':
                    $recordsSql .= "sr.service_hours {$sortOrder}, sr.service_date DESC";
                    break;
                case 'service_type':
                    $recordsSql .= "sr.service_type {$sortOrder}, sr.service_date DESC";
                    break;
                case 'status':
                    $recordsSql .= "sr.status {$sortOrder}, sr.service_date DESC";
                    break;
                case 'service_date':
                default:
                    $recordsSql .= "sr.service_date {$sortOrder}, sr.start_time DESC, c.name ASC, b.name ASC";
                    break;
            }
            
            $recordsSql .= " LIMIT :limit OFFSET :offset";
            
            // LIMITとOFFSETのパラメータを追加
            $params['limit'] = $pagination['per_page'];
            $params['offset'] = $pagination['offset'];
            
            $stmt = $db->prepare($recordsSql);
            
            // 整数パラメータをバインド
            $stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
            
            // その他のパラメータをバインド
            foreach ($params as $key => $value) {
                if ($key !== 'limit' && $key !== 'offset') {
                    $stmt->bindValue(":$key", $value);
                }
            }
            
            $stmt->execute();
            $records = $stmt->fetchAll();
            
            // 既存の処理を継続...
            $currentContract = null;
            if (!empty($contractFilter)) {
                $contractInfoSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                                FROM contracts co
                                JOIN branches b ON co.branch_id = b.id
                                JOIN companies c ON co.company_id = c.id
                                WHERE co.id = :contract_id AND co.doctor_id = :doctor_id";
                
                $stmt = $db->prepare($contractInfoSql);
                $stmt->execute(['contract_id' => $contractFilter, 'doctor_id' => $doctorId]);
                $currentContract = $stmt->fetch();
            }
            
            // 選択した年月に反映日が該当する契約を取得(フィルター用)
            $contractsForFilterSql = "SELECT DISTINCT co.id as contract_id, 
                                             c.name as company_name, 
                                             b.name as branch_name
                                      FROM contracts co
                                      JOIN branches b ON co.branch_id = b.id
                                      JOIN companies c ON co.company_id = c.id
                                      WHERE co.doctor_id = :doctor_id
                                      AND co.contract_status = 'active'";
            
            // 年月が指定されている場合、その月に反映日が該当する契約のみ取得
            $contractParams = ['doctor_id' => $doctorId];
            if (!empty($yearMonth)) {
                $yearMonthParts = explode('-', $yearMonth);
                if (count($yearMonthParts) === 2) {
                    $filterYear = (int)$yearMonthParts[0];
                    $filterMonth = (int)$yearMonthParts[1];
                    
                    // 該当月の初日と最終日を計算
                    $monthStart = sprintf('%04d-%02d-01', $filterYear, $filterMonth);
                    $monthEnd = date('Y-m-t', strtotime($monthStart)); // 月末日
                    
                    $contractsForFilterSql .= " AND co.effective_date <= :month_end
                                               AND (co.effective_end_date IS NULL OR co.effective_end_date >= :month_start)";
                    $contractParams['month_start'] = $monthStart;
                    $contractParams['month_end'] = $monthEnd;
                }
            }
            
            $contractsForFilterSql .= " ORDER BY c.name ASC, b.name ASC";
            
            $stmt = $db->prepare($contractsForFilterSql);
            $stmt->execute($contractParams);
            $contractsForFilter = $stmt->fetchAll();
            
            // 利用可能な役務種別を判定
            $availableServiceTypes = []; // 空配列から開始
            
            // 産業医の契約で遠隔相談、書面作成、スポット契約、定期契約が有効な契約があるかチェック
            $checkServiceTypesSql = "SELECT 
                                        MAX(use_remote_consultation) as has_remote_consultation,
                                        MAX(use_document_creation) as has_document_creation,
                                        MAX(CASE WHEN visit_frequency = 'spot' THEN 1 ELSE 0 END) as has_spot,
                                        MAX(CASE WHEN visit_frequency IN ('monthly', 'bimonthly', 'weekly') THEN 1 ELSE 0 END) as has_regular
                                     FROM contracts
                                     WHERE doctor_id = :doctor_id 
                                     AND contract_status = 'active'";
            
            $stmt = $db->prepare($checkServiceTypesSql);
            $stmt->execute(['doctor_id' => $doctorId]);
            $serviceTypeFlags = $stmt->fetch();
            
            // 定期契約が1件以上ある場合、定期訪問・臨時訪問・延長を追加
            if ($serviceTypeFlags && $serviceTypeFlags['has_regular'] == 1) {
                $availableServiceTypes[] = 'regular';
                $availableServiceTypes[] = 'emergency';
                $availableServiceTypes[] = 'extension';
            }
            
            // 遠隔相談が有効な契約が1件以上ある場合
            if ($serviceTypeFlags && $serviceTypeFlags['has_remote_consultation'] == 1) {
                $availableServiceTypes[] = 'remote_consultation';
            }
            
            // 書面作成が有効な契約が1件以上ある場合
            if ($serviceTypeFlags && $serviceTypeFlags['has_document_creation'] == 1) {
                $availableServiceTypes[] = 'document';
            }
            
            // スポット契約が1件以上ある場合
            if ($serviceTypeFlags && $serviceTypeFlags['has_spot'] == 1) {
                $availableServiceTypes[] = 'spot';
            }
            
            // ビューに渡すデータ
            $data = [
                'records' => $records,
                'contractsForFilter' => $contractsForFilter,
                'currentContract' => $currentContract,
                'year' => $year ?? null,
                'month' => $month ?? null,
                'yearMonth' => $yearMonth,
                'displayYearMonth' => $yearMonth ?: date('Y-m'),
                'statusFilter' => $statusFilter,
                'serviceTypeFilter' => $serviceTypeFilter,
                'companyFilter' => $companyFilter,
                'contractFilter' => $contractFilter,
                'searchFilter' => $searchFilter,
                'isFiltered' => !empty($yearMonth),
                'pagination' => $paginationInfo,
                'sortColumn' => $sortColumn,
                'sortOrder' => $sortOrder,
                'availableServiceTypes' => $availableServiceTypes // 追加
            ];
            
            extract($data);
            
            ob_start();
            include __DIR__ . '/../views/service_records/index.php';
            $content = ob_get_clean();
            
            include __DIR__ . '/../views/layouts/base.php';
            
        } elseif ($userType === 'company') {
            // 企業ユーザーの場合(ページネーション対応 + 並び替え対応 + 契約フィルター追加)
            $companyId = Session::get('company_id');
            $userId = Session::get('user_id');
            $month = $_GET['month'] ?? '';
            $year = $_GET['year'] ?? date('Y');
            $status = $_GET['status'] ?? '';
            $doctorFilter = $_GET['doctor'] ?? '';
            $serviceTypeFilter = $_GET['service_type'] ?? '';
            $contractFilter = $_GET['contract'] ?? '';  // 契約フィルター追加
            
            // 並び替えパラメータを取得
            $sortColumn = $_GET['sort'] ?? 'service_date';
            $sortOrder = $_GET['order'] ?? 'desc';
            
            // 許可される並び替え列
            $allowedSorts = ['service_date', 'doctor_name', 'branch_name', 'service_type', 'service_hours', 'status', 'visit_type'];
            if (!in_array($sortColumn, $allowedSorts)) {
                $sortColumn = 'service_date';
            }
            
            // 並び替え順序の検証
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            // ページネーションパラメータを取得
            $pagination = $this->getPaginationParams();
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // アクセス可能な拠点IDを事前に取得（整数配列として安全に処理）
            $accessibleBranchIds = $this->getAccessibleBranchIds($companyId, $userId);
            
            // 拠点アクセス権がない場合は空の結果を返す
            if (empty($accessibleBranchIds)) {
                $data = [
                    'records' => [],
                    'stats' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total_hours' => 0],
                    'doctors' => [],
                    'contracts' => [],
                    'month' => $month,
                    'year' => $year,
                    'status' => $status,
                    'doctorFilter' => $doctorFilter,
                    'serviceTypeFilter' => $serviceTypeFilter,
                    'contractFilter' => $contractFilter,
                    'pagination' => $this->calculatePagination(0, 1, $pagination['per_page']),
                    'sortColumn' => $sortColumn,
                    'sortOrder' => $sortOrder
                ];
                
                extract($data);
                ob_start();
                include __DIR__ . '/../views/service_records/company_index.php';
                $content = ob_get_clean();
                include __DIR__ . '/../views/layouts/base.php';
                return;
            }
            
            // 拠点IDを安全に文字列化（SQLインジェクション対策）
            $safeBranchIds = implode(',', array_map('intval', $accessibleBranchIds));
            
            // 契約フィルターの全バージョンを事前に取得
            $contractVersions = [];
            if (!empty($contractFilter)) {
                $versionSql = "SELECT id FROM contracts 
                              WHERE (id = ? 
                                    OR parent_contract_id = ?
                                    OR id IN (SELECT parent_contract_id FROM contracts 
                                              WHERE id = ? 
                                              AND parent_contract_id IS NOT NULL))
                              AND company_id = ?";
                
                $versionStmt = $db->prepare($versionSql);
                $versionStmt->execute([
                    $contractFilter,
                    $contractFilter,
                    $contractFilter,
                    $companyId
                ]);
                $contractVersions = $versionStmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            // ===== 総件数取得 =====
            $countSql = "SELECT COUNT(*) as total
                        FROM service_records sr
                        JOIN contracts co ON sr.contract_id = co.id
                        JOIN branches b ON co.branch_id = b.id
                        JOIN users u ON sr.doctor_id = u.id
                        WHERE co.company_id = ?
                        AND co.branch_id IN ({$safeBranchIds})";
            
            $countParams = [$companyId];
            
            if (!empty($year)) {
                $countSql .= " AND YEAR(sr.service_date) = ?";
                $countParams[] = $year;
            }
            
            if (!empty($month)) {
                $countSql .= " AND MONTH(sr.service_date) = ?";
                $countParams[] = $month;
            }
            
            if (!empty($status)) {
                $countSql .= " AND sr.status = ?";
                $countParams[] = $status;
            }
            
            if (!empty($serviceTypeFilter)) {
                $countSql .= " AND sr.service_type = ?";
                $countParams[] = $serviceTypeFilter;
            }
            
            if (!empty($doctorFilter)) {
                $countSql .= " AND sr.doctor_id = ?";
                $countParams[] = $doctorFilter;
            }
            
            // 契約フィルター追加（全バージョンを含む）
            if (!empty($contractVersions)) {
                $safeContractIds = implode(',', array_map('intval', $contractVersions));
                $countSql .= " AND sr.contract_id IN ({$safeContractIds})";
            }
            
            // 総件数を取得
            $stmt = $db->prepare($countSql);
            $stmt->execute($countParams);
            $totalRecords = $stmt->fetch()['total'];
            
            // ページネーション情報を計算
            $paginationInfo = $this->calculatePagination($totalRecords, $pagination['page'], $pagination['per_page']);
            
            // ===== データ取得 =====
            $sql = "SELECT sr.*, 
                        u.name as doctor_name,
                        b.name as branch_name,
                        co.regular_visit_hours
                    FROM service_records sr
                    JOIN contracts co ON sr.contract_id = co.id
                    JOIN branches b ON co.branch_id = b.id
                    JOIN users u ON sr.doctor_id = u.id
                    WHERE co.company_id = ?
                    AND co.branch_id IN ({$safeBranchIds})";
            
            $dataParams = [$companyId];
            
            // 同じフィルター条件を適用
            if (!empty($year)) {
                $sql .= " AND YEAR(sr.service_date) = ?";
                $dataParams[] = $year;
            }
            
            if (!empty($month)) {
                $sql .= " AND MONTH(sr.service_date) = ?";
                $dataParams[] = $month;
            }
            
            if (!empty($status)) {
                $sql .= " AND sr.status = ?";
                $dataParams[] = $status;
            }
            
            if (!empty($serviceTypeFilter)) {
                $sql .= " AND sr.service_type = ?";
                $dataParams[] = $serviceTypeFilter;
            }
            
            if (!empty($doctorFilter)) {
                $sql .= " AND sr.doctor_id = ?";
                $dataParams[] = $doctorFilter;
            }
            
            // 契約フィルター追加（全バージョンを含む）
            if (!empty($contractVersions)) {
                $safeContractIds = implode(',', array_map('intval', $contractVersions));
                $sql .= " AND sr.contract_id IN ({$safeContractIds})";
            }
            
            // 並び替え条件を追加
            $sql .= " ORDER BY ";
            switch ($sortColumn) {
                case 'doctor_name':
                    $sql .= "u.name {$sortOrder}, sr.service_date DESC";
                    break;
                case 'branch_name':
                    $sql .= "b.name {$sortOrder}, u.name ASC, sr.service_date DESC";
                    break;
                case 'service_type':
                    $sql .= "sr.service_type {$sortOrder}, sr.service_date DESC";
                    break;
                case 'service_hours':
                    $sql .= "sr.service_hours {$sortOrder}, sr.service_date DESC";
                    break;
                case 'status':
                    $sql .= "sr.status {$sortOrder}, sr.service_date DESC";
                    break;
                case 'visit_type':
                    $sql .= "sr.visit_type {$sortOrder}, sr.service_date DESC";
                    break;
                case 'service_date':
                default:
                    $sql .= "sr.service_date {$sortOrder}, sr.start_time DESC, b.name ASC, u.name ASC";
                    break;
            }
            
            $sql .= " LIMIT " . (int)$pagination['per_page'] . " OFFSET " . (int)$pagination['offset'];
            
            $stmt = $db->prepare($sql);
            $stmt->execute($dataParams);
            $records = $stmt->fetchAll();
            
            $stats = [
                'pending' => count(array_filter($records, function($r) { return $r['status'] === 'pending'; })),
                'approved' => count(array_filter($records, function($r) { return $r['status'] === 'approved'; })),
                'rejected' => count(array_filter($records, function($r) { return $r['status'] === 'rejected'; })),
                'total_hours' => array_sum(array_column($records, 'service_hours'))
            ];
            
            // 拠点制限を考慮した産業医一覧を取得（産業医1人につき1件）
            $doctorsSql = "SELECT DISTINCT u.id, u.name
                            FROM contracts co
                            JOIN users u ON co.doctor_id = u.id
                            WHERE co.company_id = ?
                            AND co.contract_status = 'active'
                            AND co.branch_id IN ({$safeBranchIds})
                            ORDER BY u.name ASC";
            
            $stmt = $db->prepare($doctorsSql);
            $stmt->execute([$companyId]);
            $doctors = $stmt->fetchAll();
            
            // 契約一覧を取得（フィルター用 - 現在有効な契約のみ）
            $currentDate = date('Y-m-01'); // 当月の1日
            $contractsSql = "SELECT co.id, 
                                    b.name as branch_name, 
                                    u.name as doctor_name,
                                    co.effective_date,
                                    co.effective_end_date,
                                    co.effective_date,
                                    co.effective_end_date,
                                    co.version_number,
                                    co.use_remote_consultation,
                                    co.use_document_creation,
                                    co.visit_frequency
                            FROM contracts co
                            JOIN branches b ON co.branch_id = b.id
                            JOIN users u ON co.doctor_id = u.id
                            WHERE co.company_id = ? 
                            AND co.contract_status = 'active'
                            AND co.effective_date <= ?
                            AND (co.effective_end_date IS NULL OR co.effective_end_date >= ?)
                            AND co.branch_id IN ({$safeBranchIds})
                            ORDER BY b.name ASC, u.name ASC";
            
            $stmt = $db->prepare($contractsSql);
            $stmt->execute([$companyId, $currentDate, $currentDate]);
            $contracts = $stmt->fetchAll();
            
            // 利用可能な役務種別を判定（いずれかの契約で有効になっていればOK）
            $availableServiceTypes = []; // 空配列から開始
            $hasRegularContract = false; // 定期契約の有無フラグ
            
            foreach ($contracts as $contract) {
                // 定期契約（毎月・隔月・毎週）のチェック
                if (in_array($contract['visit_frequency'], ['monthly', 'bimonthly', 'weekly'])) {
                    $hasRegularContract = true;
                }
                
                if ($contract['use_remote_consultation'] == 1) {
                    $availableServiceTypes[] = 'remote_consultation';
                }
                if ($contract['use_document_creation'] == 1) {
                    $availableServiceTypes[] = 'document';
                }
                if ($contract['visit_frequency'] === 'spot') {
                    $availableServiceTypes[] = 'spot';
                }
            }
            
            // 定期契約が1件以上ある場合のみ、定期訪問と臨時訪問を追加
            if ($hasRegularContract) {
                $availableServiceTypes[] = 'regular';
                $availableServiceTypes[] = 'emergency';
            }
            
            // 重複を削除
            $availableServiceTypes = array_unique($availableServiceTypes);
            
            // ビューに渡すデータにページネーション情報と並び替え情報を追加
            $data = [
                'records' => $records,
                'stats' => $stats,
                'doctors' => $doctors,
                'contracts' => $contracts,
                'availableServiceTypes' => $availableServiceTypes,
                'month' => $month,
                'year' => $year,
                'status' => $status,
                'doctorFilter' => $doctorFilter,
                'serviceTypeFilter' => $serviceTypeFilter,
                'contractFilter' => $contractFilter,
                'pagination' => $paginationInfo,
                'sortColumn' => $sortColumn,
                'sortOrder' => $sortOrder
            ];
            
            extract($data);
            
            ob_start();
            include __DIR__ . '/../views/service_records/company_index.php';
            $content = ob_get_clean();
            
            include __DIR__ . '/../views/layouts/base.php';

        } elseif ($userType === 'admin') {
            redirect('service_records/admin');
            return;
        } else {
            redirect('dashboard');
        }
    }

    public function show($id) {
        Session::requireLogin();
        
        require_once __DIR__ . '/../models/ServiceRecord.php';
        require_once __DIR__ . '/../models/Contract.php';
        
        $serviceModel = new ServiceRecord();
        $record = $serviceModel->findById($id);
        
        if (!$record) {
            $this->setFlash('error', '役務記録が見つかりません。');
            redirect('service_records');
            return;
        }
        
        // 権限チェック
        $userType = Session::get('user_type');
        $userId = Session::get('user_id');
        
        if ($userType === 'doctor' && $record['doctor_id'] != $userId) {
            $this->setFlash('error', '権限がありません。');
            redirect('service_records');
            return;
        } elseif ($userType === 'company') {
            // 企業は自社の契約に関する記録のみ閲覧可能（拠点制限付き）
            $contractModel = new Contract();
            $contract = $contractModel->findById($record['contract_id']);
            
            if (!$contract || $contract['company_id'] != Session::get('company_id')) {
                $this->setFlash('error', '権限がありません。');
                redirect('service_records');
                return;
            }
            
            // 拠点レベルの権限チェックを追加
            $accessibleBranchIds = $this->getAccessibleBranchIds(Session::get('company_id'), $userId);
            if (!in_array($contract['branch_id'], $accessibleBranchIds)) {
                $this->setFlash('error', 'この拠点の記録にアクセスする権限がありません。');
                redirect('service_records');
                return;
            }
        }
        
        // 契約情報を取得
        $contractModel = new Contract();
        $contract = $contractModel->findById($record['contract_id']);
        
        // データを渡してビューを表示
        $data = [
            'record' => $record,
            'contract' => $contract,
            'userType' => $userType
        ];
        
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/service_records/show.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 役務詳細をJSON形式で返す(企業ユーザー向け)
     */
    public function detail($id) {
        Session::requireLogin();
        
        header('Content-Type: application/json; charset=utf-8');
        
        require_once __DIR__ . '/../models/ServiceRecord.php';
        require_once __DIR__ . '/../models/Contract.php';
        require_once __DIR__ . '/../core/Database.php';
        
        $db = Database::getInstance()->getConnection();
        $userType = Session::get('user_type');
        $userId = Session::get('user_id');
        
        try {
            // 役務記録を取得(JOINで関連情報も取得)
            $sql = "SELECT 
                        sr.*,
                        sr.description as service_content,
                        u.name as doctor_name,
                        b.name as branch_name,
                        c.name as company_name,
                        co.company_id,
                        co.branch_id,
                        approver.name as approved_by_name
                    FROM service_records sr
                    JOIN contracts co ON sr.contract_id = co.id
                    JOIN users u ON sr.doctor_id = u.id
                    JOIN branches b ON co.branch_id = b.id
                    JOIN companies c ON co.company_id = c.id
                    LEFT JOIN users approver ON sr.approved_by = approver.id
                    WHERE sr.id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                echo json_encode([
                    'success' => false,
                    'message' => '役務記録が見つかりません'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // 権限チェック
            if ($userType === 'doctor' && $record['doctor_id'] != $userId) {
                echo json_encode([
                    'success' => false,
                    'message' => '権限がありません'
                ], JSON_UNESCAPED_UNICODE);
                return;
            } elseif ($userType === 'company') {
                // 企業は自社の契約に関する記録のみ閲覧可能(拠点制限付き)
                $companyId = Session::get('company_id');
                
                if ($record['company_id'] != $companyId) {
                    echo json_encode([
                        'success' => false,
                        'message' => '権限がありません'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
                
                // 拠点レベルの権限チェック
                $accessibleBranchIds = $this->getAccessibleBranchIds($companyId, $userId);
                
                if (!in_array($record['branch_id'], $accessibleBranchIds)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'この拠点の記録にアクセスする権限がありません'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
            }
            
            // 日時のフォーマット
            if ($record['created_at']) {
                $record['created_at'] = date('Y/m/d H:i', strtotime($record['created_at']));
            }
            if ($record['updated_at']) {
                $record['updated_at'] = date('Y/m/d H:i', strtotime($record['updated_at']));
            }
            if ($record['approved_at']) {
                $record['approved_at'] = date('Y/m/d H:i', strtotime($record['approved_at']));
            }
            if ($record['rejected_at']) {
                $record['rejected_at'] = date('Y/m/d H:i', strtotime($record['rejected_at']));
            }
            
            // 役務日のフォーマット
            if ($record['service_date']) {
                $record['service_date'] = date('Y年n月j日', strtotime($record['service_date']));
            }
            
            // 成功レスポンス
            echo json_encode([
                'success' => true,
                'record' => $record
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            error_log("役務詳細取得エラー: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'データの取得に失敗しました'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    public function create() {
        Session::requireUserType('doctor');
        
        $doctorId = Session::get('user_id');
        
        // 遷移元情報を取得・保存
        $returnTo = $_GET['return_to'] ?? null;
        if ($returnTo && in_array($returnTo, ['dashboard', 'service_records', 'contracts'])) {
            Session::set('service_record_return_to', $returnTo);
        }

        // URLパラメータから初期値を取得
        $preselectedContractId = (int)($_GET['contract_id'] ?? 0);
        $preselectedServiceType = $_GET['service_type'] ?? '';
        
        // バリデーションエラー後の場合、POSTデータから値を復元
        $formData = [
            'contract_id' => $_POST['contract_id'] ?? ($_GET['contract_id'] ?? ''),
            'service_date' => $_POST['service_date'] ?? date('Y-m-d'),
            'service_type' => $_POST['service_type'] ?? ($_GET['service_type'] ?? 'regular'),
            'visit_type' => $_POST['visit_type'] ?? 'visit',
            'start_time' => $_POST['start_time'] ?? '',
            'end_time' => $_POST['end_time'] ?? '',
            'description' => $_POST['description'] ?? '',
            'overtime_reason' => $_POST['overtime_reason'] ?? ''
        ];
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 現在日または指定された役務日に有効な契約を取得
        $targetDate = $formData['service_date'] ?? date('Y-m-d');
        
        // 位置パラメータを使用したシンプルなSQL
        $contractsSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                    FROM contracts co
                    JOIN branches b ON co.branch_id = b.id
                    JOIN companies c ON co.company_id = c.id
                    WHERE co.doctor_id = ? 
                    AND co.contract_status = 'active'
                    AND co.effective_date <= ?
                    AND (co.effective_end_date IS NULL OR co.effective_end_date >= ?)
                    AND co.start_date <= ?
                    AND (co.end_date IS NULL OR co.end_date >= ?)
                    ORDER BY c.name ASC, b.name ASC, co.effective_date DESC";
        
        try {
            $stmt = $db->prepare($contractsSql);
            
            // 位置パラメータでバインド
            $stmt->bindValue(1, $doctorId, PDO::PARAM_INT);
            $stmt->bindValue(2, $targetDate, PDO::PARAM_STR);
            $stmt->bindValue(3, $targetDate, PDO::PARAM_STR);
            $stmt->bindValue(4, $targetDate, PDO::PARAM_STR);
            $stmt->bindValue(5, $targetDate, PDO::PARAM_STR);
            
            $stmt->execute();
            $contracts = $stmt->fetchAll();
                        
        } catch (PDOException $e) {
            error_log('PDO Error in create: ' . $e->getMessage());
            error_log('Error Info: ' . print_r($e->errorInfo, true));
            $contracts = [];
        }
        
        // 事前選択された契約の詳細を取得
        $preselectedContract = null;
        if ($formData['contract_id'] > 0) {
            foreach ($contracts as $contract) {
                if ($contract['id'] == $formData['contract_id']) {
                    $preselectedContract = $contract;
                    break;
                }
            }
        }
        
        // 役務種別が事前選択されている場合の検証
        $validServiceTypes = ['regular', 'emergency', 'extension', 'document', 'remote_consultation', 'spot', 'other'];
        if (!empty($formData['service_type']) && !in_array($formData['service_type'], $validServiceTypes)) {
            $formData['service_type'] = 'regular';
        }
        
        // 訪問種別の検証
        $validVisitTypes = ['visit', 'online'];
        if (!empty($formData['visit_type']) && !in_array($formData['visit_type'], $validVisitTypes)) {
            $formData['visit_type'] = 'visit';
        }
        
        // 時間記録なし役務の場合の特別処理
        $isNoTimeService = in_array($formData['service_type'], ['document', 'remote_consultation', 'other']);
        if ($isNoTimeService) {
            $this->setFlash('info', get_service_type_label($formData['service_type']) . 'の記録を作成します。業務内容を詳細に記載してください。');
        }

        // 契約が事前選択されている場合の締め処理チェック
        $closedWarning = false;
        if (!empty($formData['contract_id']) && !empty($formData['service_date'])) {
            if ($this->checkServiceRecordClosedStatus($formData['contract_id'], $formData['service_date'])) {
                $closedWarning = true;
                $serviceYear = date('Y', strtotime($formData['service_date']));
                $serviceMonth = date('n', strtotime($formData['service_date']));
                $this->setFlash('warning', "選択された日付({$serviceYear}年{$serviceMonth}月)は既に締め処理が完了しています。別の日付を選択してください。");
            }
        }
        
        // ビューに渡すデータ
        $data = [
            'contracts' => $contracts,
            'preselected_contract_id' => $formData['contract_id'],
            'preselected_service_type' => $formData['service_type'],
            'preselected_contract' => $preselectedContract,
            'is_no_time_service' => $isNoTimeService,
            'form_data' => $formData,
            'target_date' => $targetDate
        ];
        
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/service_records/create.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * API: 役務日に基づく有効な契約リストを取得
     */
    public function getContractsByServiceDate() {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $doctorId = Session::get('user_id');
            $serviceDate = $_POST['service_date'] ?? '';
            
            if (empty($serviceDate)) {
                echo json_encode([
                    'success' => false,
                    'error' => '役務日が指定されていません'
                ]);
                return;
            }
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // SQLクエリをシンプルに修正
            $sql = "SELECT co.*, c.name as company_name, b.name as branch_name
                    FROM contracts co
                    JOIN branches b ON co.branch_id = b.id
                    JOIN companies c ON co.company_id = c.id
                    WHERE co.doctor_id = ? 
                    AND co.contract_status = 'active'
                    AND co.effective_date <= ?
                    AND (co.effective_end_date IS NULL OR co.effective_end_date >= ?)
                    AND co.start_date <= ?
                    AND (co.end_date IS NULL OR co.end_date >= ?)
                    ORDER BY c.name ASC, b.name ASC, co.effective_date DESC";
                        
            $stmt = $db->prepare($sql);
            
            // 位置パラメータでバインド
            $stmt->bindValue(1, $doctorId, PDO::PARAM_INT);
            $stmt->bindValue(2, $serviceDate, PDO::PARAM_STR);
            $stmt->bindValue(3, $serviceDate, PDO::PARAM_STR);
            $stmt->bindValue(4, $serviceDate, PDO::PARAM_STR);
            $stmt->bindValue(5, $serviceDate, PDO::PARAM_STR);
                        
            $result = $stmt->execute();
                        
            $contracts = $stmt->fetchAll();
                        
            // 各契約に訪問月判定を追加
            foreach ($contracts as &$contract) {
                $year = date('Y', strtotime($serviceDate));
                $month = date('n', strtotime($serviceDate));
                $contract['is_visit_month'] = is_visit_month($contract, $year, $month);
            }
            unset($contract);
                        
            echo json_encode([
                'success' => true,
                'contracts' => $contracts,
                'service_date' => $serviceDate
            ]);
            
        } catch (PDOException $e) {
            error_log('PDO Error in getContractsByServiceDate: ' . $e->getMessage());
            error_log('Error Code: ' . $e->getCode());
            error_log('SQL State: ' . $e->errorInfo[0]);
            error_log('Driver Error Code: ' . $e->errorInfo[1]);
            error_log('Driver Error Message: ' . $e->errorInfo[2]);
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            echo json_encode([
                'success' => false,
                'error' => 'データベースエラーが発生しました',
                'debug' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            error_log('General Error in getContractsByServiceDate: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました',
                'debug' => $e->getMessage()
            ]);
        }
    }

    public function store() {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            $this->redirectBackWithInput();
            return;
        }
        
        // 入力データを取得
        $data = [
            'contract_id' => (int)($_POST['contract_id'] ?? 0),
            'doctor_id' => Session::get('user_id'),
            'service_date' => sanitize($_POST['service_date'] ?? ''),
            'service_type' => sanitize($_POST['service_type'] ?? 'regular'),
            'visit_type' => sanitize($_POST['visit_type'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'overtime_reason' => sanitize($_POST['overtime_reason'] ?? ''),
            'direct_billing_amount' => null,  // 初期化
            'status' => 'pending'
        ];

        // その他の場合は請求金額を取得
        if ($data['service_type'] === 'other') {
            $data['direct_billing_amount'] = !empty($_POST['direct_billing_amount']) ? (int)$_POST['direct_billing_amount'] : null;
        }

        // 訪問種別の検証と設定
        if (requires_visit_type_selection($data['service_type'])) {
            if (empty($data['visit_type']) || !in_array($data['visit_type'], ['visit', 'online'])) {
                $data['visit_type'] = 'visit'; // デフォルトは訪問
            }
        } else {
            $data['visit_type'] = null;
        }

        // 役務種別に応じた時間処理
        if (requires_time_input($data['service_type'])) {
            $data['start_time'] = sanitize($_POST['start_time'] ?? '');
            $data['end_time'] = sanitize($_POST['end_time'] ?? '');
        } else {
            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['service_hours'] = 0;
        }

        // *** 締め処理済みチェックを追加 ***
        if (!empty($data['contract_id']) && !empty($data['service_date'])) {
            if ($this->checkServiceRecordClosedStatus($data['contract_id'], $data['service_date'])) {
                $serviceYear = date('Y', strtotime($data['service_date']));
                $serviceMonth = date('n', strtotime($data['service_date']));
                $this->setFlash('error', "選択された役務日（{$serviceYear}年{$serviceMonth}月）は既に締め処理が完了しているため、新しい役務記録を登録することはできません。");
                $this->redirectBackWithInput();
                return;
            }
        }
        
        // バリデーション
        $errors = [];
        
        if (empty($data['contract_id'])) {
            $errors[] = '契約を選択してください。';
        }
        
        if (empty($data['service_date'])) {
            $errors[] = '役務日を入力してください。';
        }
        
        if (!in_array($data['service_type'], ['regular', 'emergency', 'extension', 'document', 'remote_consultation', 'spot', 'other'])) {
            $errors[] = '正しい役務種別を選択してください。';
        }
        
        // **非訪問月の定期訪問バリデーション追加**
        if ($data['service_type'] === 'regular' && !empty($data['contract_id']) && !empty($data['service_date'])) {
            // 契約情報を取得
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $contractSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                        FROM contracts co
                        JOIN branches b ON co.branch_id = b.id
                        JOIN companies c ON co.company_id = c.id
                        WHERE co.id = :contract_id";
            
            $stmt = $db->prepare($contractSql);
            $stmt->execute(['contract_id' => $data['contract_id']]);
            $contract = $stmt->fetch();
            
            if ($contract) {
                $serviceYear = date('Y', strtotime($data['service_date']));
                $serviceMonth = date('n', strtotime($data['service_date']));
                
                // 非訪問月かどうかを判定
                if (!is_visit_month($contract, $serviceYear, $serviceMonth)) {
                    $errors[] = '選択された役務日は隔月契約の非訪問月です。定期訪問は実施できません。臨時訪問をご選択ください。';
                }
            }
        }

        // 訪問種別のバリデーション
        if (requires_visit_type_selection($data['service_type'])) {
            if (empty($data['visit_type']) || !in_array($data['visit_type'], ['visit', 'online'])) {
                $errors[] = '訪問種別を選択してください。';
            }
        }
        
        // 時間が必要な役務種別の場合のバリデーション（スポットも含む）
        if (requires_time_input($data['service_type'])) {
            if (empty($data['start_time'])) {
                $errors[] = '開始時刻を入力してください。';
            }
            
            if (empty($data['end_time'])) {
                $errors[] = '終了時刻を入力してください。';
            }
            
            if (!empty($data['start_time']) && !empty($data['end_time']) && $data['start_time'] >= $data['end_time']) {
                $errors[] = '終了時刻は開始時刻より後に設定してください。';
            }
            
            // スポットの場合は役務内容も必須
            if ($data['service_type'] === 'spot' && empty($data['description'])) {
                $errors[] = 'スポットの場合、役務内容の入力は必須です。';
            }
            
            // 時間重複チェック
            if (empty($errors)) {
                $overlapCheck = $this->checkTimeOverlap(
                    $data['doctor_id'], 
                    $data['service_date'], 
                    $data['start_time'], 
                    $data['end_time']
                );
                
                if (!$overlapCheck['valid']) {
                    $errors[] = $overlapCheck['message'];
                }
            }
            
            // 役務時間を計算
            if (empty($errors)) {
                $serviceHours = calculate_service_hours($data['start_time'], $data['end_time'], $data['service_type']);
                $data['service_hours'] = $serviceHours;
                
                // 定期訪問の場合、延長理由の必要性をチェック
                if ($data['service_type'] === 'regular' && !empty($data['contract_id'])) {
                    $overtimeCheck = $this->checkOvertimeRequirement(
                        $data['contract_id'], 
                        $data['service_date'], 
                        $serviceHours,
                        null  // 新規作成時はexcludeIdなし
                    );
                    
                    if ($overtimeCheck['requires_reason']) {
                        // 延長理由が必要な場合
                        if (empty($data['overtime_reason'])) {
                            $errors[] = $overtimeCheck['message'] . '延長理由を入力してください。';
                        }
                    } else {
                        // 延長理由が不要な場合はクリア
                        $data['overtime_reason'] = null;
                    }
                }
            }
        } else {
            // 書面作成・遠隔相談・その他の場合
            $data['service_hours'] = 0;
            
            // その他の場合の請求金額バリデーション
            if ($data['service_type'] === 'other') {
                if (empty($data['direct_billing_amount']) || $data['direct_billing_amount'] <= 0) {
                    $errors[] = 'その他の場合、請求金額の入力は必須です。';
                }
            }
            
            // 業務内容は必須
            if (empty($data['description'])) {
                $errors[] = '書面作成・遠隔相談・その他の場合、業務内容の入力は必須です。';
            }
        }
        
        // エラーがある場合はリダイレクト（入力値を保持）
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            $this->redirectBackWithInput();
            return;
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // トランザクション開始
        $db->beginTransaction();
        
        try {
            $serviceModel = new ServiceRecord();
            $createdBy = Session::get('user_id');
            
            // 【修正】定期訪問も含めて通常処理のみ（分割処理を削除）
            $data['is_auto_split'] = false;
            
            // 履歴対応版で作成
            $recordId = $serviceModel->createWithHistory($data, $createdBy);
            
            if (!$recordId) {
                throw new Exception('役務記録の作成に失敗しました。');
            }
            
            $serviceTypeLabel = get_service_type_label($data['service_type']);
            if (requires_visit_type_selection($data['service_type'])) {
                $visitTypeLabel = get_visit_type_label($data['visit_type']);
                $message = "{$serviceTypeLabel}({$visitTypeLabel})の記録を登録しました。";
            } else {
                $message = "{$serviceTypeLabel}の記録を登録しました。";
            }
            
            // 月次サマリーを更新（時間のある役務のみ）
            if (requires_time_input($data['service_type']) || counts_toward_regular_hours($data['service_type'])) {
                $summaryModel = new MonthlySummary();
                $serviceDate = $data['service_date'];
                $year = date('Y', strtotime($serviceDate));
                $month = date('n', strtotime($serviceDate));
                
                $summaryModel->updateSummaryBasic($data['contract_id'], $year, $month);
            }
            
            // トランザクションコミット
            $db->commit();
            
            $this->setFlash('success', $message);
            
            // 遷移先を決定
            $returnTo = Session::get('service_record_return_to');
            Session::remove('service_record_return_to');
            
            switch ($returnTo) {
                case 'dashboard':
                    redirect('dashboard');
                    break;
                case 'contracts':
                    redirect('contracts');
                    break;
                case 'service_records':
                default:
                    redirect('service_records');
                    break;
            }

        } catch (Exception $e) {
            // トランザクションロールバック
            $db->rollback();
            error_log('Transaction failed in store: ' . $e->getMessage());
            $this->setFlash('error', '役務記録の登録中にエラーが発生しました: ' . $e->getMessage());
            $this->redirectBackWithInput();
            return;
        }
    }

    public function edit($id) {
        Session::requireUserType('doctor');
        
        // セッションから保存された入力値を取得し、使用後は削除
        $savedInput = $_SESSION['form_input'] ?? [];
        unset($_SESSION['form_input']);
        
        $serviceModel = new ServiceRecord();
        $record = $serviceModel->findById($id);
        
        if (!$record) {
            $this->setFlash('error', '役務記録が見つかりません。');
            redirect('service_records');
            return;
        }
        
        // 権限チェック
        if ($record['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('service_records');
            return;
        }
        
        // 役務種別を取得
        $serviceType = $record['service_type'] ?? 'regular';
        
        // 進行中の記録は編集不可(時間管理が必要な役務のみ)
        $requiresTimeTracking = in_array($serviceType, ['regular', 'emergency', 'extension']);
        
        if ($requiresTimeTracking && $record['service_hours'] == 0) {
            $this->setFlash('error', '進行中の役務記録は役務終了画面から編集してください。');
            redirect('service_records/active');
            return;
        }
        
        // 承認済み・差戻し済みの記録は編集不可
        if ($record['status'] === 'approved') {
            $this->setFlash('error', '承認済みの役務記録は編集できません。');
            redirect('service_records');
            return;
        }
        
        // 承認待ち(pending)または差戻し(rejected)の記録のみ編集可能
        if ($record['status'] !== 'pending' && $record['status'] !== 'rejected') {
            $this->setFlash('error', '承認待ちまたは差戻しの役務記録のみ編集できます。');
            redirect('service_records');
            return;
        }

        // 締め処理済みチェック
        if ($this->checkServiceRecordClosedStatus($record['contract_id'], $record['service_date'])) {
            $serviceYear = date('Y', strtotime($record['service_date']));
            $serviceMonth = date('n', strtotime($record['service_date']));
            $this->setFlash('warning', "この役務記録({$serviceYear}年{$serviceMonth}月)は既に締め処理が完了しているため、編集できません。");
            redirect('service_records');
            return;
        }
        
        // フォームデータの優先順位: セッション保存データ > POST > 既存レコード > デフォルト値
        $formData = [];
        $fields = ['contract_id', 'service_date', 'service_type', 'visit_type', 'start_time', 'end_time', 'description', 'overtime_reason'];
        
        foreach ($fields as $field) {
            if (isset($savedInput[$field])) {
                $formData[$field] = $savedInput[$field];
            } elseif (isset($_POST[$field])) {
                $formData[$field] = $_POST[$field];
            } else {
                $formData[$field] = $record[$field] ?? '';
            }
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 役務日(または既存の役務日)を基準に有効な契約のみを取得
        $targetDate = !empty($formData['service_date']) ? $formData['service_date'] : $record['service_date'];
        
        // ★修正: 位置指定パラメーター(?)を使用
        $contractsSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                        FROM contracts co
                        JOIN branches b ON co.branch_id = b.id
                        JOIN companies c ON co.company_id = c.id
                        WHERE co.doctor_id = ? 
                        AND co.contract_status = 'active'
                        AND co.effective_date <= ?
                        AND (co.effective_end_date IS NULL OR co.effective_end_date >= ?)
                        AND co.start_date <= ?
                        AND (co.end_date IS NULL OR co.end_date >= ?)
                        ORDER BY c.name ASC, b.name ASC";
        
        $stmt = $db->prepare($contractsSql);
        $stmt->execute([
            Session::get('user_id'),
            $targetDate,
            $targetDate,
            $targetDate,
            $targetDate
        ]);
        $contracts = $stmt->fetchAll();
        
        // 現在の契約詳細を拠点情報付きで取得
        $contractSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                    FROM contracts co
                    JOIN branches b ON co.branch_id = b.id
                    JOIN companies c ON co.company_id = c.id
                    WHERE co.id = ?";
        
        $stmt = $db->prepare($contractSql);
        $stmt->execute([$record['contract_id']]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            $this->setFlash('error', '契約情報が見つかりません。');
            redirect('service_records');
            return;
        }
        
        // 全ての役務種別で月間統計を取得(時間なし役務も含む)
        $currentMonth = date('n', strtotime($record['service_date']));
        $currentYear = date('Y', strtotime($record['service_date']));
        
        // 役務種別ごとの統計を取得(編集中の記録を除く)
        $statsSql = "SELECT 
                        COALESCE(service_type, 'regular') as service_type,
                        SUM(service_hours) as total_hours,
                        COUNT(*) as record_count
                    FROM service_records 
                    WHERE contract_id = ? 
                    AND YEAR(service_date) = ? 
                    AND MONTH(service_date) = ?
                    AND id != ?
                    GROUP BY COALESCE(service_type, 'regular')";
        
        $stmt = $db->prepare($statsSql);
        $stmt->execute([
            $record['contract_id'],
            $currentYear,
            $currentMonth,
            $id
        ]);
        
        $statsResults = $stmt->fetchAll();
        
        // 統計データを整理
        $monthlyStats = [
            'regular' => ['hours' => 0, 'count' => 0],
            'emergency' => ['hours' => 0, 'count' => 0],
            'extension' => ['hours' => 0, 'count' => 0],
            'spot' => ['hours' => 0, 'count' => 0],
            'document' => ['hours' => 0, 'count' => 0],
            'remote_consultation' => ['hours' => 0, 'count' => 0],
            'other' => ['hours' => 0, 'count' => 0]
        ];
        
        foreach ($statsResults as $result) {
            $statServiceType = $result['service_type'] ?? 'regular';
            if (isset($monthlyStats[$statServiceType])) {
                $monthlyStats[$statServiceType]['hours'] = (float)$result['total_hours'];
                $monthlyStats[$statServiceType]['count'] = (int)$result['record_count'];
            }
        }
        
        // ビューに渡すデータ
        // 契約の役務種別の利用可否を判断（厳密な比較を使用）
        $useRemoteConsultation = isset($contract['use_remote_consultation']) && $contract['use_remote_consultation'] == 1;
        $useDocumentCreation = isset($contract['use_document_creation']) && $contract['use_document_creation'] == 1;
        
        $data = [
            'record' => $record,
            'contracts' => $contracts,
            'contract' => $contract,
            'monthlyStats' => $monthlyStats,
            'serviceType' => $serviceType,
            'requiresTimeTracking' => $requiresTimeTracking,
            'form_data' => $formData,
            'target_date' => $targetDate,
            'useRemoteConsultation' => $useRemoteConsultation,
            'useDocumentCreation' => $useDocumentCreation
        ];
        
        // 変数を展開
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/service_records/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function update($id) {
        Session::requireUserType('doctor');
        
        $serviceModel = new ServiceRecord();
        $record = $serviceModel->findById($id);
        
        if (!$record) {
            $this->setFlash('error', '役務記録が見つかりません。');
            redirect('service_records');
        }
        
        // 権限チェック
        if ($record['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('service_records');
        }
        
        // 進行中の記録は編集不可
        if ($record['service_hours'] == 0 && requires_active_tracking($record['service_type'] ?? 'regular')) {
            $this->setFlash('error', '進行中の役務記録は編集できません。');
            redirect('service_records/active');
        }
        
        // 承認済みの記録は編集不可
        if ($record['status'] === 'approved') {
            $this->setFlash('error', '承認済みの役務記録は編集できません。');
            redirect('service_records');
        }
        
        // 元の記録が締め処理済みかチェック
        if ($this->checkServiceRecordClosedStatus($record['contract_id'], $record['service_date'])) {
            $serviceYear = date('Y', strtotime($record['service_date']));
            $serviceMonth = date('n', strtotime($record['service_date']));
            $this->setFlash('error', "この役務記録({$serviceYear}年{$serviceMonth}月)は既に締め処理が完了しているため、編集することはできません。");
            redirect('service_records');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            $this->redirectBackWithInputForEdit($id);
            return;
        }
        
        // 入力データを取得
        $data = [
            'contract_id' => (int)($_POST['contract_id'] ?? 0),
            'service_date' => sanitize($_POST['service_date'] ?? ''),
            'service_type' => sanitize($_POST['service_type'] ?? 'regular'),
            'visit_type' => sanitize($_POST['visit_type'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'overtime_reason' => sanitize($_POST['overtime_reason'] ?? ''),
            'direct_billing_amount' => null  // 初期化
        ];
        
        // その他の場合は請求金額を取得
        if ($data['service_type'] === 'other') {
            $data['direct_billing_amount'] = !empty($_POST['direct_billing_amount']) ? (int)$_POST['direct_billing_amount'] : null;
        }
        
        // ★追加: 契約が役務日に対して有効かチェック
        if (!$this->isContractValidForServiceDate($data['contract_id'], $data['service_date'], Session::get('user_id'))) {
            $this->setFlash('error', '選択された契約は指定の役務日に対して有効ではありません。別の契約または役務日を選択してください。');
            $this->redirectBackWithInputForEdit($id);
            return;
        }
        
        // 訪問種別の検証と設定
        if (requires_visit_type_selection($data['service_type'])) {
            if (empty($data['visit_type']) || !in_array($data['visit_type'], ['visit', 'online'])) {
                $data['visit_type'] = $record['visit_type'] ?? 'visit';
            }
        } else {
            $data['visit_type'] = null;
        }
        
        // 役務種別に応じた時間処理
        if (requires_time_input($data['service_type'])) {
            $data['start_time'] = sanitize($_POST['start_time'] ?? '');
            $data['end_time'] = sanitize($_POST['end_time'] ?? '');
        } else {
            $data['start_time'] = null;
            $data['end_time'] = null;
            $data['service_hours'] = 0;
            $data['visit_type'] = null;
            $data['overtime_reason'] = null;
        }

        // 新しい役務日が締め処理済みかチェック(日付変更の場合)
        if (!empty($data['contract_id']) && !empty($data['service_date'])) {
            if ($data['service_date'] !== $record['service_date']) {
                if ($this->checkServiceRecordClosedStatus($data['contract_id'], $data['service_date'])) {
                    $serviceYear = date('Y', strtotime($data['service_date']));
                    $serviceMonth = date('n', strtotime($data['service_date']));
                    $this->setFlash('error', "変更先の役務日({$serviceYear}年{$serviceMonth}月)は既に締め処理が完了しているため、この日付に変更することはできません。");
                    $this->redirectBackWithInputForEdit($id);
                    return;
                }
            }
        }
        
        // バリデーション
        $errors = [];
        
        if (empty($data['contract_id'])) {
            $errors[] = '契約を選択してください。';
        }
        
        if (empty($data['service_date'])) {
            $errors[] = '役務日を入力してください。';
        }
        
        if (!in_array($data['service_type'], ['regular', 'emergency', 'extension', 'document', 'remote_consultation', 'spot', 'other'])) {
            $errors[] = '正しい役務種別を選択してください。';
        }

        // 非訪問月の定期訪問バリデーション追加
        if ($data['service_type'] === 'regular' && !empty($data['contract_id']) && !empty($data['service_date'])) {
            $contractModel = new Contract();
            $contract = $contractModel->findById($data['contract_id']);
            
            if ($contract) {
                $serviceYear = date('Y', strtotime($data['service_date']));
                $serviceMonth = date('n', strtotime($data['service_date']));
                
                if (!is_visit_month($contract, $serviceYear, $serviceMonth)) {
                    $errors[] = '選択された役務日は隔月契約の非訪問月です。定期訪問は実施できません。臨時訪問をご選択ください。';
                }
            }
        }
        
        // 訪問種別のバリデーション
        if (requires_visit_type_selection($data['service_type'])) {
            if (empty($data['visit_type']) || !in_array($data['visit_type'], ['visit', 'online'])) {
                $errors[] = '訪問種別を選択してください。';
            }
        }
        
        // 時間が必要な役務種別の場合のバリデーション
        if (requires_time_input($data['service_type'])) {
            if (empty($data['start_time'])) {
                $errors[] = '開始時刻を入力してください。';
            }
            
            if (empty($data['end_time'])) {
                $errors[] = '終了時刻を入力してください。';
            }
            
            if (!empty($data['start_time']) && !empty($data['end_time']) && $data['start_time'] >= $data['end_time']) {
                $errors[] = '終了時刻は開始時刻より後に設定してください。';
            }
            
            // スポットの場合は役務内容も必須
            if ($data['service_type'] === 'spot' && empty($data['description'])) {
                $errors[] = 'スポットの場合、役務内容の入力は必須です。';
            }
            
            // 時間重複チェック(編集時は自分の記録IDを除外)
            if (empty($errors)) {
                $overlapCheck = $this->checkTimeOverlap(
                    Session::get('user_id'), 
                    $data['service_date'], 
                    $data['start_time'], 
                    $data['end_time'], 
                    $id
                );
                
                if (!$overlapCheck['valid']) {
                    $errors[] = $overlapCheck['message'];
                }
            }
            
            // 役務時間を計算
            if (empty($errors)) {
                $newServiceHours = calculate_service_hours($data['start_time'], $data['end_time'], $data['service_type']);
                $data['service_hours'] = $newServiceHours;
                
                if ($data['service_type'] === 'regular' && !empty($data['contract_id'])) {
                    // 延長理由の必要性をチェック
                    $overtimeCheck = $this->checkOvertimeRequirement(
                        $data['contract_id'], 
                        $data['service_date'], 
                        $newServiceHours,
                        $id
                    );
                    
                    if ($overtimeCheck['requires_reason']) {
                        // 延長理由が必要な場合
                        if (empty($data['overtime_reason'])) {
                            $errors[] = $overtimeCheck['message'] . '延長理由を入力してください。';
                        }
                    } else {
                        // 延長理由が不要な場合はクリア
                        $data['overtime_reason'] = null;
                    }
                }
            }
        } else {
            $data['service_hours'] = 0;
            
            if (empty($data['description'])) {
                $errors[] = '書面作成・遠隔相談の場合、業務内容の入力は必須です。';
            }
        }
        
        // 差し戻し記録を修正する場合はステータスを承認待ちに戻す
        if ($record['status'] === 'rejected') {
            $data['status'] = 'pending';
            $data['company_comment'] = null;
            $data['rejected_at'] = null;
            $data['rejected_by'] = null;
        }
        
        // エラーがある場合はリダイレクト(入力値を保持)
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            $this->redirectBackWithInputForEdit($id);
            return;
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $db->beginTransaction();
        
        try {
            $updatedBy = Session::get('user_id');
            
            $data['is_auto_split'] = false;
            
            if (!$serviceModel->updateWithHistory($id, $data, $updatedBy)) {
                throw new Exception('役務記録の更新に失敗しました。');
            }
            
            $serviceTypeLabel = get_service_type_label($data['service_type']);
            if (requires_visit_type_selection($data['service_type'])) {
                $visitTypeLabel = get_visit_type_label($data['visit_type']);
                $message = "{$serviceTypeLabel}({$visitTypeLabel})の記録を更新しました。";
            } else {
                $message = "{$serviceTypeLabel}の記録を更新しました。";
            }
            
            if (requires_time_input($data['service_type']) || counts_toward_regular_hours($data['service_type'])) {
                $summaryModel = new MonthlySummary();
                $serviceDate = $data['service_date'];
                $year = date('Y', strtotime($serviceDate));
                $month = date('n', strtotime($serviceDate));
                
                $summaryModel->updateSummaryBasic($data['contract_id'], $year, $month);
                
                if ($record['service_date'] !== $data['service_date']) {
                    $originalYear = date('Y', strtotime($record['service_date']));
                    $originalMonth = date('n', strtotime($record['service_date']));
                    
                    if ($originalYear !== $year || $originalMonth !== $month) {
                        $summaryModel->updateSummaryBasic($record['contract_id'], $originalYear, $originalMonth);
                    }
                }
            }
            
            $this->logSecurityEvent('service_record_updated', [
                'record_id' => $id,
                'contract_id' => $data['contract_id'],
                'service_date' => $data['service_date'],
                'service_hours' => $data['service_hours'],
                'service_type' => $data['service_type'],
                'visit_type' => $data['visit_type'],
                'was_split' => false,
                'original_hours' => $record['service_hours'],
                'new_hours' => $data['service_hours'],
                'overtime_reason_cleared' => (empty($data['overtime_reason']) && !empty($record['overtime_reason']))
            ]);
            
            $db->commit();
            
            if ($record['status'] === 'rejected') {
                $this->setFlash('success', '差し戻された役務記録を修正しました。企業による再審査をお待ちください。');
            } else {
                $this->setFlash('success', $message);
            }
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('Transaction failed in update: ' . $e->getMessage());
            $this->setFlash('error', '役務記録の更新中にエラーが発生しました: ' . $e->getMessage());
        }
        
        redirect('service_records');
    }

    /**
     * 契約が指定の役務日に対して有効かチェック
     */
    private function isContractValidForServiceDate($contractId, $serviceDate, $doctorId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // ★修正: 位置指定パラメーター(?)を使用
        $sql = "SELECT COUNT(*) as count
                FROM contracts
                WHERE id = ?
                AND doctor_id = ?
                AND contract_status = 'active'
                AND effective_date <= ?
                AND (effective_end_date IS NULL OR effective_end_date >= ?)
                AND start_date <= ?
                AND (end_date IS NULL OR end_date >= ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $contractId,
            $doctorId,
            $serviceDate,
            $serviceDate,
            $serviceDate,
            $serviceDate
        ]);
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * 定期訪問の延長理由自動クリア判定
     * @param int $contractId 契約ID
     * @param string $serviceDate 役務日
     * @param float $serviceHours 役務時間
     * @param string $currentOvertimeReason 現在の延長理由
     * @param int|null $excludeId 除外する記録ID（編集時）
     * @return string|null 延長する場合は元の理由、しない場合はNULL
     */
    private function checkAndClearOvertimeReason($contractId, $serviceDate, $serviceHours, $currentOvertimeReason, $excludeId = null) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 契約の定期訪問時間を取得
        $contractSql = "SELECT regular_visit_hours FROM contracts WHERE id = :contract_id";
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['contract_id' => $contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract || $contract['regular_visit_hours'] <= 0) {
            // 契約が見つからないか定期時間が設定されていない場合は元の理由を保持
            return $currentOvertimeReason;
        }
        
        $regularLimit = $contract['regular_visit_hours'];
        
        // 対象月の既存の定期訪問時間を取得（編集中の記録を除外）
        $year = date('Y', strtotime($serviceDate));
        $month = date('n', strtotime($serviceDate));
        
        $usageSql = "SELECT COALESCE(SUM(service_hours), 0) as current_used 
                    FROM service_records 
                    WHERE contract_id = :contract_id 
                    AND YEAR(service_date) = :year 
                    AND MONTH(service_date) = :month
                    AND service_type = 'regular'";
        
        $params = [
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ];
        
        // 編集時は自分の記録を除外
        if ($excludeId) {
            $usageSql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $db->prepare($usageSql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        $currentUsed = $result['current_used'] ?? 0;
        $newTotal = $currentUsed + $serviceHours;
        
        // 延長判定
        if ($newTotal > $regularLimit) {
            // 延長する場合は元の延長理由を保持
            error_log("Overtime reason kept - exceeds limit: {$newTotal} > {$regularLimit}");
            return $currentOvertimeReason;
        } else {
            // 延長しない場合は理由をクリア
            if (!empty($currentOvertimeReason)) {
                error_log("Overtime reason cleared - within limit: {$newTotal} <= {$regularLimit}");
            }
            return null;
        }
    }

    /**
     * 延長理由の必要性をチェック（バリデーション用）
     * 
     * @param int $contractId 契約ID
     * @param string $serviceDate 役務日
     * @param float $serviceHours 役務時間
     * @param int|null $excludeId 除外する記録ID（編集時）
     * @return array ['requires_reason' => bool, 'message' => string]
     */
    private function checkOvertimeRequirement($contractId, $serviceDate, $serviceHours, $excludeId = null) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 契約情報を取得
        $contractSql = "SELECT c.regular_visit_hours, c.visit_frequency, c.bimonthly_type
                        FROM contracts c
                        WHERE c.id = :contract_id";
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['contract_id' => $contractId]);
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contract || $contract['regular_visit_hours'] === null || $contract['regular_visit_hours'] <= 0) {
            return ['requires_reason' => false, 'message' => ''];
        }
        
        $contractRegularHours = $contract['regular_visit_hours'];
        $visitFrequency = $contract['visit_frequency'] ?? 'monthly';
        
        // 役務日の年月を取得
        $year = date('Y', strtotime($serviceDate));
        $month = date('n', strtotime($serviceDate));
        
        // 月間上限時間を計算（毎週契約の場合は祝日を考慮）
        $monthlyLimit = $contractRegularHours;
        if ($visitFrequency === 'weekly') {
            require_once __DIR__ . '/../core/helpers.php';
            $calculatedLimit = calculate_weekly_monthly_hours_with_contract_settings(
                $contractId,
                $contractRegularHours,
                $year,
                $month,
                $db
            );
            if ($calculatedLimit > 0) {
                $monthlyLimit = $calculatedLimit;
            }
        }
        
        // 累積時間を取得（編集中の記録を除く、今回の役務より前の記録のみ）
        $cumulativeSql = "SELECT COALESCE(SUM(service_hours), 0) as cumulative_hours
                         FROM service_records
                         WHERE contract_id = :contract_id
                         AND YEAR(service_date) = :year
                         AND MONTH(service_date) = :month
                         AND service_type = 'regular'";
        
        $params = [
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ];
        
        // 編集時は自分の記録を除外
        if ($excludeId) {
            $cumulativeSql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        // 今回の役務より前の記録のみ（同じ日の場合は時刻で判定すべきだが、簡易的に同日の記録も含める）
        $cumulativeSql .= " AND service_date <= :service_date";
        $params['service_date'] = $serviceDate;
        
        $stmt = $db->prepare($cumulativeSql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $cumulativeHours = $result['cumulative_hours'] ?? 0;
        $totalWithThisService = $cumulativeHours + $serviceHours;
        
        // 延長判定
        // 1. 1回の役務時間が契約時間を超える場合
        if ($serviceHours > $contractRegularHours) {
            return [
                'requires_reason' => true,
                'message' => '1回の役務時間が契約時間を超過しています。'
            ];
        }
        
        // 2. 累積時間が月間上限を超える場合
        if ($totalWithThisService > $monthlyLimit) {
            $limitDescription = '月間定期訪問時間';
            if ($visitFrequency === 'weekly') {
                $limitDescription = '今月の上限時間（祝日考慮）';
            } elseif ($visitFrequency === 'bimonthly') {
                $limitDescription = '隔月契約の訪問時間';
            }
            
            return [
                'requires_reason' => true,
                'message' => "累積時間が{$limitDescription}を超過しています。"
            ];
        }
        
        return ['requires_reason' => false, 'message' => ''];
    }

    /**
     * バリデーションエラー時に入力値を保持してリダイレクト
     */
    private function redirectBackWithInput() {
        // POSTデータをセッションに保存
        $_SESSION['form_input'] = $_POST;
        redirect('service_records/create');
    }

    /**
     * 編集画面用：バリデーションエラー時に入力値を保持してリダイレクト
     */
    private function redirectBackWithInputForEdit($id) {
        // POSTデータをセッションに保存
        $_SESSION['form_input'] = $_POST;
        redirect("service_records/$id/edit");
    }

    /**
     * 役務記録の削除
     */
    public function delete($id) {
        // リクエストヘッダーの設定
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        try {
            error_log("Delete request received for service record ID: {$id}");
            error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
            error_log("Is Ajax: " . ($this->isAjaxRequest() ? 'yes' : 'no'));
            error_log("POST data: " . print_r($_POST, true));
            
            // ログイン確認
            Session::requireUserType('doctor');
            
            // POSTメソッドの確認
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $error = '無効なリクエストメソッドです';
                error_log($error);
                
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['success' => false, 'error' => $error]);
                } else {
                    $this->setFlash('error', $error);
                    redirect('service_records');
                }
                return;
            }
            
            // CSRFトークンの検証
            if (!$this->validateCsrf()) {
                $error = 'セキュリティトークンが無効です。ページを再読み込みしてください。';
                error_log("CSRF token validation failed");
                
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['success' => false, 'error' => $error]);
                } else {
                    $this->setFlash('error', $error);
                    redirect('service_records');
                }
                return;
            }
            
            // 記録の存在確認
            $serviceModel = new ServiceRecord();
            $record = $serviceModel->findById($id);
            
            if (!$record) {
                $error = '指定された役務記録が見つかりません';
                error_log("Service record not found: {$id}");
                
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['success' => false, 'error' => $error]);
                } else {
                    $this->setFlash('error', $error);
                    redirect('service_records');
                }
                return;
            }
            
            // 権限チェック：自分の記録のみ削除可能
            if ($record['doctor_id'] != Session::get('user_id')) {
                $error = '権限がありません。他の産業医の記録は削除できません。';
                error_log("Access denied. Doctor " . Session::get('user_id') . " tried to delete record of doctor {$record['doctor_id']}");
                
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['success' => false, 'error' => $error]);
                } else {
                    $this->setFlash('error', $error);
                    redirect('service_records');
                }
                return;
            }
            
            // 進行中の記録は削除不可
            if ($record['service_hours'] == 0 && requires_active_tracking($record['service_type'] ?? 'regular')) {
                $error = '進行中の役務記録は削除できません。まず役務を終了してください。';
                error_log("Cannot delete active record. Record ID: {$id}");
                
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['success' => false, 'error' => $error]);
                } else {
                    $this->setFlash('error', $error);
                    redirect('service_records/active');
                }
                return;
            }
            
            // *** 修正箇所：承認済みの記録のみ削除不可に変更 ***
            if ($record['status'] === 'approved') {
                $error = '承認済みの役務記録は削除できません。';
                error_log("Cannot delete approved record. Status: {$record['status']}");
                
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['success' => false, 'error' => $error]);
                } else {
                    $this->setFlash('error', $error);
                    redirect('service_records');
                }
                return;
            }

            // *** 締め処理済みチェック ***
            if ($this->checkServiceRecordClosedStatus($record['contract_id'], $record['service_date'])) {
                $serviceYear = date('Y', strtotime($record['service_date']));
                $serviceMonth = date('n', strtotime($record['service_date']));
                $error = "この役務記録（{$serviceYear}年{$serviceMonth}月）は既に締め処理が完了しているため、削除することはできません。";
                
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['success' => false, 'error' => $error]);
                } else {
                    $this->setFlash('error', $error);
                    redirect('service_records');
                }
                return;
            }
            
            // データベース処理
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // トランザクション開始
            $db->beginTransaction();
            
            try {
                // 関連する交通費記録を削除
                $travelExpenseDeleteSql = "DELETE FROM travel_expenses WHERE service_record_id = :service_record_id";
                $stmt = $db->prepare($travelExpenseDeleteSql);
                $stmt->execute(['service_record_id' => $id]);
                $deletedTravelExpenses = $stmt->rowCount();
                
                error_log("Deleted {$deletedTravelExpenses} travel expense records for service record {$id}");
                
                // 自動分割記録の場合の特別処理
                $isAutoSplit = ($record['is_auto_split'] ?? false) || 
                            strpos($record['description'] ?? '', '自動分割') !== false ||
                            strpos($record['description'] ?? '', '延長分') !== false;
                
                if ($isAutoSplit && $record['service_type'] === 'extension') {
                    // 自動分割で作成された延長記録の場合、関連する定期記録も確認
                    $this->handleAutoSplitDeletion($db, $record);
                }
                
                // 役務記録を削除(履歴付き)
                $deletedBy = Session::get('user_id');
                if (!$serviceModel->deleteWithHistory($id, $deletedBy)) {
                    throw new Exception('役務記録の削除に失敗しました。');
                }
                
                // 月次サマリーを更新
                if (class_exists('MonthlySummary')) {
                    $summaryModel = new MonthlySummary();
                    $serviceDate = $record['service_date'];
                    $year = date('Y', strtotime($serviceDate));
                    $month = date('n', strtotime($serviceDate));
                    
                    $summaryModel->updateSummaryBasic($record['contract_id'], $year, $month);
                }
                
                // セキュリティログに記録
                $this->logSecurityEvent('service_record_deleted', [
                    'record_id' => $id,
                    'contract_id' => $record['contract_id'],
                    'service_date' => $record['service_date'],
                    'service_hours' => $record['service_hours'],
                    'service_type' => $record['service_type'],
                    'status' => $record['status'],
                    'is_auto_split' => $isAutoSplit
                ]);
                
                // トランザクションコミット
                $db->commit();
                
                // 削除理由をステータスに応じて調整
                $statusLabel = '';
                switch ($record['status']) {
                    case 'pending':
                        $statusLabel = '承認待ち';
                        break;
                    case 'rejected':
                        $statusLabel = '差し戻された';
                        break;
                    default:
                        $statusLabel = '';
                }
                
                $successMessage = $statusLabel ? 
                    "{$statusLabel}役務記録が正常に削除されました。" : 
                    "役務記録が正常に削除されました。";
                    
                if ($deletedTravelExpenses > 0) {
                    $successMessage .= "（関連する交通費記録{$deletedTravelExpenses}件も削除されました）";
                }
                
                error_log("Successfully deleted service record {$id} (status: {$record['status']}) and {$deletedTravelExpenses} related travel expenses");
                
                // 成功レスポンス
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse([
                        'success' => true,
                        'message' => $successMessage,
                        'deleted_travel_expenses' => $deletedTravelExpenses,
                        'record_id' => $id,
                        'status' => $record['status']
                    ]);
                } else {
                    $this->setFlash('success', $successMessage);
                    redirect('service_records');
                }
                
            } catch (Exception $e) {
                // トランザクションロールバック
                $db->rollBack();
                error_log("Database error during deletion: " . $e->getMessage());
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Error in delete method: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $errorMessage = 'システムエラーが発生しました: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->sendJsonResponse(['success' => false, 'error' => $errorMessage]);
            } else {
                $this->setFlash('error', $errorMessage);
                redirect('service_records');
            }
        }
    }

    /**
     * 自動分割記録削除時の特別処理
     */
    private function handleAutoSplitDeletion($db, $record) {
        // 同じ日時の関連記録を検索
        $relatedSql = "SELECT id, service_type, is_auto_split, description 
                    FROM service_records 
                    WHERE contract_id = :contract_id 
                    AND service_date = :service_date 
                    AND doctor_id = :doctor_id 
                    AND id != :exclude_id
                    AND (is_auto_split = 1 OR description LIKE '%自動分割%' OR description LIKE '%元の時間:%')
                    ORDER BY created_at";
        
        $stmt = $db->prepare($relatedSql);
        $stmt->execute([
            'contract_id' => $record['contract_id'],
            'service_date' => $record['service_date'],
            'doctor_id' => $record['doctor_id'],
            'exclude_id' => $record['id']
        ]);
        
        $relatedRecords = $stmt->fetchAll();
        
        if (!empty($relatedRecords)) {
            // 関連記録がある場合は警告をログに記録
            error_log("Auto-split record deletion: Related records found for record ID {$record['id']}");
            
            // 必要に応じて関連記録の状態を更新
            // 例：定期記録の説明文から分割情報を削除など
            foreach ($relatedRecords as $related) {
                if ($related['service_type'] === 'regular') {
                    // 定期記録の説明文を更新（分割情報を削除）
                    $newDescription = preg_replace('/\n元の時間:.*$/s', '', $related['description']);
                    if ($newDescription !== $related['description']) {
                        $updateSql = "UPDATE service_records SET description = :description WHERE id = :id";
                        $updateStmt = $db->prepare($updateSql);
                        $updateStmt->execute([
                            'description' => $newDescription,
                            'id' => $related['id']
                        ]);
                    }
                }
            }
        }
    }

    /**
     * 論理削除対応版（物理削除の代わりに使用する場合）
     */
    public function softDelete($id) {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('service_records');
            return;
        }
        
        $serviceModel = new ServiceRecord();
        $record = $serviceModel->findById($id);
        
        if (!$record) {
            $this->setFlash('error', '役務記録が見つかりません。');
            redirect('service_records');
            return;
        }
        
        // 権限チェック
        if ($record['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('service_records');
            return;
        }
        
        // 削除可能かチェック
        if ($record['service_hours'] == 0) {
            $this->setFlash('error', '進行中の役務記録は削除できません。');
            redirect('service_records/active');
            return;
        }
        
        if ($record['status'] === 'approved') {
            $this->setFlash('error', '承認済みの役務記録は削除できません。');
            redirect('service_records');
            return;
        }
        
        // 論理削除（is_deletedフラグを追加する場合）
        $updateData = [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => Session::get('user_id')
        ];
        
        if ($serviceModel->update($id, $updateData)) {
            // 月次サマリーを更新
            $summaryModel = new MonthlySummary();
            $serviceDate = $record['service_date'];
            $year = date('Y', strtotime($serviceDate));
            $month = date('n', strtotime($serviceDate));
            
            $summaryModel->updateSummaryBasic($record['contract_id'], $year, $month);
            
            $this->setFlash('success', '役務記録を削除しました。');
        } else {
            $this->setFlash('error', '役務記録の削除に失敗しました。');
        }
        
        redirect('service_records');
    }

    /**
     * 指定契約・年月が締め処理済みかチェック
     */
    private function isContractClosed($contractId, $year, $month) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) as count 
                FROM monthly_closing_records 
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

    /**
     * 役務記録が締め処理済み月に属するかチェック
     */
    private function checkServiceRecordClosedStatus($contractId, $serviceDate) {
        $year = date('Y', strtotime($serviceDate));
        $month = date('n', strtotime($serviceDate));
        
        return $this->isContractClosed($contractId, $year, $month);
    }


    public function approve($id) {
        // AJAXリクエストかどうかをチェック
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        try {
            Session::requireUserType('company');
            
            if (!$this->validateCsrf()) {
                $error = 'セキュリティトークンが無効です。ページを再読み込みしてください。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            $serviceModel = new ServiceRecord();
            $record = $serviceModel->findById($id);
            
            if (!$record) {
                $error = '役務記録が見つかりません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 企業の権限チェック（拠点制限付き）
            $contractModel = new Contract();
            $contract = $contractModel->findById($record['contract_id']);
            
            if (!$contract || $contract['company_id'] != Session::get('company_id')) {
                $error = '権限がありません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 拠点レベルの権限チェックを追加
            $userId = Session::get('user_id');
            $accessibleBranchIds = $this->getAccessibleBranchIds(Session::get('company_id'), $userId);
            if (!in_array($contract['branch_id'], $accessibleBranchIds)) {
                $error = 'この拠点の記録を承認する権限がありません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 既に承認済みかチェック
            if ($record['status'] === 'approved') {
                $error = 'この記録は既に承認済みです。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            $comment = sanitize($_POST['comment'] ?? '');
            $approvedBy = Session::get('user_id');
            
            // 承認処理（履歴記録付き）
            if ($serviceModel->approveRecordWithHistory($id, $comment, $approvedBy)) {
                // 月次サマリーを更新
                if (class_exists('MonthlySummary')) {
                    $summaryModel = new MonthlySummary();
                    $serviceDate = $record['service_date'];
                    $year = date('Y', strtotime($serviceDate));
                    $month = date('n', strtotime($serviceDate));
                    $summaryModel->updateSummary($record['contract_id'], $year, $month);
                }
                
                // セキュリティログに記録
                $this->logSecurityEvent('service_record_approved', [
                    'record_id' => $id,
                    'contract_id' => $record['contract_id'],
                    'branch_id' => $contract['branch_id'], // 拠点情報を追加
                    'approved_by' => $approvedBy,
                    'comment' => $comment
                ]);
                
                $successMessage = '役務記録を承認しました。';
                
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'message' => $successMessage,
                        'record_id' => $id
                    ]);
                    return;
                } else {
                    $this->setFlash('success', $successMessage);
                    $this->redirectToPreviousPage();
                    return;
                }
            } else {
                $error = '承認処理に失敗しました。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
        } catch (Exception $e) {
            error_log('Approve error: ' . $e->getMessage());
            
            $errorMessage = 'システムエラーが発生しました';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                return;
            } else {
                $this->setFlash('error', $errorMessage);
                $this->redirectToPreviousPage();
                return;
            }
        }
    }

    /**
     * 企業ユーザー用：役務記録の承認取り消し
     */
    public function unapprove($id) {
        // AJAXリクエストかどうかをチェック
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        try {
            Session::requireUserType('company');
            
            if (!$this->validateCsrf()) {
                $error = 'セキュリティトークンが無効です。ページを再読み込みしてください。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            $serviceModel = new ServiceRecord();
            $record = $serviceModel->findById($id);
            
            if (!$record) {
                $error = '役務記録が見つかりません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 企業の権限チェック（拠点制限付き）
            $contractModel = new Contract();
            $contract = $contractModel->findById($record['contract_id']);
            
            if (!$contract || $contract['company_id'] != Session::get('company_id')) {
                $error = '権限がありません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 拠点レベルの権限チェックを追加
            $userId = Session::get('user_id');
            $accessibleBranchIds = $this->getAccessibleBranchIds(Session::get('company_id'), $userId);
            if (!in_array($contract['branch_id'], $accessibleBranchIds)) {
                $error = 'この拠点の記録を操作する権限がありません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 承認取り消し可能かチェック
            if ($record['status'] !== 'approved') {
                $error = '承認済みの記録のみ承認取り消しできます。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 締め済みの記録は承認取り消しできない
            if ($record['is_closed'] == 1) {
                $error = '締め処理済みの記録は承認取り消しできません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            $comment = sanitize($_POST['comment'] ?? '');
            $unapprovedBy = Session::get('user_id');
            
            // 承認取り消し処理（履歴記録付き）
            if ($serviceModel->unapproveRecordWithHistory($id, $comment, $unapprovedBy)) {
                // 月次サマリーを更新
                if (class_exists('MonthlySummary')) {
                    $summaryModel = new MonthlySummary();
                    $serviceDate = $record['service_date'];
                    $year = date('Y', strtotime($serviceDate));
                    $month = date('n', strtotime($serviceDate));
                    $summaryModel->updateSummary($record['contract_id'], $year, $month);
                }
                
                // セキュリティログに記録
                $this->logSecurityEvent('service_record_unapproved', [
                    'record_id' => $id,
                    'contract_id' => $record['contract_id'],
                    'branch_id' => $contract['branch_id'],
                    'unapproved_by' => $unapprovedBy,
                    'comment' => $comment
                ]);
                
                $successMessage = '役務記録の承認を取り消しました。';
                
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'message' => $successMessage,
                        'record_id' => $id
                    ]);
                    return;
                } else {
                    $this->setFlash('success', $successMessage);
                    $this->redirectToPreviousPage();
                    return;
                }
            } else {
                $error = '承認取り消し処理に失敗しました。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
        } catch (Exception $e) {
            error_log('Unapprove error: ' . $e->getMessage());
            
            $errorMessage = 'システムエラーが発生しました';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                return;
            } else {
                $this->setFlash('error', $errorMessage);
                $this->redirectToPreviousPage();
                return;
            }
        }
    }
    
    public function reject($id) {
        // AJAXリクエストかどうかをチェック
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        try {
            Session::requireUserType('company');
            
            if (!$this->validateCsrf()) {
                $error = 'セキュリティトークンが無効です。ページを再読み込みしてください。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            $serviceModel = new ServiceRecord();
            $record = $serviceModel->findById($id);
            
            if (!$record) {
                $error = '役務記録が見つかりません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 企業の権限チェック（拠点制限付き）
            $contractModel = new Contract();
            $contract = $contractModel->findById($record['contract_id']);
            
            if (!$contract || $contract['company_id'] != Session::get('company_id')) {
                $error = '権限がありません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // 拠点レベルの権限チェックを追加
            $userId = Session::get('user_id');
            $accessibleBranchIds = $this->getAccessibleBranchIds(Session::get('company_id'), $userId);
            if (!in_array($contract['branch_id'], $accessibleBranchIds)) {
                $error = 'この拠点の記録を差戻しする権限がありません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // reject_reasonとcommentの両方に対応（互換性のため）
            $comment = sanitize($_POST['reject_reason'] ?? $_POST['comment'] ?? '');
            
            if (empty($comment)) {
                $error = '差戻し理由を入力してください。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            $rejectedBy = Session::get('user_id');
            
            // 差戻し処理（履歴記録付き）
            if ($serviceModel->rejectRecordWithHistory($id, $comment, $rejectedBy)) {
                // 月次サマリーを更新
                if (class_exists('MonthlySummary')) {
                    $summaryModel = new MonthlySummary();
                    $serviceDate = $record['service_date'];
                    $year = date('Y', strtotime($serviceDate));
                    $month = date('n', strtotime($serviceDate));
                    $summaryModel->updateSummary($record['contract_id'], $year, $month);
                }
                
                // セキュリティログに記録
                $this->logSecurityEvent('service_record_rejected', [
                    'record_id' => $id,
                    'contract_id' => $record['contract_id'],
                    'branch_id' => $contract['branch_id'], // 拠点情報を追加
                    'rejected_by' => $rejectedBy,
                    'comment' => $comment
                ]);
                
                $successMessage = '役務記録を差戻しました。';
                
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'message' => $successMessage,
                        'record_id' => $id
                    ]);
                    return;
                } else {
                    $this->setFlash('success', $successMessage);
                    $this->redirectToPreviousPage();
                    return;
                }
            } else {
                $error = '差戻し処理に失敗しました。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
        } catch (Exception $e) {
            error_log('Reject error: ' . $e->getMessage());
            
            $errorMessage = 'システムエラーが発生しました';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                return;
            } else {
                $this->setFlash('error', $errorMessage);
                $this->redirectToPreviousPage();
                return;
            }
        }
    }
    
    public function selectContract() {
        Session::requireUserType('doctor');
        
        $doctorId = Session::get('user_id');
        $contractModel = new Contract();
        
        // 進行中の役務をチェック（時間管理が必要な役務のみ）
        $activeService = $this->getActiveService($doctorId);
        
        if ($activeService) {
            $this->setFlash('error', '現在進行中の役務があります。先に終了してから新しい役務を開始してください。');
            redirect('service_records/active');
            return;
        }
        
        //$contracts = $contractModel->findByDoctor($doctorId);
        // 契約を取得
        $allContracts = $contractModel->findByDoctor($doctorId);
        
        // 現在日付を取得
        $currentDate = date('Y-m-d');
        
        // 反映日～反映終了日が現在に該当する契約のみをフィルタリング
        $contracts = array_filter($allContracts, function($contract) use ($currentDate) {
            // effective_dateが存在しない場合は表示しない
            if (empty($contract['effective_date'])) {
                return false;
            }
            
            // 反映日が現在日付以降の場合は表示しない
            if ($contract['effective_date'] > $currentDate) {
                return false;
            }
            
            // effective_end_dateがNULLの場合は反映日のみでチェック（期限なし）
            if (empty($contract['effective_end_date'])) {
                return true;
            }
            
            // effective_end_dateがある場合は、現在日付が反映終了日以内かチェック
            return $currentDate <= $contract['effective_end_date'];
        });
        
        // 役務種別の情報を取得
        $selectableServiceTypes = get_selectable_service_types();
        
        ob_start();
        include __DIR__ . '/../views/service_records/select_contract.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    
    public function startService() {
        Session::requireUserType('doctor');

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('service_records/select-contract');
            return;
        }
        
        $contractId = (int)($_POST['contract_id'] ?? 0);
        $serviceType = $_POST['service_type'] ?? 'regular';
        $visitType = $_POST['visit_type'] ?? 'visit'; // 訪問種別を追加
        $doctorId = Session::get('user_id');

        // バリデーション
        if (!in_array($serviceType, ['regular', 'emergency', 'extension', 'document', 'remote_consultation', 'spot'])) {
            $this->setFlash('error', '役務種別を正しく選択してください。');
            redirect('service_records/select-contract');
            return;
        }
        
        // 訪問種別のバリデーション
        if (requires_visit_type_selection($serviceType)) {
            if (!in_array($visitType, ['visit', 'online'])) {
                $this->setFlash('error', '訪問種別を正しく選択してください。');
                redirect('service_records/select-contract');
                return;
            }
        } else {
            $visitType = null; // 書面作成・遠隔相談は訪問種別不要
        }
        
        // 契約の有効性チェック
        $contractModel = new Contract();
        if (!$contractModel->isValidContract($contractId, $doctorId)) {
            $this->setFlash('error', '選択された契約は無効です。');
            redirect('service_records/select-contract');
            return;
        }
        
        // 書面作成・遠隔相談の場合は進行中管理しない（直接作成画面へ）
        if (!requires_active_tracking($serviceType)) {
            // 直接作成画面へリダイレクト（パラメータ付き）
            $this->setFlash('info', get_service_type_label($serviceType) . 'の記録を作成してください。');
            redirect("service_records/create?contract_id={$contractId}&service_type={$serviceType}");
            return;
        }
        
        // 進行中の役務がないかチェック（時間管理が必要な役務のみ）
        $activeService = $this->getActiveService($doctorId);
        
        if ($activeService) {
            $this->setFlash('error', '既に進行中の役務があります。先に終了してください。');
            redirect('service_records/active');
            return;
        }
        
        // 役務開始記録を作成（進行中の場合はend_timeに開始時刻と同じ値を設定）
        $serviceModel = new ServiceRecord();

        $currentTime = new DateTime();
        $currentTime->setTime($currentTime->format('H'), $currentTime->format('i'), 0); // 秒を00に設定
        $startTime = $currentTime->format('H:i:s');

        $data = [
            'contract_id' => $contractId,
            'doctor_id' => $doctorId,
            'service_date' => date('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $startTime, // 進行中を示すため開始時刻と同じ値を設定
            'service_hours' => 0,
            'service_type' => $serviceType,
            'visit_type' => $visitType, // 訪問種別を設定
            'status' => 'pending',
            'description' => ''
        ];
        
        $serviceId = $serviceModel->create($data);
        
        if ($serviceId) {
            $serviceTypeLabel = get_service_type_label($serviceType);
            $visitTypeLabel = get_visit_type_label($visitType);
            $this->setFlash('success', "{$serviceTypeLabel}({$visitTypeLabel})を開始しました。");
            redirect("service_records/active");
        } else {
            $this->setFlash('error', '役務開始の記録に失敗しました。');
            redirect('service_records/select-contract');
        }
    }


    public function activeService() {
        Session::requireUserType('doctor');
        
        $doctorId = Session::get('user_id');
        $activeService = $this->getActiveService($doctorId);
        
        if (!$activeService) {
            $this->setFlash('error', '進行中の役務がありません。');
            redirect('dashboard');
            return;
        }
        
        // 拠点情報を含む契約詳細を取得
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $contractSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                    FROM contracts co
                    JOIN branches b ON co.branch_id = b.id
                    JOIN companies c ON co.company_id = c.id
                    WHERE co.id = :contract_id";
        
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['contract_id' => $activeService['contract_id']]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            $this->setFlash('error', '契約情報が見つかりません。');
            redirect('dashboard');
            return;
        }
        
        // ServiceRecordモデルを追加でビューに渡す
        $serviceModel = new ServiceRecord();
        
        // 変数を展開
        extract([
            'activeService' => $activeService,
            'contract' => $contract,
            'serviceModel' => $serviceModel
        ]);
        
        ob_start();
        include __DIR__ . '/../views/service_records/active.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function endService() {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('service_records/active');
        }
        
        $serviceModel = new ServiceRecord();
        $activeService = $serviceModel->getActiveService(Session::get('user_id'));
        
        if (!$activeService) {
            $this->setFlash('error', '進行中の役務が見つかりません。');
            redirect('dashboard');
        }
        
        // フォームデータを取得
        $description = trim($_POST['description'] ?? '');
        $overtimeReason = trim($_POST['overtime_reason'] ?? '');
        $draftSave = isset($_POST['draft_save']);
        
        // 役務種別を取得（未設定の場合は定期訪問）
        $serviceType = $activeService['service_type'] ?? 'regular';
        
        // スポット役務の場合は役務内容が必須
        if ($serviceType === 'spot' && empty($description) && !$draftSave) {
            $this->setFlash('error', 'スポット役務では役務内容の入力が必須です。');
            redirect('service_records/active');
            return;
        }
        
        // 現在時刻を取得
        $currentTime = new DateTime();
        $currentTime->setTime($currentTime->format('H'), $currentTime->format('i'), 0); // 秒を00に設定
        $endTime = $currentTime->format('H:i:s');

        $startTime = $activeService['start_time'];
        
        // 役務時間を計算
        $totalHours = calculate_service_hours($startTime, $endTime, $serviceType);
        
        if ($totalHours <= 0) {
            error_log('Invalid service hours calculated');
            $this->setFlash('error', '役務時間の計算でエラーが発生しました。');
            redirect('service_records/active');
        }
        
        // 契約情報を取得
        $contractModel = new Contract();
        $contract = $contractModel->findById($activeService['contract_id']);
        
        if (!$contract) {
            $this->setFlash('error', '契約情報が見つかりません。');
            redirect('service_records/active');
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // トランザクション開始
        $db->beginTransaction();
        
        try {
            // 下書き保存の場合
            if ($draftSave) {
                $updateData = [
                    'description' => $description,
                    'overtime_reason' => $overtimeReason,
                    'draft_saved_at' => date('Y-m-d H:i:s')
                ];
                
                if (!$serviceModel->update($activeService['id'], $updateData)) {
                    throw new Exception('下書き保存に失敗しました。');
                }
                
                // トランザクションコミット
                $db->commit();
                
                $this->setFlash('success', '下書きを保存しました。役務を継続できます。');
                redirect('service_records/active');
                return;
            }
            
            // 【修正】すべて通常終了処理（分割処理を削除）
            $updateData = [
                'end_time' => $endTime,
                'service_hours' => $totalHours,
                'description' => $description,
                'overtime_reason' => $overtimeReason,
                'status' => 'pending',
                'is_auto_split' => false
            ];
            
            if (!$serviceModel->update($activeService['id'], $updateData)) {
                throw new Exception('役務記録の更新に失敗しました。');
            }
            
            $serviceTypeLabel = get_service_type_label($serviceType);
            if (requires_visit_type_selection($serviceType)) {
                $visitTypeLabel = get_visit_type_label($activeService['visit_type']);
                $message = "{$serviceTypeLabel}（{$visitTypeLabel}）を終了しました。";
            } else {
                $message = "{$serviceTypeLabel}を終了しました。";
            }
            
            // 月次サマリーを更新
            if (class_exists('MonthlySummary')) {
                $summaryModel = new MonthlySummary();
                $serviceDate = $activeService['service_date'];
                $year = date('Y', strtotime($serviceDate));
                $month = date('n', strtotime($serviceDate));
                
                $summaryModel->updateSummaryBasic($activeService['contract_id'], $year, $month);
            }
            
            // 進行中の役務セッション情報をクリア
            Session::remove('active_service_id');
            Session::remove('service_start_time');
            
            // トランザクションコミット
            $db->commit();
            
            error_log("EndService completed successfully");
            $this->setFlash('success', $message);
            
        } catch (Exception $e) {
            // トランザクションロールバック
            $db->rollback();
            error_log('EndService failed: ' . $e->getMessage());
            $this->setFlash('error', '役務終了中にエラーが発生しました: ' . $e->getMessage());
        }
        
        redirect('dashboard');
    }
    
    private function getActiveService($doctorId) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 時間管理が必要な役務種別のみを対象とする
        $sql = "SELECT * FROM service_records 
                WHERE doctor_id = :doctor_id 
                AND service_hours = 0 
                AND start_time = end_time
                AND service_date = CURDATE()
                AND service_type IN ('regular', 'emergency', 'extension', 'spot')
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(['doctor_id' => $doctorId]);
        
        return $stmt->fetch();
    }

    public function getServiceRecordDetail($id) {
        // Set proper headers for API response
        header('Content-Type: application/json; charset=utf-8');
        
        // 管理者、企業ユーザー、産業医の権限をチェック
        if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'company', 'doctor'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        try {
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT sr.*, 
                        u.name as doctor_name,
                        c.name as company_name,
                        b.name as branch_name,
                        co.regular_visit_hours
                    FROM service_records sr
                    LEFT JOIN users u ON sr.doctor_id = u.id
                    LEFT JOIN contracts co ON sr.contract_id = co.id
                    LEFT JOIN companies c ON co.company_id = c.id
                    LEFT JOIN branches b ON co.branch_id = b.id
                    WHERE sr.id = :id";
            
            $params = ['id' => (int)$id];
            
            // 企業ユーザーの場合は自社かつアクセス可能な拠点の記録のみ
            if ($_SESSION['user_type'] === 'company') {
                $sql .= " AND co.company_id = :company_id";
                $params['company_id'] = $_SESSION['company_id'];
                
                // 拠点制限を追加（修正版のaddBranchAccessConditionを使用）
                $userId = $_SESSION['user_id'];
                $accessibleBranchIds = $this->getAccessibleBranchIds($_SESSION['company_id'], $userId);
                
                if (empty($accessibleBranchIds)) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'アクセス可能な拠点がありません'
                    ]);
                    return;
                }
                
                // 名前付きパラメータで拠点制限を追加
                $placeholders = [];
                foreach ($accessibleBranchIds as $index => $branchId) {
                    $paramName = "branch_id_{$index}";
                    $placeholders[] = ":{$paramName}";
                    $params[$paramName] = $branchId;
                }
                $placeholderString = implode(',', $placeholders);
                $sql .= " AND co.branch_id IN ({$placeholderString})";
            }
            // 産業医の場合は自分の記録のみアクセス可能
            elseif ($_SESSION['user_type'] === 'doctor') {
                $sql .= " AND sr.doctor_id = :doctor_id";
                $params['doctor_id'] = $_SESSION['user_id'];
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $record = $stmt->fetch();
            
            if (!$record) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => '役務記録が見つかりません'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $record
            ]);
            
        } catch (Exception $e) {
            error_log('API Error in getServiceRecordDetail: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => '詳細情報の取得に失敗しました',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 一括承認処理
     */
    public function bulkApprove() {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        try {
            Session::requireUserType('company');
            
            if (!$this->validateCsrf()) {
                $error = 'セキュリティエラーが発生しました。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            $recordIds = $_POST['record_ids'] ?? [];
            $comment = sanitize($_POST['comment'] ?? '');
            $companyId = Session::get('company_id');
            $userId = Session::get('user_id');
            $approvedBy = $userId;
            
            if (empty($recordIds) || !is_array($recordIds)) {
                $error = '承認する記録が選択されていません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            // アクセス可能な拠点IDを事前に取得
            $accessibleBranchIds = $this->getAccessibleBranchIds($companyId, $userId);
            if (empty($accessibleBranchIds)) {
                $error = 'アクセス可能な拠点がありません。';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    return;
                } else {
                    $this->setFlash('error', $error);
                    $this->redirectToPreviousPage();
                    return;
                }
            }
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // トランザクション開始
            $db->beginTransaction();
            
            try {
                $serviceModel = new ServiceRecord();
                $contractModel = new Contract();
                $successCount = 0;
                $errorRecords = [];
                
                foreach ($recordIds as $recordId) {
                    $recordId = (int)$recordId;
                    
                    // 記録を取得
                    $record = $serviceModel->findById($recordId);
                    if (!$record) {
                        $errorRecords[] = "記録ID {$recordId}: 記録が見つかりません";
                        continue;
                    }
                    
                    // 企業の権限チェック
                    $contract = $contractModel->findById($record['contract_id']);
                    
                    if (!$contract || $contract['company_id'] != $companyId) {
                        $errorRecords[] = "記録ID {$recordId}: 権限がありません";
                        continue;
                    }
                    
                    // 拠点レベルの権限チェック
                    if (!in_array($contract['branch_id'], $accessibleBranchIds)) {
                        $errorRecords[] = "記録ID {$recordId}: この拠点へのアクセス権限がありません";
                        continue;
                    }
                    
                    // 承認可能な状態かチェック
                    if ($record['status'] !== 'pending') {
                        $errorRecords[] = "記録ID {$recordId}: 承認待ち以外の記録は承認できません";
                        continue;
                    }
                    
                    // 承認処理
                    $updateData = [
                        'status' => 'approved',
                        'company_comment' => $comment,
                        'approved_at' => date('Y-m-d H:i:s'),
                        'approved_by' => $approvedBy,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($serviceModel->update($recordId, $updateData)) {
                        $successCount++;
                        
                        // 月次サマリーを更新
                        if (class_exists('MonthlySummary')) {
                            $summaryModel = new MonthlySummary();
                            $serviceDate = $record['service_date'];
                            $year = date('Y', strtotime($serviceDate));
                            $month = date('n', strtotime($serviceDate));
                            $summaryModel->updateSummary($record['contract_id'], $year, $month);
                        }
                    } else {
                        $errorRecords[] = "記録ID {$recordId}: 承認処理に失敗しました";
                    }
                }
                
                // トランザクションコミット
                $db->commit();
                
                // セキュリティログ
                $this->logSecurityEvent('bulk_approve_service_records', [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'accessible_branch_ids' => $accessibleBranchIds, // アクセス可能拠点情報
                    'record_ids' => $recordIds,
                    'success_count' => $successCount,
                    'error_count' => count($errorRecords),
                    'comment' => $comment
                ]);
                
                // 結果メッセージ
                if ($successCount > 0) {
                    $message = "{$successCount}件の役務記録を一括承認しました。";
                    if (!empty($errorRecords)) {
                        $message .= " ただし、" . count($errorRecords) . "件でエラーが発生しました。";
                    }
                    
                    if ($isAjax) {
                        echo json_encode([
                            'success' => true,
                            'message' => $message,
                            'approved_count' => $successCount,
                            'error_count' => count($errorRecords),
                            'errors' => $errorRecords
                        ]);
                        return;
                    } else {
                        $this->setFlash('success', $message);
                    }
                } else {
                    $message = '承認できる記録がありませんでした。';
                    if ($isAjax) {
                        echo json_encode([
                            'success' => false,
                            'message' => $message,
                            'errors' => $errorRecords
                        ]);
                        return;
                    } else {
                        $this->setFlash('error', $message);
                    }
                }
                
            } catch (Exception $e) {
                // トランザクションロールバック
                $db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('Bulk approve failed: ' . $e->getMessage());
            
            $errorMessage = 'システムエラーが発生しました: ' . $e->getMessage();
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMessage]);
                return;
            } else {
                $this->setFlash('error', $errorMessage);
            }
        }
        
        if (!$isAjax) {
            $this->redirectToPreviousPage();
        }
    }

    private function redirectToPreviousPage() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        if (!empty($referer)) {
            $parsedReferer = parse_url($referer);
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
            
            if (isset($parsedReferer['host']) && $parsedReferer['host'] === $currentHost) {
                $refererPath = $parsedReferer['path'] ?? '';
                $refererQuery = $parsedReferer['query'] ?? '';
                
                if (strpos($refererPath, '/service_records') !== false) {
                    $redirectUrl = $refererPath;
                    if (!empty($refererQuery)) {
                        $redirectUrl .= '?' . $refererQuery;
                    }
                    redirect($redirectUrl);
                    return;
                } elseif (strpos($refererPath, '/dashboard') !== false) {
                    redirect('dashboard');
                    return;
                }
            }
        }
        
        redirect('dashboard');
    }

    /**
     * 管理者用：産業医×拠点ごとの月次サマリー（統計カード削除版）
     */
    public function adminIndex() {
        Session::requireUserType('admin');
        
        // 検索パラメータ
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        $search = trim($_GET['search'] ?? '');
        $approval_status = trim($_GET['approval_status'] ?? '');
        
        // 並び替えパラメータを追加
        $sortColumn = trim($_GET['sort'] ?? 'doctor_name');
        $sortOrder = trim($_GET['order'] ?? 'asc');
        
        // 許可される並び替え列を検証
        $allowedSorts = ['doctor_name', 'company_name', 'visit_frequency', 'total_hours', 'usage_percentage'];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'doctor_name';
        }
        
        // 並び替え順序の検証
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }
        
        // ページネーションパラメータを取得
        $pagination = $this->getPaginationParams();
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 対象年月の最初の日と最後の日を計算
        $targetDate = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = date('t', strtotime($targetDate));
        $targetMonthEnd = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);
        $closingPeriod = sprintf('%04d-%02d', $year, $month);
        
        // 総件数取得用のSQL
        $countSql = "SELECT COUNT(DISTINCT co.id) as total
                    FROM contracts co
                    JOIN users u ON co.doctor_id = u.id
                    JOIN companies c ON co.company_id = c.id
                    JOIN branches b ON co.branch_id = b.id";
        
        // 承認状況フィルターがある場合はservice_recordsを結合
        if (!empty($approval_status)) {
            $countSql .= " JOIN service_records sr ON sr.contract_id = co.id
                          AND YEAR(sr.service_date) = :year 
                          AND MONTH(sr.service_date) = :month";
        }
        
        $countSql .= " WHERE co.contract_status = 'active'
                    AND co.effective_date <= :target_month_end
                    AND (co.effective_end_date IS NULL OR co.effective_end_date >= :target_date)";
        
        $countParams = [
            'target_date' => $targetDate, 
            'target_month_end' => $targetMonthEnd
        ];
        
        // 承認状況フィルター
        if (!empty($approval_status)) {
            $countParams['year'] = $year;
            $countParams['month'] = $month;
            $countSql .= " AND sr.status = :approval_status";
            $countParams['approval_status'] = $approval_status;
        }
        
        // 検索条件
        if (!empty($search)) {
            $countSql .= " AND (u.name LIKE :search_doctor OR c.name LIKE :search_company OR b.name LIKE :search_branch)";
            $countParams['search_doctor'] = "%{$search}%";
            $countParams['search_company'] = "%{$search}%";
            $countParams['search_branch'] = "%{$search}%";
        }
        
        $stmt = $db->prepare($countSql);
        $stmt->execute($countParams);
        $totalRecords = $stmt->fetch()['total'];
        
        // ページネーション情報を計算
        $paginationInfo = $this->calculatePagination($totalRecords, $pagination['page'], $pagination['per_page']);
        
        // 🔧 修正: COUNT(CASE WHEN ...)をCOUNT(sr.id)に変更してnull行をカウントしないようにする
        $summariesSql = "SELECT 
                            co.id as contract_id,
                            co.regular_visit_hours,
                            co.visit_frequency,
                            co.bimonthly_type,
                            co.start_date,
                            co.effective_date,
                            co.effective_end_date,
                            u.id as doctor_id,
                            u.name as doctor_name,
                            c.name as company_name,
                            b.name as branch_name,
                            
                            -- 締め処理状況を追加
                            mcr.status as closing_status,
                            mcr.finalized_at as closing_finalized_at,
                            mcr.finalized_by as closing_finalized_by,
                            
                            -- 時間業務の集計
                            COALESCE(SUM(CASE WHEN sr.service_type IN ('regular', 'emergency', 'extension', 'spot') OR sr.service_type IS NULL THEN sr.service_hours ELSE 0 END), 0) as total_hours,
                            COALESCE(SUM(CASE WHEN sr.status = 'approved' AND (sr.service_type IN ('regular', 'emergency', 'extension', 'spot') OR sr.service_type IS NULL) THEN sr.service_hours ELSE 0 END), 0) as approved_hours,
                            COALESCE(SUM(CASE WHEN sr.status = 'pending' AND (sr.service_type IN ('regular', 'emergency', 'extension', 'spot') OR sr.service_type IS NULL) THEN sr.service_hours ELSE 0 END), 0) as pending_hours,
                            COALESCE(SUM(CASE WHEN sr.status = 'rejected' AND (sr.service_type IN ('regular', 'emergency', 'extension', 'spot') OR sr.service_type IS NULL) THEN sr.service_hours ELSE 0 END), 0) as rejected_hours,
                            
                            -- 🔧 修正: 役務種別ごとの件数(COUNT(sr.id)を使用してnull行を除外)
                            COUNT(CASE WHEN sr.id IS NOT NULL AND (sr.service_type = 'regular' OR sr.service_type IS NULL) THEN 1 END) as regular_count,
                            COUNT(CASE WHEN sr.id IS NOT NULL AND sr.service_type = 'emergency' THEN 1 END) as emergency_count,
                            COUNT(CASE WHEN sr.id IS NOT NULL AND sr.service_type = 'extension' THEN 1 END) as extension_count,
                            COUNT(CASE WHEN sr.id IS NOT NULL AND sr.service_type = 'spot' THEN 1 END) as spot_count,
                            COUNT(CASE WHEN sr.id IS NOT NULL AND sr.service_type = 'document' THEN 1 END) as document_count,
                            COUNT(CASE WHEN sr.id IS NOT NULL AND sr.service_type = 'remote_consultation' THEN 1 END) as remote_consultation_count,
                            COUNT(CASE WHEN sr.id IS NOT NULL AND sr.service_type = 'other' THEN 1 END) as other_count,
                    
                            -- 役務種別ごとの時間(時間記録業務のみ)
                            COALESCE(SUM(CASE WHEN sr.service_type = 'regular' OR sr.service_type IS NULL THEN sr.service_hours ELSE 0 END), 0) as regular_hours,
                            COALESCE(SUM(CASE WHEN sr.service_type = 'emergency' THEN sr.service_hours ELSE 0 END), 0) as emergency_hours,
                            COALESCE(SUM(CASE WHEN sr.service_type = 'extension' THEN sr.service_hours ELSE 0 END), 0) as extension_hours,
                            COALESCE(SUM(CASE WHEN sr.service_type = 'spot' THEN sr.service_hours ELSE 0 END), 0) as spot_hours,

                            -- 全体の記録件数(sr.idがnullでない場合のみカウント)
                            COUNT(sr.id) as record_count
                            
                        FROM contracts co
                        JOIN users u ON co.doctor_id = u.id
                        JOIN companies c ON co.company_id = c.id
                        JOIN branches b ON co.branch_id = b.id";
        
        $summaryParams = [
            'year' => $year, 
            'month' => $month, 
            'target_date' => $targetDate, 
            'target_month_end' => $targetMonthEnd,
            'closing_period' => $closingPeriod
        ];
        
        // 承認状況フィルターがある場合はINNER JOIN、ない場合はLEFT JOIN
        if (!empty($approval_status)) {
            $summariesSql .= "
                        JOIN service_records sr ON co.id = sr.contract_id 
                            AND YEAR(sr.service_date) = :year 
                            AND MONTH(sr.service_date) = :month
                            AND sr.status = :approval_status";
            $summaryParams['approval_status'] = $approval_status;
        } else {
            $summariesSql .= "
                        LEFT JOIN service_records sr ON co.id = sr.contract_id 
                            AND YEAR(sr.service_date) = :year 
                            AND MONTH(sr.service_date) = :month";
        }
        
        $summariesSql .= "
                        -- 月次締め処理記録をLEFT JOIN
                        LEFT JOIN monthly_closing_records mcr ON co.id = mcr.contract_id 
                            AND mcr.closing_period = :closing_period";
        
        // 契約の有効性条件を追加(開始前・終了後の契約を除外)
        $summariesSql .= " WHERE co.contract_status = 'active'
                        AND co.effective_date <= :target_month_end
                        AND (co.effective_end_date IS NULL OR co.effective_end_date >= :target_date)";
        
        // 検索条件
        if (!empty($search)) {
            $summariesSql .= " AND (u.name LIKE :search_doctor OR c.name LIKE :search_company OR b.name LIKE :search_branch)";
            $summaryParams['search_doctor'] = "%{$search}%";
            $summaryParams['search_company'] = "%{$search}%";
            $summaryParams['search_branch'] = "%{$search}%";
        }
        
        $summariesSql .= " GROUP BY 
                                co.id, 
                                u.id, 
                                u.name, 
                                c.name, 
                                b.name, 
                                co.regular_visit_hours,
                                co.visit_frequency,
                                co.bimonthly_type,
                                co.start_date,
                                co.effective_date,
                                co.effective_end_date,
                                mcr.status,
                                mcr.finalized_at,
                                mcr.finalized_by";
        
        // 使用率以外の列でソート(使用率は後でPHPでソート)
        if ($sortColumn === 'usage_percentage') {
            // 使用率の場合は一旦産業医名でソート(後でPHPで再ソート)
            $summariesSql .= " ORDER BY u.name ASC";
        } else {
            // その他の列は通常通りソート
            switch ($sortColumn) {
                case 'company_name':
                    $summariesSql .= " ORDER BY c.name {$sortOrder}, b.name {$sortOrder}, u.name ASC";
                    break;
                case 'visit_frequency':
                    // 訪問頻度順: monthly < bimonthly < weekly
                    $summariesSql .= " ORDER BY 
                        CASE co.visit_frequency 
                            WHEN 'monthly' THEN 1 
                            WHEN 'bimonthly' THEN 2 
                            WHEN 'weekly' THEN 3 
                            ELSE 0 
                        END {$sortOrder}, 
                        u.name ASC";
                    break;
                case 'total_hours':
                    $summariesSql .= " ORDER BY total_hours {$sortOrder}, u.name ASC";
                    break;
                default: // doctor_name
                    $summariesSql .= " ORDER BY u.name {$sortOrder}, c.name ASC, b.name ASC";
            }
        }
        
        // ページネーション追加
        $summariesSql .= " LIMIT :limit OFFSET :offset";
        
        $summaryParams['limit'] = $pagination['per_page'];
        $summaryParams['offset'] = $pagination['offset'];
        
        $stmt = $db->prepare($summariesSql);
        
        // 整数パラメータをバインド
        $stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        
        // その他のパラメータをバインド
        foreach ($summaryParams as $key => $value) {
            if ($key !== 'limit' && $key !== 'offset') {
                $stmt->bindValue(":$key", $value);
            }
        }
        
        $stmt->execute();
        $summaries = $stmt->fetchAll();
        
        // 使用率と残り時間を計算
        foreach ($summaries as &$summary) {
            // 修正: regular_hours だけでなく、時間業務全体(定期+臨時+延長)を使用
            $timeBasedHours = ($summary['regular_hours'] ?? 0) + 
                            ($summary['emergency_hours'] ?? 0) + 
                            ($summary['extension_hours'] ?? 0);
            
            $contractHours = $summary['regular_visit_hours'] ?? 0;
            $visitFrequency = $summary['visit_frequency'] ?? 'monthly';
            
            // 毎週契約の場合は月間時間を再計算
            if ($visitFrequency === 'weekly') {
                $monthlyHours = calculate_weekly_monthly_hours_with_contract_settings(
                    $summary['contract_id'], 
                    $contractHours, 
                    $year, 
                    $month, 
                    $db
                );
                $summary['monthly_regular_hours'] = $monthlyHours;
                $summary['weekly_base_hours'] = $contractHours;
                
                // 使用率と残り時間を月間時間ベースで計算
                if ($monthlyHours > 0) {
                    $summary['usage_percentage'] = ($timeBasedHours / $monthlyHours) * 100;
                    $summary['remaining_hours'] = $monthlyHours - $timeBasedHours;
                } else {
                    $summary['usage_percentage'] = 0;
                    $summary['remaining_hours'] = 0;
                }
                
                // 表示用の契約時間を月間時間に置き換え
                $summary['display_contract_hours'] = $monthlyHours;
                
            } else {
                // 毎月・隔月契約の従来処理(変更なし)
                $summary['monthly_regular_hours'] = $contractHours;
                $summary['display_contract_hours'] = $contractHours;
                
                if ($contractHours > 0) {
                    $summary['usage_percentage'] = ($timeBasedHours / $contractHours) * 100;
                    $summary['remaining_hours'] = $contractHours - $timeBasedHours;
                } else {
                    $summary['usage_percentage'] = 0;
                    $summary['remaining_hours'] = 0;
                }
            }
        }
        unset($summary);

        // 使用率でソートする場合は、PHP側で再ソート
        if ($sortColumn === 'usage_percentage') {
            usort($summaries, function($a, $b) use ($sortOrder) {
                $comparison = $a['usage_percentage'] <=> $b['usage_percentage'];
                return $sortOrder === 'desc' ? -$comparison : $comparison;
            });
        }
        
        // ビューに渡すデータを明示的に定義
        $data = [
            'summaries' => $summaries,
            'year' => $year,
            'month' => $month,
            'search' => $search,
            'approval_status' => $approval_status,
            'sortColumn' => $sortColumn,
            'sortOrder' => $sortOrder,
            'pagination' => $paginationInfo
        ];
        
        // 変数を展開してからビューを読み込み
        extract($data);

        ob_start();
        include __DIR__ . '/../views/service_records/admin_index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    /**
     * 管理者用：特定契約の詳細役務記録一覧
     */
    public function adminDetail($contractId) {
        Session::requireUserType('admin');
        
        // 検索パラメータ
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        $status = trim($_GET['status'] ?? '');
        $serviceType = trim($_GET['service_type'] ?? '');
        $visitType = trim($_GET['visit_type'] ?? '');
        
        // *** 並び替えパラメータを追加 ***
        $sortColumn = trim($_GET['sort'] ?? 'service_date');
        $sortOrder = trim($_GET['order'] ?? 'desc');
        
        // 許可される並び替え列を検証
        $allowedSorts = ['service_date', 'service_type', 'visit_type', 'service_hours', 'status'];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'service_date';
        }
        
        // 並び替え順序の検証
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // ページネーションパラメータを取得
        $pagination = $this->getPaginationParams();
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 契約情報を取得（regular_visit_hoursに修正）
        $contractSql = "SELECT co.*, 
                            u.name as doctor_name,
                            c.name as company_name,
                            b.name as branch_name
                        FROM contracts co
                        JOIN users u ON co.doctor_id = u.id
                        JOIN companies c ON co.company_id = c.id
                        JOIN branches b ON co.branch_id = b.id
                        WHERE co.id = :contract_id";
        
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['contract_id' => $contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            $this->setFlash('error', '契約が見つかりません。');
            redirect('service_records/admin');
        }
        
        // 総件数取得用のSQL
        $countSql = "SELECT COUNT(*) as total
                    FROM service_records sr
                    JOIN contracts co ON sr.contract_id = co.id
                    WHERE sr.contract_id = :contract_id
                    AND YEAR(sr.service_date) = :year 
                    AND MONTH(sr.service_date) = :month";
        
        $params = [
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ];
        
        // ステータスフィルター
        if (!empty($status)) {
            $countSql .= " AND sr.status = :status";
            $params['status'] = $status;
        }
        
        // 役務種別フィルター（追加）
        if (!empty($serviceType)) {
            $countSql .= " AND sr.service_type = :service_type";
            $params['service_type'] = $serviceType;
        }

        // 実施方法フィルター
        if (!empty($visitType)) {
            $countSql .= " AND sr.visit_type = :visit_type";
            $params['visit_type'] = $visitType;
        }
        
        // 総件数を取得
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $totalRecords = $stmt->fetch()['total'];
        
        // ページネーション情報を計算
        $paginationInfo = $this->calculatePagination($totalRecords, $pagination['page'], $pagination['per_page']);
        
        // 役務記録を取得（service_typeカラムを追加・ページネーション付き）
        $recordsSql = "SELECT sr.*, 
                            u.name as doctor_name,
                            c.name as company_name,
                            b.name as branch_name
                        FROM service_records sr
                        JOIN contracts co ON sr.contract_id = co.id
                        JOIN users u ON sr.doctor_id = u.id
                        JOIN companies c ON co.company_id = c.id
                        JOIN branches b ON co.branch_id = b.id
                        WHERE sr.contract_id = :contract_id
                        AND YEAR(sr.service_date) = :year 
                        AND MONTH(sr.service_date) = :month";
        
        // 同じフィルター条件を適用
        if (!empty($status)) {
            $recordsSql .= " AND sr.status = :status";
        }
        
        if (!empty($serviceType)) {
            $recordsSql .= " AND sr.service_type = :service_type";
        }
        
        if (!empty($visitType)) {
            $recordsSql .= " AND sr.visit_type = :visit_type";
        }
        
        // *** 並び替え条件を追加 ***
        $recordsSql .= " ORDER BY ";
        switch ($sortColumn) {
            case 'service_type':
                $recordsSql .= "sr.service_type {$sortOrder}, sr.service_date DESC, sr.created_at DESC";
                break;
            case 'visit_type':
                $recordsSql .= "sr.visit_type {$sortOrder}, sr.service_date DESC, sr.created_at DESC";
                break;
            case 'service_hours':
                $recordsSql .= "sr.service_hours {$sortOrder}, sr.service_date DESC, sr.created_at DESC";
                break;
            case 'status':
                $recordsSql .= "sr.status {$sortOrder}, sr.service_date DESC, sr.created_at DESC";
                break;
            case 'service_date':
            default:
                $recordsSql .= "sr.service_date {$sortOrder}, sr.created_at {$sortOrder}";
                break;
        }

        $recordsSql .= " LIMIT :limit OFFSET :offset";
        
        // LIMITとOFFSETのパラメータを追加
        $params['limit'] = $pagination['per_page'];
        $params['offset'] = $pagination['offset'];
        
        $stmt = $db->prepare($recordsSql);
        
        // 整数パラメータをバインド
        $stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        
        // その他のパラメータをバインド
        foreach ($params as $key => $value) {
            if ($key !== 'limit' && $key !== 'offset') {
                $stmt->bindValue(":$key", $value);
            }
        }
        
        $stmt->execute();
        $records = $stmt->fetchAll();
        
        // 統計情報
        $stats = [
            'total_records' => $totalRecords,
            'approved_hours' => array_sum(array_filter(array_map(function($r) {
                return $r['status'] === 'approved' ? $r['service_hours'] : 0;
            }, $records))),
            'pending_hours' => array_sum(array_filter(array_map(function($r) {
                return $r['status'] === 'pending' ? $r['service_hours'] : 0;
            }, $records))),
            'rejected_hours' => array_sum(array_filter(array_map(function($r) {
                return $r['status'] === 'rejected' ? $r['service_hours'] : 0;
            }, $records)))
        ];
        
        $data = [
            'contract' => $contract,
            'records' => $records,
            'stats' => $stats,
            'year' => $year,
            'month' => $month,
            'status' => $status,
            'serviceType' => $serviceType,
            'visitType' => $visitType,
            'sortColumn' => $sortColumn,
            'sortOrder' => $sortOrder,
            'pagination' => $paginationInfo
        ];
        
        // 変数を展開してからビューを読み込み
        extract($data);

        ob_start();
        include __DIR__ . '/../views/service_records/admin_detail.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    /**
     * 管理者用: 締め処理詳細表示（Ajax用）
     */
    public function getClosingDetail($contractId = null) {
        Session::requireUserType('admin');
        
        $contractId = $contractId ?? (int)($_GET['contract_id'] ?? 0);
        $closingPeriod = $_GET['period'] ?? '';
        
        if (!$contractId || !$closingPeriod) {
            echo '<div class="alert alert-danger">必要なパラメータが不足しています。</div>';
            return;
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 締め処理記録を取得
        $closingSql = "SELECT mcr.*, c.name as company_name, b.name as branch_name, u.name as doctor_name,
                            finalizer.name as finalized_by_name
                    FROM monthly_closing_records mcr
                    JOIN contracts co ON mcr.contract_id = co.id
                    JOIN companies c ON co.company_id = c.id
                    JOIN branches b ON co.branch_id = b.id
                    JOIN users u ON mcr.doctor_id = u.id
                    LEFT JOIN users finalizer ON mcr.finalized_by = finalizer.id
                    WHERE mcr.contract_id = :contract_id 
                    AND mcr.closing_period = :closing_period";
        
        $stmt = $db->prepare($closingSql);
        $stmt->execute([
            'contract_id' => $contractId,
            'closing_period' => $closingPeriod
        ]);
        
        $closingRecord = $stmt->fetch();
        
        if (!$closingRecord) {
            echo '<div class="alert alert-warning">指定された契約・期間の締め処理記録が見つかりません。</div>';
            return;
        }
        
        // シミュレーションデータをデコード
        $simulationData = $closingRecord['simulation_data'] ? 
            json_decode($closingRecord['simulation_data'], true) : null;
        
        // 処理履歴を取得
        $historySql = "SELECT cph.*, u.name as action_by_name
                    FROM closing_process_history cph
                    LEFT JOIN users u ON cph.action_by = u.id
                    WHERE cph.monthly_closing_record_id = :closing_record_id
                    ORDER BY cph.action_at DESC";
        
        $stmt = $db->prepare($historySql);
        $stmt->execute(['closing_record_id' => $closingRecord['id']]);
        $history = $stmt->fetchAll();
                
        // 詳細表示用の部分ビューを読み込み
        ob_start();
        include __DIR__ . '/../views/closing/detail_partial.php';
        echo ob_get_clean();
    }


    /**
     * 時間重複チェック
     */
    private function checkTimeOverlap($doctorId, $serviceDate, $startTime, $endTime, $excludeId = null) {
        // 時間がnullの場合は重複チェック不要
        if (is_null($startTime) || is_null($endTime)) {
            return ['valid' => true];
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT sr.id, sr.start_time, sr.end_time, sr.service_type,
                    co.company_id, comp.name as company_name, b.name as branch_name
                FROM service_records sr
                JOIN contracts co ON sr.contract_id = co.id
                JOIN companies comp ON co.company_id = comp.id
                JOIN branches b ON co.branch_id = b.id
                WHERE sr.doctor_id = :doctor_id 
                AND sr.service_date = :service_date 
                AND sr.service_hours > 0
                AND sr.start_time IS NOT NULL 
                AND sr.end_time IS NOT NULL"; // 時間がnullでない記録のみ
        
        $params = [
            'doctor_id' => $doctorId,
            'service_date' => $serviceDate
        ];
        
        // 編集時は自分の記録を除外
        if ($excludeId) {
            $sql .= " AND sr.id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $existingRecords = $stmt->fetchAll();
        
        foreach ($existingRecords as $record) {
            $existingStart = $record['start_time'];
            $existingEnd = $record['end_time'];
            
            // 時間重複チェック
            if ($this->isTimeOverlap($startTime, $endTime, $existingStart, $existingEnd)) {
                $serviceTypeLabel = $this->getServiceTypeLabel($record['service_type']);
                return [
                    'valid' => false,
                    'message' => "指定した時間帯は既存の役務記録と重複しています。\n" .
                            "重複する記録: {$record['company_name']} - {$record['branch_name']} " .
                            "({$existingStart} - {$existingEnd}) [{$serviceTypeLabel}]",
                    'conflicting_record' => $record
                ];
            }
        }
        
        return ['valid' => true];
    }

    /**
     * 時間重複判定
     */
    private function isTimeOverlap($start1, $end1, $start2, $end2) {
        // 時間を秒に変換
        $start1_seconds = strtotime("1970-01-01 $start1");
        $end1_seconds = strtotime("1970-01-01 $end1");
        $start2_seconds = strtotime("1970-01-01 $start2");
        $end2_seconds = strtotime("1970-01-01 $end2");
        
        // 重複判定: 一方の開始時間が他方の終了時間より前で、かつ一方の終了時間が他方の開始時間より後
        return ($start1_seconds < $end2_seconds) && ($end1_seconds > $start2_seconds);
    }

    /**
     * 役務種別のラベル取得
     */
    private function getServiceTypeLabel($serviceType) {
        $labels = [
            'regular' => '定期訪問',
            'emergency' => '臨時訪問',
            'extension' => '定期延長'
        ];
        
        return $labels[$serviceType] ?? '定期訪問';
    }

    /**
     * API: 時間重複チェック（Ajax用）
     */
    public function checkTimeOverlapApi() {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json');
        
        try {
            $doctorId = Session::get('user_id');
            $serviceDate = $_POST['service_date'] ?? '';
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';
            $excludeId = !empty($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : null;
            
            // バリデーション
            if (empty($serviceDate)) {
                echo json_encode([
                    'valid' => false,
                    'message' => '役務日が必要です。'
                ]);
                return;
            }
            
            // 時間がnullまたは空の場合は重複チェック不要
            if (empty($startTime) || empty($endTime)) {
                echo json_encode(['valid' => true]);
                return;
            }
            
            // 時間の妥当性チェック
            if ($startTime >= $endTime) {
                echo json_encode([
                    'valid' => false,
                    'message' => '終了時刻は開始時刻より後に設定してください。'
                ]);
                return;
            }
            
            // 重複チェック
            $result = $this->checkTimeOverlap($doctorId, $serviceDate, $startTime, $endTime, $excludeId);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log('Time overlap check API error: ' . $e->getMessage());
            echo json_encode([
                'valid' => false,
                'message' => 'システムエラーが発生しました。'
            ]);
        }
    }

    public function adminDetailApi($contractId) {
        Session::requireUserType('admin');
        
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT sr.*, u.name as doctor_name, c.name as company_name, b.name as branch_name
                FROM service_records sr
                JOIN contracts co ON sr.contract_id = co.id
                JOIN users u ON sr.doctor_id = u.id
                JOIN companies c ON co.company_id = c.id
                JOIN branches b ON co.branch_id = b.id
                WHERE sr.contract_id = :contract_id
                AND YEAR(sr.service_date) = :year 
                AND MONTH(sr.service_date) = :month
                ORDER BY sr.service_date DESC, sr.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'contract_id' => $contractId,
            'year' => $year,
            'month' => $month
        ]);
        $records = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $records
        ]);
    }

    /**
     * API: 締め処理状態チェック
     */
    public function checkClosingStatusApi() {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $contractId = (int)($_POST['contract_id'] ?? 0);
            $serviceDate = $_POST['service_date'] ?? '';
            
            if (!$contractId || !$serviceDate) {
                echo json_encode([
                    'success' => false,
                    'error' => '必要なパラメータが不足しています'
                ]);
                return;
            }
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 年月を取得
            $year = date('Y', strtotime($serviceDate));
            $month = date('n', strtotime($serviceDate));
            $closingPeriod = sprintf('%04d-%02d', $year, $month);
            
            // 締め処理レコードを確認
            $sql = "SELECT 
                        mcr.id,
                        mcr.status,
                        mcr.finalized_at,
                        mcr.finalized_by,
                        u.name as finalized_by_name
                    FROM monthly_closing_records mcr
                    LEFT JOIN users u ON mcr.finalized_by = u.id
                    WHERE mcr.contract_id = :contract_id 
                    AND mcr.closing_period = :closing_period 
                    AND mcr.status = 'finalized'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'contract_id' => $contractId,
                'closing_period' => $closingPeriod
            ]);
            
            $closingRecord = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'is_closed' => !empty($closingRecord),
                'closing_period' => $closingPeriod,
                'finalized_at' => $closingRecord['finalized_at'] ?? null,
                'finalized_by_name' => $closingRecord['finalized_by_name'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log('Closing status check API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました'
            ]);
        }
    }

    /**
     * API: 指定年月に有効な契約リストを取得(産業医用)
     */
    public function getContractsForMonth() {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $yearMonth = $_GET['year_month'] ?? '';
            $doctorId = Session::get('user_id');
            
            if (empty($yearMonth)) {
                echo json_encode([
                    'success' => false,
                    'error' => '年月が指定されていません'
                ]);
                return;
            }
            
            // 年月をパース
            $yearMonthParts = explode('-', $yearMonth);
            if (count($yearMonthParts) !== 2) {
                echo json_encode([
                    'success' => false,
                    'error' => '年月の形式が正しくありません'
                ]);
                return;
            }
            
            $year = (int)$yearMonthParts[0];
            $month = (int)$yearMonthParts[1];
            
            // 該当月の初日と最終日を計算
            $monthStart = sprintf('%04d-%02d-01', $year, $month);
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 選択した年月に反映日が該当する契約を取得
            $sql = "SELECT DISTINCT co.id as contract_id, 
                           c.name as company_name, 
                           b.name as branch_name
                    FROM contracts co
                    JOIN branches b ON co.branch_id = b.id
                    JOIN companies c ON co.company_id = c.id
                    WHERE co.doctor_id = :doctor_id
                    AND co.contract_status = 'active'
                    AND co.effective_date <= :month_end
                    AND (co.effective_end_date IS NULL OR co.effective_end_date >= :month_start)
                    ORDER BY c.name ASC, b.name ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'doctor_id' => $doctorId,
                'month_start' => $monthStart,
                'month_end' => $monthEnd
            ]);
            $contracts = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'contracts' => $contracts
            ]);
            
        } catch (Exception $e) {
            error_log('Get contracts for month API error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました'
            ]);
        }
    }
}

?>