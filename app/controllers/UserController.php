<?php
// app/controllers/UserController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Company.php';

class UserController extends BaseController {
    public function index() {
        Session::requireUserType('admin');
        
        // ページネーション設定
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 20);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 20;
        }
        $offset = ($currentPage - 1) * $perPage;
        
        // 検索パラメータを取得
        $search = trim($_GET['search'] ?? '');
        $userType = trim($_GET['user_type'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $company = trim($_GET['company'] ?? '');
        
        // ★ 並び替えパラメータを追加
        $sortColumn = $_GET['sort'] ?? 'created_at';
        $sortOrder = $_GET['order'] ?? 'desc';
        
        // 許可されたソートカラムのみを受け入れる
        $allowedSortColumns = ['id', 'name', 'login_id', 'email', 'user_type', 'company_name', 'is_active', 'created_at'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }
        
        // ソート順序の検証
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';

        // データベース接続を取得
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 総件数取得用のSQL（メインクエリと同じ条件）
        $countSql = "SELECT COUNT(*) as total
                     FROM users u 
                     LEFT JOIN companies c ON u.company_id = c.id 
                     WHERE 1=1";
        
        $params = [];
        
        // 検索条件を追加
        if ($search !== '') {
            $countSql .= " AND (u.name LIKE :search OR u.login_id LIKE :search2 OR u.email LIKE :search3)";
            $params['search'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
        }
        
        if ($userType !== '') {
            $countSql .= " AND u.user_type = :user_type";
            $params['user_type'] = $userType;
        }
        
        // ステータスフィルター：空文字列の場合は全て表示
        if ($status !== '') {
            $countSql .= " AND u.is_active = :status";
            $params['status'] = (int)$status;
        }
        
        if ($company !== '') {
            $countSql .= " AND u.company_id = :company_id";
            $params['company_id'] = (int)$company;
        }
        
        // 総件数を取得
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // ページ数を計算
        $totalPages = ceil($totalCount / $perPage);
        
        // 現在のページが範囲外の場合は調整
        if ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
            $offset = ($currentPage - 1) * $perPage;
        }
        
        // メインのユーザー情報取得SQL（ページネーション対応）
        $sql = "SELECT u.*, c.name as company_name 
                FROM users u 
                LEFT JOIN companies c ON u.company_id = c.id 
                WHERE 1=1";
        
        // 同じ検索条件を追加
        if ($search !== '') {
            $sql .= " AND (u.name LIKE :search OR u.login_id LIKE :search2 OR u.email LIKE :search3)";
        }
        
        if ($userType !== '') {
            $sql .= " AND u.user_type = :user_type";
        }
        
        if ($status !== '') {
            $sql .= " AND u.is_active = :status";
        }
        
        if ($company !== '') {
            $sql .= " AND u.company_id = :company_id";
        }
        
        // ★ 並び替えを追加(元のORDER BYを置き換え)
        if ($sortColumn === 'company_name') {
            $sql .= " ORDER BY c.name {$sortOrder}, u.name ASC";
        } else {
            $sql .= " ORDER BY u.{$sortColumn} {$sortOrder}";
            // 第2ソートキーを追加
            if ($sortColumn !== 'name') {
                $sql .= ", u.name ASC";
            }
        }

        $sql .= " LIMIT :limit OFFSET :offset";
        
        // ユーザー情報を取得
        $stmt = $db->prepare($sql);
        
        // パラメータをバインド
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 各ユーザーの拠点情報を個別に取得
        $userBranches = [];
        
        if (!empty($users)) {
            $userIds = array_column($users, 'id');
            
            if (!empty($userIds)) {
                // プレースホルダーを安全に作成
                $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                
                $branchSql = "SELECT ubm.user_id, b.id as branch_id, b.name as branch_name, b.company_id
                            FROM user_branch_mappings ubm
                            JOIN branches b ON ubm.branch_id = b.id
                            WHERE ubm.user_id IN ({$placeholders})
                            AND ubm.is_active = 1 AND b.is_active = 1
                            ORDER BY ubm.user_id, b.name";
                
                $stmt = $db->prepare($branchSql);
                $stmt->execute($userIds);
                $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // ユーザーID別に拠点をグループ化
                foreach ($branches as $branch) {
                    if (!isset($userBranches[$branch['user_id']])) {
                        $userBranches[$branch['user_id']] = [];
                    }
                    $userBranches[$branch['user_id']][] = $branch;
                }
            }
        }
        
        // ユーザー配列に拠点情報を追加（元の配列を変更しない）
        $usersWithBranches = [];
        foreach ($users as $user) {
            $userWithBranches = $user; // コピーを作成
            $userWithBranches['branches'] = $userBranches[$user['id']] ?? [];
            $usersWithBranches[] = $userWithBranches;
        }
        
        // 最終的な配列を$usersに代入
        $users = $usersWithBranches;
        
        ob_start();
        include __DIR__ . '/../views/users/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function create() {
        Session::requireUserType('admin');
        
        $companyModel = new Company();
        $companies = $companyModel->findActive();
        
        ob_start();
        include __DIR__ . '/../views/users/create.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function store() {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect('users/create');
        }
        
        $loginId = sanitize($_POST['login_id'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $userType = $_POST['user_type'] ?? '';
        $companyId = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;
        $contractType = $_POST['contract_type'] ?? null;
        
        // 産業医用の追加項目
        $postalCode = sanitize($_POST['postal_code'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $tradeName = sanitize($_POST['trade_name'] ?? '');
        $bankName = sanitize($_POST['bank_name'] ?? '');
        $bankBranchName = sanitize($_POST['bank_branch_name'] ?? '');
        $bankAccountType = $_POST['bank_account_type'] ?? 'ordinary';
        $bankAccountNumber = sanitize($_POST['bank_account_number'] ?? '');
        $bankAccountHolder = sanitize($_POST['bank_account_holder'] ?? '');
        
        // ★ 新規追加: 事業者区分とインボイス番号
        $businessClassification = $_POST['business_classification'] ?? null;
        $invoiceRegistrationNumber = sanitize($_POST['invoice_registration_number'] ?? '');
        
        // 企業ユーザーの場合、選択された拠点IDを取得
        $selectedBranchIds = [];
        if ($userType === 'company' && isset($_POST['branch_ids'])) {
            $selectedBranchIds = array_map('intval', $_POST['branch_ids']);
        }
        
        // バリデーション
        $errors = [];
        
        if (empty($loginId)) {
            $errors[] = 'ログインIDを入力してください。';
        }
        
        // パスワードが入力されている場合のバリデーション
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $errors[] = 'パスワードは8文字以上で入力してください。';
            }
            if (!preg_match('/[a-zA-Z]/', $password)) {
                $errors[] = 'パスワードにはアルファベットを含めてください。';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'パスワードには数字を含めてください。';
            }
        }
        
        
        if (empty($name)) {
            $errors[] = '氏名を入力してください。';
        }
        
        if (empty($email)) {
            $errors[] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '有効なメールアドレスを入力してください。';
        }
        
        if (!in_array($userType, ['doctor', 'company', 'admin'])) {
            $errors[] = 'ユーザータイプを選択してください。';
        }
        
        if ($userType === 'company') {
            if (empty($companyId)) {
                $errors[] = '企業ユーザーの場合は所属企業を選択してください。';
            }
            if (empty($selectedBranchIds)) {
                $errors[] = '企業ユーザーの場合は拠点を選択してください。';
            } else {
                // 選択された拠点が指定企業に属するかチェック
                $userModel = new User();
                $validBranchIds = array_column($userModel->getCompanyBranches($companyId), 'id');
                $invalidBranches = array_diff($selectedBranchIds, $validBranchIds);
                if (!empty($invalidBranches)) {
                    $errors[] = '選択された拠点が無効です。';
                }
            }
        }
        
        if ($userType === 'doctor') {
            if (empty($contractType)) {
                $errors[] = '産業医の場合は契約形態を選択してください。';
            }
            
            // 産業医用項目のバリデーション
            if (!empty($postalCode) && !preg_match('/^\d{3}-?\d{4}$/', $postalCode)) {
                $errors[] = '郵便番号は123-4567の形式で入力してください。';
            }
            
            if (!empty($bankAccountNumber) && !preg_match('/^\d+$/', $bankAccountNumber)) {
                $errors[] = '口座番号は数字のみで入力してください。';
            }
            
            // 振込先情報の整合性チェック
            $bankFields = [$bankName, $bankBranchName, $bankAccountNumber, $bankAccountHolder];
            $filledBankFields = array_filter($bankFields, function($field) { return !empty($field); });
            
            if (!empty($filledBankFields) && count($filledBankFields) < 4) {
                $errors[] = '振込先情報を入力する場合は、銀行名、支店名、口座番号、口座名義をすべて入力してください。';
            }
            
            // ★ 新規追加: 事業者区分のバリデーション
            if (empty($businessClassification)) {
                $errors[] = '産業医の場合は事業者区分を選択してください。';
            } elseif (!in_array($businessClassification, ['taxable', 'tax_exempt'])) {
                $errors[] = '有効な事業者区分を選択してください。';
            }
            
            // ★ 新規追加: インボイス番号のバリデーション
            if ($businessClassification === 'taxable') {
                if (empty($invoiceRegistrationNumber)) {
                    $errors[] = '課税事業者の場合はインボイス登録番号を入力してください。';
                } elseif (!preg_match('/^T\d{13}$/', $invoiceRegistrationNumber)) {
                    $errors[] = 'インボイス登録番号は「T」+13桁の数字の形式で入力してください。(例: T1234567890123)';
                }
            }
        }
        
        // ログインIDの重複チェック
        $userModel = new User();
        if ($userModel->isLoginIdExists($loginId)) {
            $errors[] = 'このログインIDは既に使用されています。';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect('users/create');
        }
        
        // 郵便番号のハイフン整形
        if (!empty($postalCode)) {
            $postalCode = preg_replace('/(\d{3})(\d{4})/', '$1-$2', str_replace('-', '', $postalCode));
        }
        
        $data = [
            'login_id' => $loginId,
            'password' => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null,
            'name' => $name,
            'email' => $email,
            'user_type' => $userType,
            'company_id' => $companyId,
            'contract_type' => $contractType,
            'is_active' => 1
        ];
        
        // 産業医の場合は追加項目を含める
        if ($userType === 'doctor') {
            $data['postal_code'] = $postalCode ?: null;
            $data['address'] = $address ?: null;
            $data['trade_name'] = $tradeName ?: null;
            $data['bank_name'] = $bankName ?: null;
            $data['bank_branch_name'] = $bankBranchName ?: null;
            $data['bank_account_type'] = $bankAccountType;
            $data['bank_account_number'] = $bankAccountNumber ?: null;
            $data['bank_account_holder'] = $bankAccountHolder ?: null;
            
            // ★ 新規追加: 事業者区分とインボイス番号
            $data['business_classification'] = $businessClassification;
            $data['invoice_registration_number'] = $businessClassification === 'taxable' ? $invoiceRegistrationNumber : null;
        }
        
        // 企業ユーザーの場合は拠点紐づけも含めて作成
        if ($userType === 'company') {
            $userId = $userModel->createCompanyUserWithBranches($data, $selectedBranchIds);
        } else {
            $userId = $userModel->createUser($data);
        }
        
        if ($userId) {
            $this->setFlash('success', 'ユーザーを作成しました。');
            redirect('users');
        } else {
            $this->setFlash('error', 'ユーザーの作成に失敗しました。');
            redirect('users/create');
        }
    }
    
    public function edit($id) {
        // 管理者または自分自身の編集のみ許可
        $currentUserId = Session::get('user_id');
        $currentUserType = Session::get('user_type');
        
        // 管理者でない場合は、自分自身のIDのみ編集可能
        if ($currentUserType !== 'admin') {
            if ((int)$id !== (int)$currentUserId) {
                Session::setFlash('error', '他のユーザーの情報を編集する権限がありません。');
                redirect('dashboard');
                return;
            }
        }
        
        $userModel = new User();
        $user = $userModel->findByIdWithBranches($id); // 拠点情報も含めて取得
        
        if (!$user) {
            $this->setFlash('error', 'ユーザーが見つかりません。');
            if ($currentUserType === 'admin') {
                redirect('users');
            } else {
                redirect('dashboard');
            }
            return;
        }
        
        $userModel = new User();
        $user = $userModel->findByIdWithBranches($id); // 拠点情報も含めて取得
        
        if (!$user) {
            $this->setFlash('error', 'ユーザーが見つかりません。');
            redirect('users');
        }
        
        $companyModel = new Company();
        $companies = $companyModel->findActive();
        
        // 企業ユーザーの場合、現在の拠点情報を取得
        $userBranches = [];
        $availableBranches = [];
        
        if ($user['user_type'] === 'company' && !empty($user['branches'])) {
            $userBranches = array_column($user['branches'], 'id');
            
            // 所属企業の全拠点を取得
            $companyIds = array_unique(array_column($user['branches'], 'company_id'));
            foreach ($companyIds as $companyId) {
                $branches = $userModel->getCompanyBranches($companyId);
                $availableBranches[$companyId] = $branches;
            }
        }
        
        // ★ 追加: 編集モードの判定（管理者か本人か）
        $isOwnProfile = ((int)$id === (int)$currentUserId);
        $isAdmin = ($currentUserType === 'admin');

        ob_start();
        include __DIR__ . '/../views/users/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
        
    public function update($id) {
        // ★ 修正: 管理者または自分自身の更新のみ許可
        $currentUserId = Session::get('user_id');
        $currentUserType = Session::get('user_type');
        
        // 管理者でない場合は、自分自身のIDのみ更新可能
        if ($currentUserType !== 'admin') {
            if ((int)$id !== (int)$currentUserId) {
                Session::setFlash('error', '他のユーザーの情報を更新する権限がありません。');
                redirect('dashboard');
                return;
            }
        }
        
        if (!$this->validateCsrf()) {
            redirect("users/{$id}/edit");
        }
        
        $userModel = new User();
        $user = $userModel->findById($id);
        
        if (!$user) {
            $this->setFlash('error', 'ユーザーが見つかりません。');
            redirect('users');
        }
        
        // フォームデータ取得
        $loginId = sanitize($_POST['login_id'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        // 企業IDの取得（POSTに含まれない場合は既存の値を維持）
        $companyIdProvided = isset($_POST['company_id']);
        if ($companyIdProvided) {
            $companyId = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;
        } else {
            // フォームに含まれていない場合は既存の企業IDを維持
            $companyId = $user['company_id'];
        }
        
        $contractType = $_POST['contract_type'] ?? null;
        // ★ 修正: ユーザータイプとログインIDは管理者のみ変更可能
        if ($currentUserType === 'admin') {
            $userType = $_POST['user_type'] ?? '';
            $isActive = (int)($_POST['is_active'] ?? 0);
        } else {
            // 本人の場合は既存の値を維持
            $userType = $user['user_type'];
            $loginId = $user['login_id']; // ログインIDも変更不可
            $isActive = $user['is_active'];
        }

        // 産業医用の追加項目
        $postalCode = sanitize($_POST['postal_code'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $tradeName = sanitize($_POST['trade_name'] ?? '');
        $bankName = sanitize($_POST['bank_name'] ?? '');
        $bankBranchName = sanitize($_POST['bank_branch_name'] ?? '');
        $bankAccountType = $_POST['bank_account_type'] ?? 'ordinary';
        $bankAccountNumber = sanitize($_POST['bank_account_number'] ?? '');
        $bankAccountHolder = sanitize($_POST['bank_account_holder'] ?? '');
        $businessClassification = $_POST['business_classification'] ?? null;
        $invoiceRegistrationNumber = sanitize($_POST['invoice_registration_number'] ?? '');
        
        // 企業ユーザーの場合、選択された拠点IDを取得
        $selectedBranchIds = [];
        $branchIdsProvided = isset($_POST['branch_ids']);
        
        if ($userType === 'company') {
            if ($branchIdsProvided) {
                // 拠点情報がPOSTされている場合は、その値を使用
                $selectedBranchIds = array_map('intval', $_POST['branch_ids']);
            } else {
                // 拠点情報がPOSTされていない場合は、既存の拠点を取得して維持
                $selectedBranchIds = $userModel->getUserBranches($id);
            }
        }
        
        // バリデーション
        $errors = [];
        
        // ★ ログインIDの検証は管理者のみ
        if ($currentUserType === 'admin' && empty($loginId)) {
            $errors[] = 'ログインIDを入力してください。';
        }
        
        // パスワードが入力されている場合のバリデーション
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $errors[] = 'パスワードは8文字以上で入力してください。';
            }
            if (!preg_match('/[a-zA-Z]/', $password)) {
                $errors[] = 'パスワードにはアルファベットを含めてください。';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'パスワードには数字を含めてください。';
            }
        }
        
        if (empty($name)) {
            $errors[] = '氏名を入力してください。';
        }
        
        if (empty($email)) {
            $errors[] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '有効なメールアドレスを入力してください。';
        }
        
        if ($currentUserType === 'admin' && !in_array($userType, ['doctor', 'company', 'admin'])) {
            $errors[] = 'ユーザータイプを選択してください。';
        }
        
        if ($userType === 'company') {
            // 企業IDのバリデーション（管理者が企業を変更する場合のみ）
            if ($companyIdProvided && empty($companyId)) {
                $errors[] = '企業ユーザーの場合は所属企業を選択してください。';
            }
            
            // 拠点のバリデーション（管理者が拠点を変更する場合のみ）
            if ($branchIdsProvided) {
                if (empty($selectedBranchIds)) {
                    $errors[] = '企業ユーザーの場合は拠点を選択してください。';
                } elseif (!empty($companyId)) {
                    // 選択された拠点が指定企業に属するかチェック
                    $validBranchIds = array_column($userModel->getCompanyBranches($companyId), 'id');
                    $invalidBranches = array_diff($selectedBranchIds, $validBranchIds);
                    if (!empty($invalidBranches)) {
                        $errors[] = '選択された拠点が無効です。';
                    }
                }
            }
            // 企業IDも拠点もPOSTされていない場合は、既存の値を維持するのでバリデーション不要
        }
        
        if ($userType === 'doctor') {
            if (empty($contractType)) {
                $errors[] = '産業医の場合は契約形態を選択してください。';
            }
            
            // 産業医用項目のバリデーション
            if (!empty($postalCode) && !preg_match('/^\d{3}-?\d{4}$/', $postalCode)) {
                $errors[] = '郵便番号は123-4567の形式で入力してください。';
            }
            
            if (!empty($bankAccountNumber) && !preg_match('/^\d+$/', $bankAccountNumber)) {
                $errors[] = '口座番号は数字のみで入力してください。';
            }
            
            // 振込先情報の整合性チェック
            $bankFields = [$bankName, $bankBranchName, $bankAccountNumber, $bankAccountHolder];
            $filledBankFields = array_filter($bankFields, function($field) { return !empty($field); });
            
            if (!empty($filledBankFields) && count($filledBankFields) < 4) {
                $errors[] = '振込先情報を入力する場合は、銀行名、支店名、口座番号、口座名義をすべて入力してください。';
            }
            
            // 事業者区分のバリデーション
            if (empty($businessClassification)) {
                $errors[] = '産業医の場合は事業者区分を選択してください。';
            } elseif (!in_array($businessClassification, ['taxable', 'tax_exempt'])) {
                $errors[] = '有効な事業者区分を選択してください。';
            }
            
            // インボイス番号のバリデーション
            if ($businessClassification === 'taxable') {
                if (empty($invoiceRegistrationNumber)) {
                    $errors[] = '課税事業者の場合はインボイス登録番号を入力してください。';
                } elseif (!preg_match('/^T\d{13}$/', $invoiceRegistrationNumber)) {
                    $errors[] = 'インボイス登録番号は「T」+13桁の数字の形式で入力してください。(例: T1234567890123)';
                }
            }
        }
        
        // ログインIDの重複チェック
        if ($currentUserType === 'admin' && $userModel->isLoginIdExists($loginId, $id)) {
            $errors[] = 'このログインIDは既に使用されています。';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect("users/{$id}/edit");
        }
        
        // 郵便番号のハイフン整形
        if (!empty($postalCode)) {
            $postalCode = preg_replace('/(\d{3})(\d{4})/', '$1-$2', str_replace('-', '', $postalCode));
        }
        
        // ユーザー情報データ
        $data = [
            'login_id' => $loginId,
            'name' => $name,
            'email' => $email,
            'user_type' => $userType,
            'contract_type' => $contractType,
            'is_active' => $isActive
        ];
        
        if ($userType !== 'company') {
            $data['company_id'] = null;
        } else {
            $data['company_id'] = $companyId;
        }
        
        if (!empty($password)) {
            $data['password'] = $password;
        }
        
        // 産業医の場合は追加項目を含める
        if ($userType === 'doctor') {
            $data['postal_code'] = $postalCode ?: null;
            $data['address'] = $address ?: null;
            $data['trade_name'] = $tradeName ?: null;
            $data['bank_name'] = $bankName ?: null;
            $data['bank_branch_name'] = $bankBranchName ?: null;
            $data['bank_account_type'] = $bankAccountType;
            $data['bank_account_number'] = $bankAccountNumber ?: null;
            $data['bank_account_holder'] = $bankAccountHolder ?: null;
            $data['business_classification'] = $businessClassification;
            $data['invoice_registration_number'] = $businessClassification === 'taxable' ? $invoiceRegistrationNumber : null;
        } else {
            // 産業医以外の場合はNULLで上書き
            $data['postal_code'] = null;
            $data['address'] = null;
            $data['trade_name'] = null;
            $data['bank_name'] = null;
            $data['bank_branch_name'] = null;
            $data['bank_account_type'] = 'ordinary';
            $data['bank_account_number'] = null;
            $data['bank_account_holder'] = null;
            $data['business_classification'] = null;
            $data['invoice_registration_number'] = null;
        }
        
        // シンプルな更新処理(トランザクション管理をモデル側に委譲)
        if ($userModel->updateUser($id, $data)) {
            // 企業ユーザーの場合、拠点紐づけも更新
            if ($userType === 'company') {
                $userModel->updateUserBranches($id, $selectedBranchIds);
            } else {
                // 企業ユーザー以外の場合は拠点紐づけをクリア
                $userModel->updateUserBranches($id, []);
            }
            
            $this->setFlash('success', 'ユーザー情報を更新しました。');
            // ★ 修正: リダイレクト先の判定
            if ($currentUserType === 'admin') {
                redirect('users');
            } else {
                redirect('dashboard');
            }
        } else {
            $this->setFlash('error', 'ユーザー情報の更新に失敗しました。');
            redirect("users/{$id}/edit");
        }
    }

    /**
     * 拠点取得用のAPIエンドポイント(AJAX用)
     */
    public function getBranchesByCompany() {
        Session::requireUserType('admin');
        
        $companyId = (int)($_GET['company_id'] ?? 0);
        
        if ($companyId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => '無効な企業IDです']);
            return;
        }
        
        $userModel = new User();
        $branches = $userModel->getCompanyBranches($companyId);
        
        header('Content-Type: application/json');
        echo json_encode(['branches' => $branches]);
    }
    
    public function delete($id) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect('users');
        }
        
        $userModel = new User();
        $user = $userModel->findById($id);
        
        if (!$user) {
            $this->setFlash('error', 'ユーザーが見つかりません。');
            redirect('users');
        }
        
        // 自分自身は削除できない
        if ($user['id'] == Session::get('user_id')) {
            $this->setFlash('error', '自分自身を削除することはできません。');
            redirect('users');
        }
        
        // 論理削除（is_active = 0）
        if ($userModel->update($id, ['is_active' => 0])) {
            $this->setFlash('success', 'ユーザーを削除しました。');
        } else {
            $this->setFlash('error', 'ユーザーの削除に失敗しました。');
        }
        
        redirect('users');
    }

    public function changePassword() {
        Session::requireLogin();
        
        ob_start();
        include __DIR__ . '/../views/users/change_password.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }

    public function updatePassword() {
        Session::requireLogin();
        
        if (!$this->validateCsrf()) {
            redirect('password/change');
        }
        
        $userId = Session::get('user_id');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // バリデーション
        $errors = [];
        
        if (empty($currentPassword)) {
            $errors[] = '現在のパスワードを入力してください。';
        }
        
        if (empty($newPassword)) {
            $errors[] = '新しいパスワードを入力してください。';
        }
        
        if (strlen($newPassword) < 8) {
            $errors[] = 'パスワードは8文字以上で入力してください。';
        }
        if (!preg_match('/[a-zA-Z]/', $newPassword)) {
            $errors[] = 'パスワードにはアルファベットを含めてください。';
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'パスワードには数字を含めてください。';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = '新しいパスワードと確認用パスワードが一致しません。';
        }
        
        if (empty($errors)) {
            $userModel = new User();
            $user = $userModel->findById($userId);
            
            // 現在のパスワードの確認
            if (!password_verify($currentPassword, $user['password'])) {
                $errors[] = '現在のパスワードが正しくありません。';
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect('password/change');
        }
        
        // パスワードの更新
        $userModel = new User();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        if ($userModel->update($userId, ['password' => $hashedPassword])) {
            $this->setFlash('success', 'パスワードを変更しました。');
            redirect('dashboard');
        } else {
            $this->setFlash('error', 'パスワードの変更に失敗しました。');
            redirect('password/change');
        }
    }

    /**
     * ログイン招待メールを送信
     */
    public function sendInvitation($id) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'CSRFトークンが無効です。');
            redirect('users');
        }
        
        $userModel = new User();
        $user = $userModel->findById($id);
        
        if (!$user) {
            $this->setFlash('error', 'ユーザーが見つかりません。');
            redirect('users');
        }
        
        // メールアドレスが登録されているか確認
        if (empty($user['email'])) {
            $this->setFlash('error', 'このユーザーにはメールアドレスが登録されていません。');
            redirect('users');
        }
        
        // 暫定パスワードを生成
        $temporaryPassword = $this->generateTemporaryPassword();
        
        // パスワードをハッシュ化してDBに保存
        $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        if (!$userModel->update($id, ['password' => $hashedPassword])) {
            $this->setFlash('error', 'パスワードの更新に失敗しました。');
            redirect('users');
        }
        
        // メール送信
        require_once __DIR__ . '/../core/Mailer.php';
        $mailer = new Mailer();
        
        $emailSent = $mailer->sendLoginInvitationEmail(
            $user['email'],
            $user['name'],
            $user['login_id'],
            $temporaryPassword
        );
        
        if ($emailSent) {
            $this->setFlash('success', 'ログイン招待メールを送信しました。');
        } else {
            $this->setFlash('error', 'メールの送信に失敗しました。管理者に連絡してください。');
        }
        
        redirect('users');
    }

    /**
     * 暫定パスワードを生成(12文字、英数字記号)
     */
    private function generateTemporaryPassword() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $password = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < 12; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }
}
?>