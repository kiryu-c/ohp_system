<?php
// app/controllers/CompanyController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Branch.php';
require_once __DIR__ . '/../models/Contract.php';

class CompanyController extends BaseController {
    public function index() {
        Session::requireUserType('admin');
        
        // 検索条件を取得
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // 並び替えパラメータを取得
        $sortColumn = $_GET['sort'] ?? 'company_name';
        $sortOrder = $_GET['order'] ?? 'asc';
        
        // 許可されたソートカラムのホワイトリスト
        $allowedSortColumns = ['company_name', 'branch_name', 'is_active', 'contract_count'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'company_name';
        }
    
        // 並び替え順序の検証
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'DESC' : 'ASC';

        // ページネーション設定
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 20)));
        $offset = ($currentPage - 1) * $perPage;
        
        // 拠点データを取得
        require_once __DIR__ . '/../models/Branch.php';
        $branchModel = new Branch();
        $db = Database::getInstance()->getConnection();
        
        // 件数カウント用のSQL
        $countSql = "SELECT COUNT(DISTINCT b.id) as total
                    FROM branches b
                    JOIN companies c ON b.company_id = c.id
                    WHERE 1=1";
        
        // データ取得用のSQL
        $sql = "SELECT b.*, c.name as company_name, c.is_active as company_is_active,
                (SELECT COUNT(*) 
                 FROM contracts 
                 WHERE branch_id = b.id 
                 AND contract_status = 'active'
                 AND effective_date <= CURDATE()
                 AND (effective_end_date IS NULL OR effective_end_date >= CURDATE())
                ) as contract_count,
                (SELECT COUNT(*) FROM users WHERE company_id = b.company_id AND is_active = true) as user_count
                FROM branches b
                JOIN companies c ON b.company_id = c.id
                WHERE 1=1";
        
        $params = [];
        $whereConditions = '';
        
        // 検索条件の適用
        if (!empty($search)) {
            $whereConditions = " AND (c.name LIKE :search OR b.name LIKE :search2 OR b.address LIKE :search3 OR b.phone LIKE :search4)";
            $params['search'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
            $params['search4'] = "%{$search}%";
        }
        
        // ステータスフィルター
        if ($status !== '') {
            $whereConditions .= " AND b.is_active = :status";
            $params['status'] = $status;
        }
        
        // 条件を追加
        $countSql .= $whereConditions;
        $sql .= $whereConditions;
        
        // 総件数を取得
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch()['total'];
        
        // ソートとLIMITを追加
        $orderByClause = match($sortColumn) {
            'company_name' => "c.name $sortOrder, b.name $sortOrder",
            'branch_name' => "b.name $sortOrder, c.name $sortOrder",
            'is_active' => "b.is_active $sortOrder, c.name ASC, b.name ASC",
            'contract_count' => "contract_count $sortOrder, c.name ASC, b.name ASC",
            default => "c.name $sortOrder, b.name $sortOrder"
        };
        
        $sql .= " ORDER BY {$orderByClause} LIMIT :limit OFFSET :offset";
        
        // データを取得
        $stmt = $db->prepare($sql);
        
        // パラメータをバインド
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $branches = $stmt->fetchAll();
        
        // ページネーション情報を計算
        $totalPages = ceil($totalCount / $perPage);
        
        // 統計情報を計算(全体データから)
        $statsSql = "SELECT 
                        COUNT(DISTINCT c.id) as total_companies,
                        COUNT(DISTINCT b.id) as total_branches,
                        COUNT(DISTINCT CASE WHEN b.is_active = 1 THEN b.id END) as active_branches,
                        COALESCE(SUM(
                            CASE 
                                WHEN ct.contract_status = 'active' 
                                AND ct.effective_date <= CURDATE()
                                AND (ct.effective_end_date IS NULL OR ct.effective_end_date >= CURDATE())
                                THEN 1 
                                ELSE 0 
                            END
                        ), 0) as total_contracts
                    FROM companies c
                    LEFT JOIN branches b ON c.id = b.company_id
                    LEFT JOIN contracts ct ON b.id = ct.branch_id
                    WHERE c.is_active = 1";
        
        $statsStmt = $db->prepare($statsSql);
        $statsStmt->execute();
        $statsResult = $statsStmt->fetch();
        
        $stats = [
            'total_companies' => (int)$statsResult['total_companies'],
            'total_branches' => (int)$statsResult['total_branches'],
            'active_branches' => (int)$statsResult['active_branches'],
            'total_contracts' => (int)$statsResult['total_contracts']
        ];
        
        // 総ユーザー数を取得
        $userSql = "SELECT COUNT(DISTINCT u.id) as total FROM users u 
                    JOIN companies c ON u.company_id = c.id 
                    WHERE u.user_type = 'company' AND u.is_active = true AND c.is_active = true";
        $userStmt = $db->prepare($userSql);
        $userStmt->execute();
        $userResult = $userStmt->fetch();
        $stats['total_users'] = (int)$userResult['total'];
        
        ob_start();
        include __DIR__ . '/../views/companies/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function create() {
        Session::requireUserType('admin');
        
        ob_start();
        include __DIR__ . '/../views/companies/create.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function store() {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect('companies/create');
        }
        
        $name = sanitize($_POST['name'] ?? '');
        $branchName = sanitize($_POST['branch_name'] ?? '');
        $accountCode = sanitize($_POST['account_code'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        
        // バリデーション
        $errors = [];
        
        if (empty($name)) {
            $errors[] = '企業名を入力してください。';
        }
        
        if (empty($branchName)) {
            $errors[] = '拠点名を入力してください。';
        }
        
        if (empty($accountCode)) {
            $errors[] = '勘定科目番号を入力してください。';
        } elseif (strlen($accountCode) > 16) {
            $errors[] = '勘定科目番号は16桁以内で入力してください。';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect('companies/create');
        }
        
        // トランザクション開始
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            // 企業を作成
            $companyModel = new Company();
            $companyData = [
                'name' => $name,
                'address' => '', // 企業レベルでは住所を保持しない
                'phone' => '',
                'email' => ''
            ];
            
            $companyId = $companyModel->create($companyData);
            
            if (!$companyId) {
                throw new Exception('企業の作成に失敗しました。');
            }
            
            // 拠点を作成
            require_once __DIR__ . '/../models/Branch.php';
            $branchModel = new Branch();
            $branchData = [
                'company_id' => $companyId,
                'name' => $branchName,
                'account_code' => $accountCode,
                'address' => $address,
                'phone' => $phone,
                'email' => $email
            ];
            
            $branchId = $branchModel->create($branchData);
            
            if (!$branchId) {
                throw new Exception('拠点の作成に失敗しました。');
            }
            
            $db->commit();
            $this->setFlash('success', '企業と拠点を作成しました。');
            redirect('companies');
            
        } catch (Exception $e) {
            $db->rollBack();
            $this->setFlash('error', $e->getMessage());
            redirect('companies/create');
        }
    }
    
    public function edit($id) {
        Session::requireUserType('admin');
        
        $companyModel = new Company();
        $company = $companyModel->findById($id);
        
        if (!$company) {
            $this->setFlash('error', '企業が見つかりません。');
            redirect('companies');
        }
        
        // 拠点一覧を取得
        require_once __DIR__ . '/../models/Branch.php';
        $branchModel = new Branch();
        $branches = $branchModel->findByCompany($id);
        
        // 各拠点の有効契約数を取得
        $db = Database::getInstance()->getConnection();
        
        foreach ($branches as &$branch) {
            $sql = "SELECT COUNT(*) as count 
                    FROM contracts 
                    WHERE branch_id = :branch_id 
                    AND contract_status = 'active'
                    AND effective_date <= CURDATE()
                    AND (effective_end_date IS NULL OR effective_end_date >= CURDATE())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(['branch_id' => $branch['id']]);
            $result = $stmt->fetch();
            $branch['contract_count'] = (int)$result['count'];
        }
        unset($branch); // 参照を解除（重要！）
        
        ob_start();
        include __DIR__ . '/../views/companies/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function update($id) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect("companies/{$id}/edit");
        }
        
        $companyModel = new Company();
        $company = $companyModel->findById($id);
        
        if (!$company) {
            $this->setFlash('error', '企業が見つかりません。');
            redirect('companies');
        }
        
        $name = sanitize($_POST['name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // バリデーション
        $errors = [];
        
        if (empty($name)) {
            $errors[] = '企業名を入力してください。';
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '有効なメールアドレスを入力してください。';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect("companies/{$id}/edit");
        }
        
        $data = [
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'is_active' => $isActive
        ];
        
        if ($companyModel->update($id, $data)) {
            $this->setFlash('success', '企業情報を更新しました。');
            redirect('companies');
        } else {
            $this->setFlash('error', '企業情報の更新に失敗しました。');
            redirect("companies/{$id}/edit");
        }
    }
    
    public function delete($id) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect('companies');
        }
        
        $companyModel = new Company();
        $company = $companyModel->findById($id);
        
        if (!$company) {
            $this->setFlash('error', '企業が見つかりません。');
            redirect('companies');
        }
        
        // 論理削除（is_active = 0）
        if ($companyModel->update($id, ['is_active' => 0])) {
            $this->setFlash('success', '企業を削除しました。');
        } else {
            $this->setFlash('error', '企業の削除に失敗しました。');
        }
        
        redirect('companies');
    }
}
?>