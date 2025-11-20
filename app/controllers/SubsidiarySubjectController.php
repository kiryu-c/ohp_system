<?php
// app/controllers/SubsidiarySubjectController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/SubsidiarySubject.php';

class SubsidiarySubjectController extends BaseController {
    
    /**
     * 一覧表示
     */
    public function index() {
        Session::requireUserType('admin');
        
        $subsidiarySubjectModel = new SubsidiarySubject();
        $subsidiarySubjects = $subsidiarySubjectModel->findAllOrdered();
        
        // ビューをレンダリング
        ob_start();
        include __DIR__ . '/../views/subsidiary_subjects/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 新規作成（Ajax）
     */
    public function createAjax() {
        Session::requireUserType('admin');
        
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // CSRFトークンの検証
            if (empty($input['csrf_token']) || !verify_csrf_token($input['csrf_token'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
                exit;
            }
            
            // バリデーション
            $errors = $this->validateSubsidiarySubject($input);
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => implode('\n', $errors)]);
                exit;
            }
            
            // データ準備
            $data = [
                'number' => (int)trim($input['number']),
                'name' => trim($input['name']),
                'is_active' => isset($input['is_active']) ? (int)$input['is_active'] : 1,
                'created_by' => Session::get('user_id')
            ];
            
            // 登録処理
            $subsidiarySubjectModel = new SubsidiarySubject();
            $id = $subsidiarySubjectModel->createSubsidiarySubject($data);
            
            if ($id) {
                echo json_encode([
                    'success' => true, 
                    'message' => '補助科目を登録しました。',
                    'id' => $id,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception('補助科目の登録に失敗しました。コードが重複している可能性があります。');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * 更新（Ajax）
     */
    public function updateAjax($id) {
        Session::requireUserType('admin');
        
        header('Content-Type: application/json');
        
        try {
            $subsidiarySubjectModel = new SubsidiarySubject();
            $subsidiarySubject = $subsidiarySubjectModel->findById($id);
            
            if (!$subsidiarySubject) {
                throw new Exception('補助科目が見つかりません。');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // CSRFトークンの検証
            if (empty($input['csrf_token']) || !verify_csrf_token($input['csrf_token'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
                exit;
            }
            
            // バリデーション
            $errors = $this->validateSubsidiarySubject($input, $id);
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => implode('\n', $errors)]);
                exit;
            }
            
            // データ準備
            $data = [
                'number' => (int)trim($input['number']),
                'name' => trim($input['name']),
                'is_active' => isset($input['is_active']) ? (int)$input['is_active'] : 1,
                'updated_by' => Session::get('user_id')
            ];
            
            // 更新処理
            if ($subsidiarySubjectModel->updateSubsidiarySubject($id, $data)) {
                echo json_encode([
                    'success' => true, 
                    'message' => '補助科目を更新しました。',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception('補助科目の更新に失敗しました。コードが重複している可能性があります。');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * 削除（Ajax）
     */
    public function deleteAjax($id) {
        Session::requireUserType('admin');
        
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // CSRFトークンの検証
            if (empty($input['csrf_token']) || !verify_csrf_token($input['csrf_token'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
                exit;
            }
            
            $subsidiarySubjectModel = new SubsidiarySubject();
            $subsidiarySubject = $subsidiarySubjectModel->findById($id);
            
            if (!$subsidiarySubject) {
                throw new Exception('補助科目が見つかりません。');
            }
            
            // 論理削除
            if ($subsidiarySubjectModel->deleteSubsidiarySubject($id)) {
                echo json_encode(['success' => true, 'message' => '補助科目を削除しました。']);
            } else {
                throw new Exception('補助科目の削除に失敗しました。');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * 新規作成画面
     */
    public function create() {
        Session::requireUserType('admin');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        // ビューをレンダリング
        ob_start();
        include __DIR__ . '/../views/subsidiary_subjects/create.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 新規作成処理
     */
    private function handleCreate() {
        try {
            // CSRFトークンの検証
            if (!$this->validateCsrf()) {
                throw new Exception('不正なリクエストです。');
            }
            
            // バリデーション
            $errors = $this->validateSubsidiarySubject($_POST);
            
            if (!empty($errors)) {
                Session::setFlash('error', implode('<br>', $errors));
                $input = $_POST;
                
                // ビューをレンダリング
                ob_start();
                include __DIR__ . '/../views/subsidiary_subjects/create.php';
                $content = ob_get_clean();
                
                include __DIR__ . '/../views/layouts/base.php';
                return;
            }
            
            // データ準備
            $data = [
                'number' => (int)trim($_POST['number']),
                'name' => trim($_POST['name']),
                'display_order' => isset($_POST['display_order']) && $_POST['display_order'] !== '' 
                    ? (int)$_POST['display_order'] 
                    : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => Session::get('user_id')
            ];
            
            // 登録処理
            $subsidiarySubjectModel = new SubsidiarySubject();
            $id = $subsidiarySubjectModel->createSubsidiarySubject($data);
            
            if ($id) {
                Session::setFlash('success', '補助科目を登録しました。');
                $this->redirect('subsidiary_subjects');
            } else {
                throw new Exception('補助科目の登録に失敗しました。コードが重複している可能性があります。');
            }
            
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            $input = $_POST;
            
            // ビューをレンダリング
            ob_start();
            include __DIR__ . '/../views/subsidiary_subjects/create.php';
            $content = ob_get_clean();
            
            include __DIR__ . '/../views/layouts/base.php';
        }
    }
    
    /**
     * 編集画面
     */
    public function edit($id) {
        Session::requireUserType('admin');
        
        $subsidiarySubjectModel = new SubsidiarySubject();
        $subsidiarySubject = $subsidiarySubjectModel->findById($id);
        
        if (!$subsidiarySubject) {
            Session::setFlash('error', '補助科目が見つかりません。');
            $this->redirect('subsidiary_subjects');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($id);
            return;
        }
        
        // ビューをレンダリング
        ob_start();
        include __DIR__ . '/../views/subsidiary_subjects/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    /**
     * 編集処理
     */
    private function handleEdit($id) {
        try {
            // CSRFトークンの検証
            if (!$this->validateCsrf()) {
                throw new Exception('不正なリクエストです。');
            }
            
            $subsidiarySubjectModel = new SubsidiarySubject();
            $subsidiarySubject = $subsidiarySubjectModel->findById($id);
            
            if (!$subsidiarySubject) {
                throw new Exception('補助科目が見つかりません。');
            }
            
            // バリデーション
            $errors = $this->validateSubsidiarySubject($_POST, $id);
            
            if (!empty($errors)) {
                Session::setFlash('error', implode('<br>', $errors));
                
                // ビューをレンダリング
                ob_start();
                include __DIR__ . '/../views/subsidiary_subjects/edit.php';
                $content = ob_get_clean();
                
                include __DIR__ . '/../views/layouts/base.php';
                return;
            }
            
            // データ準備
            $data = [
                'number' => (int)trim($_POST['number']),
                'name' => trim($_POST['name']),
                'display_order' => isset($_POST['display_order']) && $_POST['display_order'] !== '' 
                    ? (int)$_POST['display_order'] 
                    : 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'updated_by' => Session::get('user_id')
            ];
            
            // 更新処理
            if ($subsidiarySubjectModel->updateSubsidiarySubject($id, $data)) {
                Session::setFlash('success', '補助科目を更新しました。');
                $this->redirect('subsidiary_subjects');
            } else {
                throw new Exception('補助科目の更新に失敗しました。コードが重複している可能性があります。');
            }
            
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            $subsidiarySubjectModel = new SubsidiarySubject();
            $subsidiarySubject = $subsidiarySubjectModel->findById($id);
            
            // ビューをレンダリング
            ob_start();
            include __DIR__ . '/../views/subsidiary_subjects/edit.php';
            $content = ob_get_clean();
            
            include __DIR__ . '/../views/layouts/base.php';
        }
    }
    
    /**
     * 削除処理
     */
    public function delete($id) {
        Session::requireUserType('admin');
        
        try {
            // CSRFトークンの検証
            if (!$this->validateCsrf()) {
                throw new Exception('不正なリクエストです。');
            }
            
            $subsidiarySubjectModel = new SubsidiarySubject();
            $subsidiarySubject = $subsidiarySubjectModel->findById($id);
            
            if (!$subsidiarySubject) {
                throw new Exception('補助科目が見つかりません。');
            }
            
            // 論理削除
            if ($subsidiarySubjectModel->deleteSubsidiarySubject($id)) {
                Session::setFlash('success', '補助科目を削除しました。');
            } else {
                throw new Exception('補助科目の削除に失敗しました。');
            }
            
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }
        
        $this->redirect('subsidiary_subjects');
    }
    
    /**
     * 表示順序の更新（Ajax）
     */
    public function updateOrder() {
        Session::requireUserType('admin');
        
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // CSRFトークンの検証
            if (empty($input['csrf_token']) || !verify_csrf_token($input['csrf_token'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
                exit;
            }
            
            if (!isset($input['orders']) || !is_array($input['orders'])) {
                throw new Exception('順序データが不正です。');
            }
            
            $subsidiarySubjectModel = new SubsidiarySubject();
            
            if ($subsidiarySubjectModel->bulkUpdateDisplayOrder($input['orders'])) {
                echo json_encode(['success' => true, 'message' => '表示順序を更新しました。']);
            } else {
                throw new Exception('表示順序の更新に失敗しました。');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * バリデーション
     */
    private function validateSubsidiarySubject($data, $excludeId = null) {
        $errors = [];
        
        // 補助科目番号
        if (empty($data['number']) && $data['number'] !== '0') {
            $errors[] = '補助科目番号を入力してください。';
        } else {
            $number = $data['number'];
            
            // 数値チェック
            if (!is_numeric($number)) {
                $errors[] = '補助科目番号は数値で入力してください。';
            } else {
                $number = (int)$number;
                
                // 範囲チェック（0以上の整数）
                if ($number < 0) {
                    $errors[] = '補助科目番号は0以上の数値で入力してください。';
                }
                
                // 重複チェック
                $subsidiarySubjectModel = new SubsidiarySubject();
                if ($subsidiarySubjectModel->isNumberExists($number, $excludeId)) {
                    $errors[] = 'この補助科目番号は既に使用されています。';
                }
            }
        }
        
        // 補助科目名称
        if (empty($data['name'])) {
            $errors[] = '補助科目名称を入力してください。';
        } else {
            $name = trim($data['name']);
            if (strlen($name) > 100) {
                $errors[] = '補助科目名称は100文字以内で入力してください。';
            }
        }
        
        return $errors;
    }
}