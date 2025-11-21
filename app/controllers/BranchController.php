<?php
// app/controllers/BranchController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Branch.php';
require_once __DIR__ . '/../models/Company.php';

class BranchController extends BaseController {
    public function index() {
        Session::requireUserType('admin');
        
        $companyId = $_GET['company_id'] ?? null;
        
        if (!$companyId) {
            $this->setFlash('error', '企業が指定されていません。');
            redirect('companies');
        }
        
        $companyModel = new Company();
        $company = $companyModel->findById($companyId);
        
        if (!$company) {
            $this->setFlash('error', '企業が見つかりません。');
            redirect('companies');
        }
        
        $branchModel = new Branch();
        $branches = $branchModel->findByCompany($companyId);
        
        ob_start();
        include __DIR__ . '/../views/branches/index.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function create() {
        Session::requireUserType('admin');
        
        $companyId = $_GET['company_id'] ?? null;
        
        if (!$companyId) {
            $this->setFlash('error', '企業が指定されていません。');
            redirect('companies');
        }
        
        $companyModel = new Company();
        $company = $companyModel->findById($companyId);
        
        if (!$company) {
            $this->setFlash('error', '企業が見つかりません。');
            redirect('companies');
        }
        
        ob_start();
        include __DIR__ . '/../views/branches/create.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function store() {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect('companies');
        }
        
        $companyId = (int)($_POST['company_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $accountCode = sanitize($_POST['account_code'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        
        // バリデーション
        $errors = [];
        
        if (empty($name)) {
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
            redirect("branches/create?company_id={$companyId}");
        }
        
        $branchModel = new Branch();
        $data = [
            'company_id' => $companyId,
            'name' => $name,
            'account_code' => $accountCode,
            'address' => $address,
            'phone' => $phone,
            'email' => $email
        ];
        
        $branchId = $branchModel->create($data);
        
        if ($branchId) {
            $this->setFlash('success', '拠点を作成しました。');
            redirect("companies/{$companyId}/edit");  // 企業編集画面に戻る
        } else {
            $this->setFlash('error', '拠点の作成に失敗しました。');
            redirect("companies/{$companyId}/edit");  // エラー時も企業編集画面に戻る
        }
    }
    
    public function edit($id) {
        Session::requireUserType('admin');
        
        $branchModel = new Branch();
        $branch = $branchModel->findWithCompany($id);
        
        if (!$branch) {
            $this->setFlash('error', '拠点が見つかりません。');
            redirect('companies');
        }
        
        ob_start();
        include __DIR__ . '/../views/branches/edit.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/base.php';
    }
    
    public function update($id) {
        Session::requireUserType('admin');
        
        if (!$this->validateCsrf()) {
            redirect("branches/{$id}/edit");
        }
        
        $branchModel = new Branch();
        $branch = $branchModel->findById($id);
        
        if (!$branch) {
            $this->setFlash('error', '拠点が見つかりません。');
            redirect('companies');
        }
        
        $name = sanitize($_POST['name'] ?? '');
        $accountCode = sanitize($_POST['account_code'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // バリデーション
        if (empty($name)) {
            $this->setFlash('error', '拠点名を入力してください。');
            redirect("branches/{$id}/edit");
        }
        
        if (empty($accountCode)) {
            $this->setFlash('error', '勘定科目番号を入力してください。');
            redirect("branches/{$id}/edit");
        } elseif (strlen($accountCode) > 16) {
            $this->setFlash('error', '勘定科目番号は16桁以内で入力してください。');
            redirect("branches/{$id}/edit");
        }
        
        $data = [
            'name' => $name,
            'account_code' => $accountCode,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'is_active' => $isActive
        ];
        
        if ($branchModel->update($id, $data)) {
            $this->setFlash('success', '拠点を更新しました。');
            redirect("companies/{$branch['company_id']}/edit");  // 企業編集画面に戻る
        } else {
            $this->setFlash('error', '拠点の更新に失敗しました。');
            redirect("companies/{$branch['company_id']}/edit");  // エラー時も企業編集画面に戻る
        }
    }
}
?>