<?php
// app/views/companies/create.php
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-plus me-2"></i>企業追加</h2>
    <a href="<?= base_url('companies') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>企業一覧に戻る
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i>新規企業情報</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('companies') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">
                                企業名 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   required maxlength="200"
                                   placeholder="例：株式会社サンプル">
                            <div class="form-text">正式な企業名を入力してください</div>
                        </div>
                    </div>
                    
                    <!-- 拠点名追加 -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="branch_name" class="form-label">
                                拠点名 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="branch_name" name="branch_name" 
                                   value="<?= htmlspecialchars($_POST['branch_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   required maxlength="200"
                                   placeholder="例：本社、東京支店、大阪工場など">
                            <div class="form-text">この企業の最初の拠点名を入力してください（後から追加可能）</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="account_code" class="form-label">
                                勘定科目番号 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="account_code" name="account_code" 
                                   value="<?= htmlspecialchars($_POST['account_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   required maxlength="16"
                                   placeholder="例：1234567890123456">
                            <div class="form-text">この拠点の勘定科目番号を入力してください（16桁まで）</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="address" class="form-label">住所</label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      placeholder="例：東京都港区1-1-1 サンプルビル10F"><?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">この拠点の住所を入力してください</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">電話番号</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="20"
                                   placeholder="例：03-1234-5678">
                            <div class="form-text">この拠点の電話番号を入力してください</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">メールアドレス</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="100"
                                   placeholder="例：info@sample.com">
                            <div class="form-text">この拠点のメールアドレスを入力してください</div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>企業・拠点追加について</h6>
                                <ul class="mb-0">
                                    <li>企業名と拠点名は必須項目です</li>
                                    <li>住所・電話番号・メールアドレスは拠点ごとに管理されます</li>
                                    <li>追加の拠点は企業編集画面から登録できます</li>
                                    <li>契約は拠点単位で管理されます</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('companies') ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>キャンセル
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>企業を追加
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // フォームバリデーション
    const form = document.querySelector('form');
    const nameInput = document.getElementById('name');
    const branchNameInput = document.getElementById('branch_name');
    const accountCodeInput = document.getElementById('account_code');
    const emailInput = document.getElementById('email');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // 企業名の検証
        if (nameInput.value.trim() === '') {
            showFieldError(nameInput, '企業名を入力してください');
            isValid = false;
        } else {
            clearFieldError(nameInput);
        }
        
        // 拠点名の検証
        if (branchNameInput.value.trim() === '') {
            showFieldError(branchNameInput, '拠点名を入力してください');
            isValid = false;
        } else {
            clearFieldError(branchNameInput);
        }
        
        // 勘定科目番号の検証
        if (accountCodeInput.value.trim() === '') {
            showFieldError(accountCodeInput, '勘定科目番号を入力してください');
            isValid = false;
        } else if (accountCodeInput.value.length > 16) {
            showFieldError(accountCodeInput, '勘定科目番号は16桁以内で入力してください');
            isValid = false;
        } else {
            clearFieldError(accountCodeInput);
        }
        
        // メールアドレスの検証（入力されている場合のみ）
        if (emailInput.value.trim() !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value)) {
                showFieldError(emailInput, '有効なメールアドレスを入力してください');
                isValid = false;
            } else {
                clearFieldError(emailInput);
            }
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    function showFieldError(field, message) {
        clearFieldError(field);
        field.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    function clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
});
</script>