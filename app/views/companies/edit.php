<?php
// app/views/companies/edit.php
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit me-2"></i>企業編集</h2>
    <a href="<?= base_url('companies') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>企業一覧に戻る
    </a>
</div>

<div class="row">
    <!-- 企業情報編集 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i>企業情報編集</h5>
                <span class="badge bg-<?= $company['is_active'] ? 'success' : 'secondary' ?>">
                    <?= $company['is_active'] ? '有効' : '無効' ?>
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url("companies/{$company['id']}") ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            企業名 <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?>" 
                               required maxlength="200">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?= $company['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                <strong>企業を有効にする</strong>
                            </label>
                            <div class="form-text">
                                無効にすると、この企業に所属するユーザーはログインできなくなります
                            </div>
                        </div>
                    </div>
                    
                    <!-- 企業情報表示 -->
                    <hr>
                    <h6>企業詳細情報</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr>
                                <th width="120">企業ID</th>
                                <td><?= $company['id'] ?></td>
                            </tr>
                            <tr>
                                <th>登録日時</th>
                                <td><?= date('Y年m月d日', strtotime($company['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <th>所属ユーザー数</th>
                                <td>
                                    <?php
                                    $userModel = new User();
                                    $companyUsers = $userModel->findByCompany($company['id']);
                                    echo count($companyUsers);
                                    ?>人
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>変更を保存
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 拠点管理 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>拠点管理</h5>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                    <i class="fas fa-plus me-1"></i>拠点追加
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($branches)): ?>
                    <p class="text-muted">拠点が登録されていません。</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>拠点名</th>
                                    <th>勘定科目番号</th>
                                    <th>契約数</th>
                                    <th>状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($branches as $branch): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($branch['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($branch['account_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php
                                            // 契約数を取得
                                            $contractCount = isset($branch['contract_count']) ? $branch['contract_count'] : 0;
                                            echo $contractCount;
                                            ?>件
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $branch['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $branch['is_active'] ? '有効' : '無効' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editBranch(<?= $branch['id'] ?>, '<?= htmlspecialchars($branch['name'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($branch['account_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($branch['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($branch['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($branch['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>', <?= $branch['is_active'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 拠点追加モーダル -->
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">拠点追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= base_url('branches') ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="company_id" value="<?= $company['id'] ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_branch_name" class="form-label">
                            拠点名 <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="new_branch_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_branch_account_code" class="form-label">
                            勘定科目番号 <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="new_branch_account_code" name="account_code" required maxlength="16" placeholder="例：1234567890123456">
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_branch_address" class="form-label">住所</label>
                        <textarea class="form-control" id="new_branch_address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_branch_phone" class="form-label">電話番号</label>
                            <input type="tel" class="form-control" id="new_branch_phone" name="phone">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="new_branch_email" class="form-label">メールアドレス</label>
                            <input type="email" class="form-control" id="new_branch_email" name="email">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-success">追加</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 拠点編集モーダル -->
<div class="modal fade" id="editBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">拠点編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editBranchForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_branch_name" class="form-label">
                            拠点名 <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_branch_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_branch_account_code" class="form-label">
                            勘定科目番号 <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_branch_account_code" name="account_code" required maxlength="16" placeholder="例：1234567890123456">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_branch_address" class="form-label">住所</label>
                        <textarea class="form-control" id="edit_branch_address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_branch_phone" class="form-label">電話番号</label>
                            <input type="tel" class="form-control" id="edit_branch_phone" name="phone">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_branch_email" class="form-label">メールアドレス</label>
                            <input type="email" class="form-control" id="edit_branch_email" name="email">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_branch_active" name="is_active">
                            <label class="form-check-label" for="edit_branch_active">
                                拠点を有効にする
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBranch(id, name, accountCode, address, phone, email, isActive) {
    document.getElementById('editBranchForm').action = '<?= base_url('branches/') ?>' + id;
    document.getElementById('edit_branch_name').value = name;
    document.getElementById('edit_branch_account_code').value = accountCode;
    document.getElementById('edit_branch_address').value = address;
    document.getElementById('edit_branch_phone').value = phone;
    document.getElementById('edit_branch_email').value = email;
    document.getElementById('edit_branch_active').checked = isActive;
    
    const modal = new bootstrap.Modal(document.getElementById('editBranchModal'));
    modal.show();
}
</script>