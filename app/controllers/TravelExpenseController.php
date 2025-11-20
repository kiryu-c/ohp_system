<?php
// app/controllers/TravelExpenseController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/TravelExpense.php';
require_once __DIR__ . '/../models/ServiceRecord.php';

class TravelExpenseController extends BaseController {
    
    
    /**
     * service_record_historyに履歴を追加
     * 
     * @param int $serviceRecordId 役務記録ID
     * @param int $expenseId 交通費ID
     * @param string $actionType アクションタイプ
     * @param string $statusFrom 変更前ステータス
     * @param string $statusTo 変更後ステータス
     * @param string|null $comment コメント
     * @param array|null $metadata 追加メタデータ
     * @param PDO|null $db データベース接続（トランザクション内で呼ぶ場合は必須）
     * @return bool 成功した場合true
     */
    private function addServiceRecordHistory($serviceRecordId, $expenseId, $actionType, $statusFrom, $statusTo, $comment = null, $metadata = null, $db = null) {
        try {
            // DB接続が渡されていない場合のみ取得
            if ($db === null) {
                require_once __DIR__ . '/../core/Database.php';
                $db = Database::getInstance()->getConnection();
            }
            
            // メタデータに交通費IDを追加
            $metadataArray = $metadata ?? [];
            $metadataArray['travel_expense_id'] = $expenseId;
            
            $sql = "INSERT INTO service_record_history 
                    (service_record_id, action_type, status_from, status_to, comment, action_by, metadata) 
                    VALUES 
                    (:service_record_id, :action_type, :status_from, :status_to, :comment, :action_by, :metadata)";
            
            $stmt = $db->prepare($sql);
            
            $params = [
                'service_record_id' => $serviceRecordId,
                'action_type' => $actionType,
                'status_from' => $statusFrom,
                'status_to' => $statusTo,
                'comment' => $comment,
                'action_by' => Session::get('user_id'),
                'metadata' => !empty($metadataArray) ? json_encode($metadataArray, JSON_UNESCAPED_UNICODE) : null
            ];
                        
            $result = $stmt->execute($params);
            
            if ($result) {
                $insertId = $db->lastInsertId();
                return true;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("✗ Failed to insert. Error: " . json_encode($errorInfo));
                return false;
            }
            
        } catch (Exception $e) {
            error_log('✗ Exception in addServiceRecordHistory: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * 交通費一覧表示
     */
    public function index() {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        $userId = Session::get('user_id');
        
        // 企業ユーザーは交通費を閲覧できない
        if ($userType === 'company') {
            $this->setFlash('error', '交通費情報は閲覧できません。');
            redirect('dashboard');
            return;
        }
        
        $travelExpenseModel = new TravelExpense();
        
        // フィルター条件を取得
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? 0); // 0 = 全月
        $status = trim($_GET['status'] ?? '');
        $contractId = (int)($_GET['contract_id'] ?? 0); // 管理者用
        $doctorId = (int)($_GET['doctor_id'] ?? 0); // 管理者用
        
        // 並び替え条件を取得
        $sortColumn = sanitize($_GET['sort'] ?? 'service_date');
        $sortOrder = sanitize($_GET['order'] ?? 'desc');

        // 許可された並び替え列
        $allowedSortColumns = ['service_date', 'amount', 'status', 'transport_type', 'company_name'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'service_date';
        }

        // 許可された並び順
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // ページネーション設定
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20); // デフォルト20件
        $allowedPerPage = [10, 20, 50, 100];
        
        // 1ページあたりの件数を制限
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 20;
        }
        
        // ページ番号の最小値チェック
        if ($page < 1) {
            $page = 1;
        }
        
        $offset = ($page - 1) * $perPage;
        
        if ($userType === 'doctor') {
            // 産業医の場合：自分の交通費のみ
            $expenses = $travelExpenseModel->findAllWithDetailsPaginated(
                $year, 
                $month > 0 ? $month : null, 
                $status, 
                $contractId > 0 ? $contractId : null, 
                $userId > 0 ? $userId : null,
                $perPage,
                $offset,
                $sortColumn,
                $sortOrder
            );
            
            // 総件数を取得
            $totalCount = $travelExpenseModel->countAllWithDetails(
                $year, 
                $month > 0 ? $month : null, 
                $status, 
                $contractId > 0 ? $contractId : null, 
                $userId > 0 ? $userId : null
            );
            
            $stats = $travelExpenseModel->getMonthlyStats($userId, $year, $month > 0 ? $month : date('n'));
            $doctors = [];
            
            // フィルター用データを取得
            $contracts = $travelExpenseModel->getContractsWithExpenses($userId);
        } else {
            // 管理者の場合：全ての交通費（フィルター適用）
            $expenses = $travelExpenseModel->findAllWithDetailsPaginated(
                $year, 
                $month > 0 ? $month : null, 
                $status, 
                $contractId > 0 ? $contractId : null, 
                $doctorId > 0 ? $doctorId : null,
                $perPage,
                $offset,
                $sortColumn,
                $sortOrder
            );
            
            // 総件数を取得
            $totalCount = $travelExpenseModel->countAllWithDetails(
                $year, 
                $month > 0 ? $month : null, 
                $status, 
                $contractId > 0 ? $contractId : null, 
                $doctorId > 0 ? $doctorId : null
            );
            
            // フィルター用データを取得
            $contracts = $travelExpenseModel->getContractsWithExpenses();
            $doctors = $travelExpenseModel->getDoctorsWithExpenses();
            
            // 管理者用統計
            $stats = $travelExpenseModel->getFilteredStats(
                $year,
                $month > 0 ? $month : null,
                $status,
                $contractId > 0 ? $contractId : null,
                $doctorId > 0 ? $doctorId : null
            );
        }
        
        // ページネーション計算
        $totalPages = ceil($totalCount / $perPage);
        $startRecord = $totalCount > 0 ? $offset + 1 : 0;
        $endRecord = min($offset + $perPage, $totalCount);
        
        // 現在のページが総ページ数を超えている場合の調整
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
            // データを再取得
            if ($userType === 'doctor') {
                $expenses = $travelExpenseModel->findAllWithDetailsPaginated(
                    $year, 
                    $month > 0 ? $month : null, 
                    $status, 
                    $contractId > 0 ? $contractId : null, 
                    $userId > 0 ? $userId : null,
                    $perPage,
                    $offset,
                    $sortColumn,
                    $sortOrder
                );
            } else {
                $expenses = $travelExpenseModel->findAllWithDetailsPaginated(
                    $year, 
                    $month > 0 ? $month : null, 
                    $status, 
                    $contractId > 0 ? $contractId : null, 
                    $doctorId > 0 ? $doctorId : null,
                    $perPage,
                    $offset,
                    $sortColumn,
                    $sortOrder
                );
            }
            $startRecord = $totalCount > 0 ? $offset + 1 : 0;
            $endRecord = min($offset + $perPage, $totalCount);
        }
        
        $data = [
            'expenses' => $expenses,
            'stats' => $stats,
            'year' => $year,
            'month' => $month,
            'status' => $status,
            'contractId' => $contractId,
            'doctorId' => $doctorId,
            'contracts' => $contracts,
            'doctors' => $doctors,
            'userType' => $userType,
            // ページネーション関連
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'allowedPerPage' => $allowedPerPage,
            // 並び替え関連を追加
            'sortColumn' => $sortColumn,
            'sortOrder' => $sortOrder
        ];
        
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/travel_expenses/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 交通費一括承認処理
     */
    public function bulkApprove() {
        Session::requireUserType(['admin']);
        
        if (!$this->validateCsrf()) {
            error_log('CSRF validation failed');
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('dashboard');
            return;
        }
        
        $expenseIds = $_POST['expense_ids'] ?? [];
        $comment = sanitize($_POST['comment'] ?? '');
        $approvedBy = Session::get('user_id');
        
        if (empty($expenseIds) || !is_array($expenseIds)) {
            error_log('No expense IDs provided or not array');
            $this->setFlash('error', '承認する交通費が選択されていません。');
            redirect('dashboard');
            return;
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $db->beginTransaction();
        
        try {
            $travelExpenseModel = new TravelExpense();
            $successCount = 0;
            $errorExpenses = [];
            
            foreach ($expenseIds as $expenseId) {
                $expenseId = (int)$expenseId;
                            
                // 交通費記録を取得
                $expense = $travelExpenseModel->findById($expenseId);
                if (!$expense) {
                    $errorExpenses[] = "交通費ID {$expenseId}: 記録が見つかりません";
                    error_log("Expense not found: $expenseId");
                    continue;
                }
                
                // 承認可能な状態かチェック
                if ($expense['status'] !== 'pending') {
                    $errorExpenses[] = "交通費ID {$expenseId}: 承認待ち以外の記録は承認できません";
                    error_log("Expense not pending: $expenseId, status: " . $expense['status']);
                    continue;
                }
                
                $oldStatus = $expense['status'];
                
                // 承認処理
                $updateData = [
                    'status' => 'approved',
                    'admin_comment' => $comment,
                    'approved_at' => date('Y-m-d H:i:s'),
                    'approved_by' => $approvedBy
                ];
                
                if ($travelExpenseModel->update($expenseId, $updateData)) {
                    $successCount++;
                    
                    // service_record_historyに履歴を追加（★DB接続を渡す★）
                    $historyResult = $this->addServiceRecordHistory(
                        $expense['service_record_id'],
                        $expenseId,
                        'travel_expense_approved',
                        $oldStatus,
                        'approved',
                        $comment ? "交通費承認: {$comment}" : '交通費承認',
                        [
                            'amount' => $expense['amount'],
                            'transport_type' => $expense['transport_type'],
                            'action' => 'approved'
                        ],
                        $db  // ★DB接続を渡す★
                    );
                    
                    if (!$historyResult) {
                        error_log("✗ Warning: History insert failed for expense {$expenseId}");
                    }
                } else {
                    $errorExpenses[] = "交通費ID {$expenseId}: 承認処理に失敗しました";
                    error_log("Failed to update expense: $expenseId");
                }
            }
            
            $db->commit();
            
            // 結果メッセージ
            if ($successCount > 0) {
                $message = "{$successCount}件の交通費を一括承認しました。";
                if (!empty($errorExpenses)) {
                    $message .= " ただし、" . count($errorExpenses) . "件でエラーが発生しました。";
                }
                $this->setFlash('success', $message);
            } else {
                $this->setFlash('error', '承認できる交通費がありませんでした。');
            }
            
            // エラーがあった場合は詳細を表示
            if (!empty($errorExpenses)) {
                foreach (array_slice($errorExpenses, 0, 5) as $error) {
                    $this->setFlash('warning', $error);
                }
            }
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('Bulk approve travel expenses failed: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->setFlash('error', '一括承認処理中にエラーが発生しました。');
        }
        
        redirect('dashboard');
    }

    /**
     * 交通費登録フォーム表示
     */
    public function create($serviceRecordId = null) {
        Session::requireUserType('doctor');

        if (!$serviceRecordId) {
            $this->setFlash('error', '役務記録が指定されていません。');
            redirect('service_records');
            return;
        }
        
        // 役務記録の詳細を取得
        $serviceModel = new ServiceRecord();
        $serviceRecord = $serviceModel->findById($serviceRecordId);
        
        if (!$serviceRecord) {
            $this->setFlash('error', '役務記録が見つかりません。');
            redirect('service_records');
            return;
        }
        
        // 権限チェック：自分の役務記録のみ
        if ($serviceRecord['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('service_records');
            return;
        }
        
        // 契約情報を取得（taxi_allowedが必要）
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $contractSql = "SELECT co.*, c.name as company_name, b.name as branch_name
                    FROM contracts co
                    JOIN companies c ON co.company_id = c.id
                    JOIN branches b ON co.branch_id = b.id
                    JOIN service_records sr ON sr.contract_id = co.id
                    WHERE sr.id = :service_record_id";
        
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['service_record_id' => $serviceRecordId]);
        $contractInfo = $stmt->fetch();
        
        if ($contractInfo) {
            // 役務記録に契約情報を追加
            $serviceRecord['taxi_allowed'] = $contractInfo['taxi_allowed'];
            $serviceRecord['contract_id'] = $contractInfo['id']; // 契約IDを追加
        }

        // 既存の交通費記録を取得
        $travelExpenseModel = new TravelExpense();
        $existingExpenses = $travelExpenseModel->findByServiceRecord($serviceRecordId);

        // テンプレート変数を確実に初期化
        $contractTemplates = []; // 従来の変数を契約特化版に変更
        $allTemplates = [];

        try {
            require_once __DIR__ . '/../models/TravelExpenseTemplate.php';
            $templateModel = new TravelExpenseTemplate();
            $doctorId = Session::get('user_id');
            
            // この契約で使われたことのあるテンプレートを取得
            if (!empty($serviceRecord['contract_id'])) {
                $contractTemplates = $templateModel->getRecommendedForContract(
                    $doctorId, 
                    $serviceRecord['contract_id'], 
                    $serviceRecordId, 
                    10
                );
            }
            
            // 全テンプレートも取得（モーダル用）
            $allTemplates = $templateModel->findByDoctor($doctorId);
            
        } catch (Exception $e) {
            error_log('Template loading error: ' . $e->getMessage());
        }

        $data = [
            'serviceRecord' => $serviceRecord,
            'existingExpenses' => $existingExpenses,
            'contractTemplates' => $contractTemplates, // 名前変更
            'allTemplates' => $allTemplates
        ];
        
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/travel_expenses/create.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 交通費登録処理
     */
    public function store() {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('service_records');
            return;
        }
        
        // 入力データを取得
        $data = [
            'service_record_id' => (int)($_POST['service_record_id'] ?? 0),
            'doctor_id' => Session::get('user_id'),
            'transport_type' => sanitize($_POST['transport_type'] ?? 'train'),
            'departure_point' => sanitize($_POST['departure_point'] ?? ''),
            'arrival_point' => sanitize($_POST['arrival_point'] ?? ''),
            'trip_type' => sanitize($_POST['trip_type'] ?? ''),
            'amount' => (int)($_POST['amount'] ?? 0),
            'memo' => sanitize($_POST['memo'] ?? ''),
            'status' => 'pending'
        ];
        
        // ★ 新規追加：企業にお伝え済みフラグ
        $data['company_notified'] = isset($_POST['company_notified']) ? 1 : 0;
        
        // trip_typeが空文字列の場合はNULLに変換
        if (empty($data['trip_type'])) {
            $data['trip_type'] = null;
        }

        // テンプレート関連の入力
        $saveAsTemplate = isset($_POST['save_as_template']) && $_POST['save_as_template'] == '1';
        $customTemplateName = sanitize($_POST['custom_template_name'] ?? '');
        $usedTemplateId = (int)($_POST['used_template_id'] ?? 0);
        
        // 役務記録の権限チェックと契約情報の取得
        $serviceModel = new ServiceRecord();
        $serviceRecord = $serviceModel->findById($data['service_record_id']);
        
        if (!$serviceRecord) {
            $this->setFlash('error', '役務記録が見つかりません。');
            redirect('service_records');
            return;
        } elseif ($serviceRecord['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('service_records');
            return;
        }
        
        // ★ 契約情報を取得してタクシー利用可否を確認
        $taxiAllowed = isset($serviceRecord['taxi_allowed']) ? (bool)$serviceRecord['taxi_allowed'] : false;
        
        // バリデーション（契約情報を渡す）
        $errors = $this->validateTravelExpenseData($data, true, $taxiAllowed, $_FILES['receipt_file'] ?? null);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect("travel_expenses/create/{$data['service_record_id']}");
            return;
        }
        
        // ファイルアップロード処理
        $uploadedFile = $_FILES['receipt_file'] ?? null;
        if ($uploadedFile && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE && $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'ファイルのアップロードでエラーが発生しました。');
            redirect("travel_expenses/create/{$data['service_record_id']}");
            return;
        }
        
        // 交通費記録を作成
        $travelExpenseModel = new TravelExpense();
        $expenseId = $travelExpenseModel->createWithFile($data, $uploadedFile);
        
        if ($expenseId) {
            // テンプレート処理
            require_once __DIR__ . '/../models/TravelExpenseTemplate.php';
            $templateModel = new TravelExpenseTemplate();
            
            if ($usedTemplateId > 0) {
                // 既存テンプレートを使用した場合、使用回数を更新
                $templateModel->updateUsage($usedTemplateId, $data['amount']);
            } elseif ($saveAsTemplate) {
                // 新規テンプレートとして保存
                $templateModel->createFromExpense($data, $customTemplateName);
            }
            
            $this->setFlash('success', '交通費を登録しました。');
            redirect("service_records/{$data['service_record_id']}");
        } else {
            $this->setFlash('error', '交通費の登録に失敗しました。');
            redirect("travel_expenses/create/{$data['service_record_id']}");
        }
    }
    
    /**
     * 交通費詳細表示
     */
    public function show($id) {
        Session::requireLogin();
        
        $userType = Session::get('user_type');
        
        // 企業ユーザーは交通費を閲覧できない
        if ($userType === 'company') {
            $this->setFlash('error', '交通費情報は閲覧できません。');
            redirect('dashboard');
            return;
        }
        
        $travelExpenseModel = new TravelExpense();
        $expense = $travelExpenseModel->findByIdWithDetails($id);
        
        if (!$expense) {
            $this->setFlash('error', '交通費記録が見つかりません。');
            redirect('travel_expenses');
            return;
        }
        
        // 権限チェック（産業医は自分の記録のみ閲覧可能）
        if ($userType === 'doctor' && $expense['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('travel_expenses');
            return;
        }
        
        $data = [
            'expense' => $expense,
            'userType' => $userType
        ];
        
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/travel_expenses/show.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 交通費編集フォーム表示
     */
    public function edit($id) {
        Session::requireUserType('doctor');
        
        $travelExpenseModel = new TravelExpense();
        $expense = $travelExpenseModel->findByIdWithDetails($id);
        
        if (!$expense) {
            $this->setFlash('error', '交通費記録が見つかりません。');
            redirect('travel_expenses');
            return;
        }
        
        // 権限チェック：自分の記録のみ編集可能
        if ($expense['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('travel_expenses');
            return;
        }
        
        // 承認済みの記録は編集不可
        if ($expense['status'] === 'approved') {
            $this->setFlash('error', '承認済みの交通費記録は編集できません。');
            redirect('travel_expenses');
            return;
        }

        // 契約情報を取得（taxi_allowedが必要）
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $contractSql = "SELECT co.taxi_allowed, co.id as contract_id
                    FROM contracts co
                    JOIN service_records sr ON sr.contract_id = co.id
                    WHERE sr.id = :service_record_id";
        
        $stmt = $db->prepare($contractSql);
        $stmt->execute(['service_record_id' => $expense['service_record_id']]);
        $contractInfo = $stmt->fetch();
        
        if ($contractInfo) {
            // 交通費記録に契約情報を追加
            $expense['taxi_allowed'] = $contractInfo['taxi_allowed'];
            $expense['contract_id'] = $contractInfo['contract_id'];
        }

        // テンプレート変数を確実に初期化
        $contractTemplates = [];
        $allTemplates = [];

        try {
            require_once __DIR__ . '/../models/TravelExpenseTemplate.php';
            $templateModel = new TravelExpenseTemplate();
            $doctorId = Session::get('user_id');
            
            // この契約で使われたことのあるテンプレートを取得
            if (!empty($expense['contract_id'])) {
                $contractTemplates = $templateModel->getRecommendedForContract(
                    $doctorId, 
                    $expense['contract_id'], 
                    $expense['service_record_id'], 
                    10
                );
            }
            
            $allTemplates = $templateModel->findByDoctor($doctorId);
            
        } catch (Exception $e) {
            error_log('Template loading error in edit: ' . $e->getMessage());
        }

        $data = [
            'expense' => $expense,
            'contractTemplates' => $contractTemplates,  // 名前変更
            'allTemplates' => $allTemplates
        ];
        
        extract($data);
        
        ob_start();
        include __DIR__ . '/../views/travel_expenses/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 交通費更新処理
     */
    public function update($id) {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('travel_expenses');
            return;
        }
        
        $travelExpenseModel = new TravelExpense();
        $existingExpense = $travelExpenseModel->findByIdWithDetails($id);
        
        if (!$existingExpense) {
            $this->setFlash('error', '交通費記録が見つかりません。');
            redirect('travel_expenses');
            return;
        }
        
        // 権限チェック：自分の記録のみ編集可能
        if ($existingExpense['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('travel_expenses');
            return;
        }
        
        // ステータスチェック：承認済み・確定済みは編集不可
        if (in_array($existingExpense['status'], ['approved', 'finalized'])) {
            $this->setFlash('error', '承認済みまたは確定済みの記録は編集できません。');
            redirect("travel_expenses/{$id}");
            return;
        }
        
        // 入力データを取得
        $data = [
            'transport_type' => sanitize($_POST['transport_type'] ?? 'train'),
            'departure_point' => sanitize($_POST['departure_point'] ?? ''),
            'arrival_point' => sanitize($_POST['arrival_point'] ?? ''),
            'trip_type' => sanitize($_POST['trip_type'] ?? ''),
            'amount' => (int)($_POST['amount'] ?? 0),
            'memo' => sanitize($_POST['memo'] ?? '')
        ];
        
        // ★ 新規追加：企業にお伝え済みフラグ
        $data['company_notified'] = isset($_POST['company_notified']) ? 1 : 0;
        
        // trip_typeが空文字列の場合はNULLに変換
        if (empty($data['trip_type'])) {
            $data['trip_type'] = null;
        }
        
        // ★ 契約情報を取得してタクシー利用可否を確認
        $taxiAllowed = isset($existingExpense['taxi_allowed']) ? (bool)$existingExpense['taxi_allowed'] : false;
        
        // バリデーション（契約情報を渡す）
        $errors = $this->validateTravelExpenseData($data, false, $taxiAllowed, $_FILES['receipt_file'] ?? null);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            redirect("travel_expenses/{$id}/edit");
            return;
        }
        
        // ファイルアップロード処理
        $uploadedFile = $_FILES['receipt_file'] ?? null;
        $deleteFile = isset($_POST['delete_receipt']) && $_POST['delete_receipt'] == '1';
        
        if ($uploadedFile && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE && $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'ファイルのアップロードでエラーが発生しました。');
            redirect("travel_expenses/{$id}/edit");
            return;
        }
        
        // 交通費記録を更新
        if ($travelExpenseModel->updateWithFile($id, $data, $uploadedFile, $deleteFile)) {
            $this->setFlash('success', '交通費情報を更新しました。');
            redirect("service_records/{$existingExpense['service_record_id']}");
        } else {
            $this->setFlash('error', '交通費情報の更新に失敗しました。');
            redirect("travel_expenses/{$id}/edit");
        }
    }
    
    /**
     * 交通費削除処理
     */
    public function delete($id) {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('travel_expenses');
            return;
        }
        
        $travelExpenseModel = new TravelExpense();
        $expense = $travelExpenseModel->findById($id);
        
        if (!$expense) {
            $this->setFlash('error', '交通費記録が見つかりません。');
            redirect('travel_expenses');
            return;
        }
        
        // 権限チェック：自分の記録のみ削除可能
        if ($expense['doctor_id'] != Session::get('user_id')) {
            $this->setFlash('error', '権限がありません。');
            redirect('travel_expenses');
            return;
        }
        
        // 承認済みの記録は削除不可
        if ($expense['status'] === 'approved') {
            $this->setFlash('error', '承認済みの交通費記録は削除できません。');
            redirect('travel_expenses');
            return;
        }
        
        if ($travelExpenseModel->deleteWithFile($id)) {
            $this->setFlash('success', '交通費記録を削除しました。');
        } else {
            $this->setFlash('error', '交通費記録の削除に失敗しました。');
        }
        
        redirect('travel_expenses');
    }
    
    /**
     * 管理者用：交通費承認処理
     */
    public function approve($id) {
        Session::requireUserType(['admin']);
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            $this->redirectToAppropriateLocation($id);
            return;
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            $travelExpenseModel = new TravelExpense();
            $expense = $travelExpenseModel->findById($id);
        
            if (!$expense) {
                $db->rollback();
                $this->setFlash('error', '交通費記録が見つかりません。');
                $this->redirectToAppropriateLocation($id);
                return;
            }
            
            if ($expense['status'] !== 'pending') {
                $db->rollback();
                $this->setFlash('error', '承認待ち以外の交通費は承認できません。');
                $this->redirectToAppropriateLocation($id);
                return;
            }
        
            $comment = sanitize($_POST['comment'] ?? '');
            $approvedBy = Session::get('user_id');
            $oldStatus = $expense['status'];
            
            $updateData = [
                'status' => 'approved',
                'admin_comment' => $comment,
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $approvedBy
            ];
            
            if ($travelExpenseModel->update($id, $updateData)) {
                
                // service_record_historyに履歴を追加（★DB接続を渡す★）
                $historyResult = $this->addServiceRecordHistory(
                    $expense['service_record_id'],
                    $id,
                    'travel_expense_approved',
                    $oldStatus,
                    'approved',
                    $comment ? "交通費承認: {$comment}" : '交通費承認',
                    [
                        'amount' => $expense['amount'],
                        'transport_type' => $expense['transport_type'],
                        'action' => 'approved'
                    ],
                    $db  // ★DB接続を渡す★
                );
                
                if (!$historyResult) {
                    error_log("✗ Warning: History insert failed but continuing");
                }
                
                $db->commit();
                
                $this->setFlash('success', '交通費を承認しました。');
                
                // セキュリティログ
                $this->logSecurityEvent('travel_expense_approved', [
                    'expense_id' => $id,
                    'doctor_id' => $expense['doctor_id'],
                    'amount' => $expense['amount'],
                    'comment' => $comment
                ]);
            } else {
                $db->rollback();
                error_log("✗ Failed to update travel expense {$id}");
                $this->setFlash('error', '交通費の承認に失敗しました。');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('✗ Exception in approve: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->setFlash('error', '承認処理中にエラーが発生しました。');
        }
        
        $this->redirectToAppropriateLocation($id);
    }
    
    /**
     * 管理者用：交通費差戻し処理
     */
    public function reject($id) {
        Session::requireUserType(['admin']);
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            $this->redirectToAppropriateLocation($id);
            return;
        }
        
        $comment = sanitize($_POST['comment'] ?? '');
        
        if (empty($comment)) {
            $this->setFlash('error', '差戻し理由を入力してください。');
            $this->redirectToAppropriateLocation($id);
            return;
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            $travelExpenseModel = new TravelExpense();
            $expense = $travelExpenseModel->findById($id);
            
            if (!$expense) {
                $db->rollback();
                $this->setFlash('error', '交通費記録が見つかりません。');
                $this->redirectToAppropriateLocation($id);
                return;
            }
            
            if ($expense['status'] !== 'pending') {
                $db->rollback();
                $this->setFlash('error', '承認待ち以外の交通費は差戻しできません。');
                $this->redirectToAppropriateLocation($id);
                return;
            }
            
            $rejectedBy = Session::get('user_id');
            $oldStatus = $expense['status'];
            
            $updateData = [
                'status' => 'rejected',
                'admin_comment' => $comment,
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejected_by' => $rejectedBy
            ];
            
            if ($travelExpenseModel->update($id, $updateData)) {
                
                // service_record_historyに履歴を追加（★DB接続を渡す★）
                $historyResult = $this->addServiceRecordHistory(
                    $expense['service_record_id'],
                    $id,
                    'travel_expense_rejected',
                    $oldStatus,
                    'rejected',
                    "交通費差戻し: {$comment}",
                    [
                        'amount' => $expense['amount'],
                        'transport_type' => $expense['transport_type'],
                        'action' => 'rejected'
                    ],
                    $db  // ★DB接続を渡す★
                );
                
                if (!$historyResult) {
                    error_log("✗ Warning: History insert failed but continuing");
                }
                
                $db->commit();
                
                $this->setFlash('success', '交通費を差戻しました。');
                
                // セキュリティログ
                $this->logSecurityEvent('travel_expense_rejected', [
                    'expense_id' => $id,
                    'doctor_id' => $expense['doctor_id'],
                    'amount' => $expense['amount'],
                    'comment' => $comment
                ]);
            } else {
                $db->rollback();
                error_log("✗ Failed to update travel expense {$id}");
                $this->setFlash('error', '交通費の差戻しに失敗しました。');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('✗ Exception in reject: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->setFlash('error', '差戻し処理中にエラーが発生しました。');
        }
        
        $this->redirectToAppropriateLocation($id);
    }


    /**
     * 管理者用:交通費承認取り消し処理
     */
    public function unapprove($id) {
        Session::requireUserType(['admin']);
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            $this->redirectToAppropriateLocation($id);
            return;
        }
        
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            $travelExpenseModel = new TravelExpense();
            $expense = $travelExpenseModel->findById($id);
            
            if (!$expense) {
                $db->rollback();
                $this->setFlash('error', '交通費記録が見つかりません。');
                $this->redirectToAppropriateLocation($id);
                return;
            }
            
            if ($expense['status'] !== 'approved') {
                $db->rollback();
                $this->setFlash('error', '承認済み以外の交通費は承認取り消しできません。');
                $this->redirectToAppropriateLocation($id);
                return;
            }
            
            $unapprovedBy = Session::get('user_id');
            $oldStatus = $expense['status'];
            
            $updateData = [
                'status' => 'pending',
                'admin_comment' => null,
                'approved_at' => null,
                'approved_by' => null,
                'unapproved_at' => date('Y-m-d H:i:s'),
                'unapproved_by' => $unapprovedBy
            ];
            
            if ($travelExpenseModel->update($id, $updateData)) {
                
                // service_record_historyに履歴を追加（★DB接続を渡す★）
                $historyResult = $this->addServiceRecordHistory(
                    $expense['service_record_id'],
                    $id,
                    'travel_expense_unapproved',
                    $oldStatus,
                    'pending',
                    '交通費承認取り消し',
                    [
                        'amount' => $expense['amount'],
                        'transport_type' => $expense['transport_type'],
                        'action' => 'unapproved'
                    ],
                    $db  // ★DB接続を渡す★
                );
                
                if (!$historyResult) {
                    error_log("✗ Warning: History insert failed but continuing");
                }
                
                $db->commit();
                
                $this->setFlash('success', '交通費の承認を取り消しました。');
                
                // セキュリティログ
                $this->logSecurityEvent('travel_expense_unapproved', [
                    'expense_id' => $id,
                    'doctor_id' => $expense['doctor_id'],
                    'amount' => $expense['amount']
                ]);
            } else {
                $db->rollback();
                error_log("✗ Failed to unapprove travel expense {$id}");
                $this->setFlash('error', '交通費の承認取り消しに失敗しました。');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('✗ Exception in unapprove: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->setFlash('error', '承認取り消し処理中にエラーが発生しました。');
        }
        
        $this->redirectToAppropriateLocation($id);
    }

    /**
     * 適切なリダイレクト先を決定してリダイレクト
     */
    private function redirectToAppropriateLocation($expenseId = null) {
        // HTTPリファラーを取得
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // リファラーからパス部分を抽出
        if (!empty($referer)) {
            $parsedUrl = parse_url($referer);
            $refererPath = $parsedUrl['path'] ?? '';
            $refererQuery = $parsedUrl['query'] ?? '';
            
            // 交通費詳細ページからの場合
            if (preg_match('#/travel_expenses/(\d+)$#', $refererPath) && $expenseId) {
                redirect("travel_expenses/$expenseId");
                return;
            }
            
            // 交通費一覧ページからの場合
            if (preg_match('#/travel_expenses#', $refererPath)) {
                // クエリパラメータを保持したままリダイレクト
                if (!empty($refererQuery)) {
                    redirect("travel_expenses?$refererQuery");
                } else {
                    redirect('travel_expenses');
                }
                return;
            }
            
            // ダッシュボードからの場合
            if (preg_match('#/dashboard#', $refererPath) || preg_match('#/$#', $refererPath)) {
                redirect('dashboard');
                return;
            }
        }
        
        // デフォルトはダッシュボードに戻る
        redirect('dashboard');
    }
    
    /**
     * 交通費データのバリデーション
     */
    private function validateTravelExpenseData($data, $includeServiceRecord = true, $taxiAllowed = false, $uploadedFile = null) {
        $errors = [];
        
        if ($includeServiceRecord && empty($data['service_record_id'])) {
            $errors[] = '役務記録が指定されていません。';
        }
        
        if (!in_array($data['transport_type'], ['train', 'bus', 'taxi', 'gasoline', 'highway_toll', 'parking', 'rental_car', 'airplane', 'other'])) {
            $errors[] = '交通手段を正しく選択してください。';
        }
        
        // ガソリン、駐車料金、レンタカーの場合は地点と往復・片道は不要
        $hideRouteTypes = ['gasoline', 'parking', 'rental_car'];
        $requireRoute = !in_array($data['transport_type'], $hideRouteTypes);
        
        if ($requireRoute) {
            if (empty($data['departure_point'])) {
                $errors[] = '出発地点を入力してください。';
            }
            
            if (empty($data['arrival_point'])) {
                $errors[] = '到着地点を入力してください。';
            }
            
            if (!in_array($data['trip_type'], ['round_trip', 'one_way'])) {
                $errors[] = '往復・片道を正しく選択してください。';
            }
        }
        
        if ($data['amount'] <= 0) {
            $errors[] = '金額は1円以上で入力してください。';
        }
        
        if ($data['amount'] > 999999) {
            $errors[] = '金額は999,999円以下で入力してください。';
        }
        
        // ★ 新規追加：タクシー利用不可契約でタクシーを選択した場合の追加バリデーション
        if ($data['transport_type'] === 'taxi' && !$taxiAllowed) {
            // ①メモ・備考を必須とする
            if (empty($data['memo']) || trim($data['memo']) === '') {
                $errors[] = 'タクシー利用不可の契約でタクシーを利用する場合、メモ・備考に理由の記載が必須です。';
            }
            
            // ②領収書のアップロードを必須とする
            $hasReceiptFile = false;
            
            // 新規登録の場合：アップロードファイルをチェック
            if ($includeServiceRecord) {
                if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                    $hasReceiptFile = true;
                }
            } else {
                // 編集の場合：既存ファイルまたは新規アップロードをチェック
                // 既存ファイルがあるかは呼び出し側で確認する必要があるため、ここではアップロードのみチェック
                if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                    $hasReceiptFile = true;
                }
                // 注意：編集時に既存の領収書がある場合は、別途チェックが必要
                // これはコントローラー側で対応します
            }
            
            if (!$hasReceiptFile && $includeServiceRecord) {
                $errors[] = 'タクシー利用不可の契約でタクシーを利用する場合、領収書のアップロードが必須です。';
            }
            
            // ③「企業にお伝え済み」チェックをONにすることを必須とする
            if (empty($data['company_notified'])) {
                $errors[] = 'タクシー利用不可の契約でタクシーを利用する場合、「企業にお伝え済み」チェックが必須です。';
            }
        }
        
        return $errors;
    }

    /**
     * API: 役務記録IDで交通費一覧を取得
     */
    public function getByServiceRecord($serviceRecordId) {
        Session::requireLogin();
        
        header('Content-Type: application/json');
        
        try {
            $userType = Session::get('user_type');
            $userId = Session::get('user_id');
            
            // 企業ユーザーは交通費を閲覧できない
            if ($userType === 'company') {
                echo json_encode([
                    'success' => false,
                    'error' => '交通費情報は閲覧できません。'
                ]);
                return;
            }
            
            // 役務記録の存在と権限をチェック
            $serviceModel = new ServiceRecord();
            $serviceRecord = $serviceModel->findById($serviceRecordId);
            
            if (!$serviceRecord) {
                echo json_encode([
                    'success' => false,
                    'error' => '役務記録が見つかりません。'
                ]);
                return;
            }
            
            // 権限チェック（産業医は自分の記録のみ閲覧可能）
            if ($userType === 'doctor' && $serviceRecord['doctor_id'] != $userId) {
                echo json_encode([
                    'success' => false,
                    'error' => '権限がありません。'
                ]);
                return;
            }
            
            // 交通費一覧を取得
            $travelExpenseModel = new TravelExpense();
            $expenses = $travelExpenseModel->findByServiceRecord($serviceRecordId);
            
            echo json_encode([
                'success' => true,
                'data' => $expenses,
                'service_record' => [
                    'id' => $serviceRecord['id'],
                    'service_date' => $serviceRecord['service_date'],
                    'company_name' => $serviceRecord['company_name'],
                    'branch_name' => $serviceRecord['branch_name']
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Get travel expenses by service record failed: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました。'
            ]);
        }
    }

    /**
     * API: 契約IDで交通費一覧を取得（管理者用）
     */
    public function getByContract($contractId) {
        Session::requireUserType(['admin', 'doctor']);
        
        header('Content-Type: application/json');
        
        try {
            $userType = Session::get('user_type');
            $userId = Session::get('user_id');
            
            $year = (int)($_GET['year'] ?? date('Y'));
            $month = (int)($_GET['month'] ?? date('n'));
            
            require_once __DIR__ . '/../core/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // 契約の存在確認
            $contractCheckSql = "SELECT co.*, u.name as doctor_name, c.name as company_name, b.name as branch_name
                               FROM contracts co
                               JOIN users u ON co.doctor_id = u.id 
                               JOIN companies c ON co.company_id = c.id
                               JOIN branches b ON co.branch_id = b.id
                               WHERE co.id = :contract_id";
            
            $stmt = $db->prepare($contractCheckSql);
            $stmt->execute(['contract_id' => $contractId]);
            $contract = $stmt->fetch();
            
            if (!$contract) {
                echo json_encode([
                    'success' => false,
                    'error' => '契約が見つかりません。'
                ]);
                return;
            }
            
            // 権限チェック（産業医は自分の契約のみ閲覧可能）
            if ($userType === 'doctor' && $contract['doctor_id'] != $userId) {
                echo json_encode([
                    'success' => false,
                    'error' => '権限がありません。'
                ]);
                return;
            }
            
            // 交通費一覧を取得（役務記録情報付き）
            $expensesSql = "SELECT te.*, 
                                sr.service_date, 
                                sr.service_type,
                                sr.start_time,
                                sr.end_time,
                                sr.service_hours,
                                u.name as doctor_name,
                                c.name as company_name,
                                b.name as branch_name
                          FROM travel_expenses te
                          JOIN service_records sr ON te.service_record_id = sr.id
                          JOIN contracts co ON sr.contract_id = co.id
                          JOIN users u ON sr.doctor_id = u.id
                          JOIN companies c ON co.company_id = c.id
                          JOIN branches b ON co.branch_id = b.id
                          WHERE sr.contract_id = :contract_id
                          AND YEAR(sr.service_date) = :year 
                          AND MONTH(sr.service_date) = :month
                          ORDER BY sr.service_date DESC, te.created_at DESC";
            
            $stmt = $db->prepare($expensesSql);
            $stmt->execute([
                'contract_id' => $contractId,
                'year' => $year,
                'month' => $month
            ]);
            $expenses = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $expenses,
                'contract' => $contract,
                'summary' => [
                    'total_count' => count($expenses),
                    'total_amount' => array_sum(array_column($expenses, 'amount')),
                    'pending_count' => count(array_filter($expenses, fn($e) => $e['status'] === 'pending')),
                    'approved_count' => count(array_filter($expenses, fn($e) => $e['status'] === 'approved')),
                    'rejected_count' => count(array_filter($expenses, fn($e) => $e['status'] === 'rejected'))
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Get travel expenses by contract failed: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました。'
            ]);
        }
    }

    /**
     * API: 交通費詳細を取得
     */
    public function getDetail($id) {
        Session::requireLogin();
        
        header('Content-Type: application/json');
        
        try {
            $userType = Session::get('user_type');
            $userId = Session::get('user_id');
            
            // 企業ユーザーは交通費を閲覧できない
            if ($userType === 'company') {
                echo json_encode([
                    'success' => false,
                    'error' => '交通費情報は閲覧できません。'
                ]);
                return;
            }
            
            $travelExpenseModel = new TravelExpense();
            $expense = $travelExpenseModel->findByIdWithDetails($id);
            
            if (!$expense) {
                echo json_encode([
                    'success' => false,
                    'error' => '交通費記録が見つかりません。'
                ]);
                return;
            }
            
            // 権限チェック（産業医は自分の記録のみ閲覧可能）
            if ($userType === 'doctor' && $expense['doctor_id'] != $userId) {
                echo json_encode([
                    'success' => false,
                    'error' => '権限がありません。'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $expense
            ]);
            
        } catch (Exception $e) {
            error_log('Get travel expense detail failed: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました。'
            ]);
        }
    }

    /**
     * API: 交通費テンプレート一覧取得
     */
    public function getTemplates() {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../models/TravelExpenseTemplate.php';
            $templateModel = new TravelExpenseTemplate();
            $doctorId = Session::get('user_id');
            
            $type = $_GET['type'] ?? 'all';
            
            switch ($type) {
                case 'frequent':
                    $templates = $templateModel->getFrequentlyUsed($doctorId, 10);
                    break;
                case 'recent':
                    $templates = $templateModel->getRecentlyUsed($doctorId, 10);
                    break;
                default:
                    $templates = $templateModel->findByDoctor($doctorId);
                    break;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $templates
            ]);
            
        } catch (Exception $e) {
            error_log('Get travel expense templates failed: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました。'
            ]);
        }
    }

    /**
     * API: テンプレート詳細取得
     */
    public function getTemplate($id) {
        Session::requireUserType('doctor');
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../models/TravelExpenseTemplate.php';
            $templateModel = new TravelExpenseTemplate();
            $template = $templateModel->findById($id);
            
            if (!$template || $template['doctor_id'] != Session::get('user_id')) {
                echo json_encode([
                    'success' => false,
                    'error' => 'テンプレートが見つかりません。'
                ]);
                return;
            }
                 
            echo json_encode([
                'success' => true,
                'data' => $template
            ]);
            
        } catch (Exception $e) {
            error_log('Get travel expense template failed: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました。'
            ]);
        }
    }

    /**
     * テンプレート削除
     */
    public function deleteTemplate($id) {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'セキュリティエラーが発生しました。');
            redirect('travel_expenses');
            return;
        }
        
        try {
            require_once __DIR__ . '/../models/TravelExpenseTemplate.php';
            $templateModel = new TravelExpenseTemplate();
            $doctorId = Session::get('user_id');
            
            if ($templateModel->softDelete($id, $doctorId)) {
                $this->setFlash('success', 'テンプレートを削除しました。');
            } else {
                $this->setFlash('error', 'テンプレートの削除に失敗しました。');
            }
            
        } catch (Exception $e) {
            error_log('Delete travel expense template failed: ' . $e->getMessage());
            $this->setFlash('error', 'システムエラーが発生しました。');
        }
        
        redirect('travel_expenses');
    }

    /**
     * テンプレート名変更
     */
    public function updateTemplateName($id) {
        Session::requireUserType('doctor');
        
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'セキュリティエラーが発生しました。'
            ]);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            $newName = sanitize($_POST['template_name'] ?? '');
            
            if (empty($newName)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'テンプレート名を入力してください。'
                ]);
                return;
            }
            
            require_once __DIR__ . '/../models/TravelExpenseTemplate.php';
            $templateModel = new TravelExpenseTemplate();
            $doctorId = Session::get('user_id');
            
            if ($templateModel->updateTemplateName($id, $doctorId, $newName)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'テンプレート名を更新しました。'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'テンプレート名の更新に失敗しました。'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Update travel expense template name failed: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'システムエラーが発生しました。'
            ]);
        }
    }
}
?>