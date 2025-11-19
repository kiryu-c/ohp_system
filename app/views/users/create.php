<?php
// app/views/users/create.php 修正版
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-plus me-2"></i>ユーザー追加</h2>
    <a href="<?= base_url('users') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>ユーザー一覧に戻る
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>ユーザー情報入力
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('users') ?>" id="userForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <!-- 基本情報 -->
            <div class="section-header mb-3">
                <h6 class="text-primary"><i class="fas fa-user me-2"></i>基本情報</h6>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">氏名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                               required maxlength="100">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="login_id" class="form-label">ログインID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="login_id" name="login_id" 
                               value="<?= htmlspecialchars($_POST['login_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                               required maxlength="50">
                        <div class="form-text">ログイン時に使用するIDです。</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                               required maxlength="255">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="user_type" class="form-label">ユーザータイプ <span class="text-danger">*</span></label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="">選択してください</option>
                            <option value="doctor" <?= ($_POST['user_type'] ?? '') === 'doctor' ? 'selected' : '' ?>>産業医</option>
                            <option value="company" <?= ($_POST['user_type'] ?? '') === 'company' ? 'selected' : '' ?>>企業ユーザー</option>
                            <option value="admin" <?= ($_POST['user_type'] ?? '') === 'admin' ? 'selected' : '' ?>>管理者</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="8"
                                   pattern="^(?=.*[a-zA-Z])(?=.*[0-9]).{8,}$"
                                   title="アルファベットと数字を含めて8文字以上で入力してください">
                            <button class="btn btn-outline-secondary password-toggle" type="button" data-target="password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">アルファベットと数字を含めて8文字以上で入力してください。ログイン招待メールを送信すると自動生成したパスワードで上書きされます。</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">パスワード確認</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                   minlength="8"
                                   pattern="^(?=.*[a-zA-Z])(?=.*[0-9]).{8,}$"
                                   title="アルファベットと数字を含めて8文字以上で入力してください">
                            <button class="btn btn-outline-secondary password-toggle" type="button" data-target="password_confirm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">パスワードを入力した場合は確認のため再度入力してください。</div>
                    </div>
                </div>
            </div>
            
            <!-- 産業医用の追加フィールド -->
            <div id="doctor-fields" style="display: none;">
                <hr class="my-4">
                
                <div class="section-header mb-3">
                    <h6 class="text-primary"><i class="fas fa-user-md me-2"></i>産業医情報</h6>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="contract_type" class="form-label">契約形態 <span class="text-danger">*</span></label>
                            <select class="form-select" id="contract_type" name="contract_type">
                                <option value="corporate" <?= ($_POST['contract_type'] ?? 'corporate') === 'corporate' ? 'selected' : '' ?>>
                                    法人契約
                                </option>
                                <option value="individual" <?= ($_POST['contract_type'] ?? 'corporate') === 'individual' ? 'selected' : '' ?>>
                                    個人契約
                                </option>
                            </select>
                            <div class="form-text">この産業医の契約形態を選択してください。</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="trade_name" class="form-label">屋号</label>
                            <input type="text" class="form-control" id="trade_name" name="trade_name" 
                                   value="<?= htmlspecialchars($_POST['trade_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="255"
                                   placeholder="例: ○○医院">
                            <div class="form-text">個人事業主として活動している場合に入力</div>
                        </div>
                    </div>
                </div>
                
                <!-- ★ 新規追加: 事業者区分セクション -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="business_classification" class="form-label">事業者区分 <span class="text-danger">*</span></label>
                            <select class="form-select" id="business_classification" name="business_classification">
                                <option value="">選択してください</option>
                                <option value="taxable" <?= ($_POST['business_classification'] ?? '') === 'taxable' ? 'selected' : '' ?>>
                                    課税事業者
                                </option>
                                <option value="tax_exempt" <?= ($_POST['business_classification'] ?? '') === 'tax_exempt' ? 'selected' : '' ?>>
                                    免税事業者
                                </option>
                            </select>
                            <div class="form-text">消費税の課税事業者か免税事業者かを選択してください</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3" id="invoice_number_field" style="display: none;">
                            <label for="invoice_registration_number" class="form-label">
                                インボイス登録番号 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="invoice_registration_number" 
                                name="invoice_registration_number" 
                                value="<?= htmlspecialchars($_POST['invoice_registration_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                maxlength="14"
                                placeholder="T1234567890123"
                                pattern="T\d{13}">
                            <div class="form-text">「T」+13桁の数字で入力してください</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="postal_code" class="form-label">郵便番号</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                   value="<?= htmlspecialchars($_POST['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   pattern="\d{3}-?\d{4}"
                                   placeholder="123-4567"
                                   maxlength="10">
                            <div class="form-text">ハイフンあり・なし両方可能</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="address" class="form-label">住所</label>
                            <textarea class="form-control" id="address" name="address" rows="2"
                                      placeholder="例: 東京都千代田区丸の内1-1-1"><?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">請求書に表示される住所</div>
                        </div>
                    </div>
                </div>
                
                <div class="section-header mb-3 mt-4">
                    <h6 class="text-primary"><i class="fas fa-university me-2"></i>振込先情報</h6>
                    <p class="text-muted small mb-0">※ 入力する場合はすべての項目を入力してください</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="bank_name" class="form-label">銀行名</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                   value="<?= htmlspecialchars($_POST['bank_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="100"
                                   placeholder="例: 三菱UFJ銀行">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="bank_branch_name" class="form-label">支店名</label>
                            <input type="text" class="form-control" id="bank_branch_name" name="bank_branch_name" 
                                   value="<?= htmlspecialchars($_POST['bank_branch_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="100"
                                   placeholder="例: 新宿支店">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="bank_account_type" class="form-label">口座種別</label>
                            <select class="form-select" id="bank_account_type" name="bank_account_type">
                                <option value="ordinary" <?= ($_POST['bank_account_type'] ?? 'ordinary') === 'ordinary' ? 'selected' : '' ?>>普通預金</option>
                                <option value="current" <?= ($_POST['bank_account_type'] ?? 'ordinary') === 'current' ? 'selected' : '' ?>>当座預金</option>
                                <option value="savings" <?= ($_POST['bank_account_type'] ?? 'ordinary') === 'savings' ? 'selected' : '' ?>>貯蓄預金</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="bank_account_number" class="form-label">口座番号</label>
                            <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" 
                                   value="<?= htmlspecialchars($_POST['bank_account_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   pattern="\d*"
                                   maxlength="20"
                                   placeholder="例: 1234567">
                            <div class="form-text">数字のみ</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="bank_account_holder" class="form-label">口座名義</label>
                            <input type="text" class="form-control" id="bank_account_holder" name="bank_account_holder" 
                                   value="<?= htmlspecialchars($_POST['bank_account_holder'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="255"
                                   placeholder="例: ヤマダ タロウ">
                            <div class="form-text">カタカナで入力</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 企業ユーザー用の追加フィールド -->
            <div id="company-fields" style="display: none;">
                <hr class="my-4">
                
                <div class="section-header mb-3">
                    <h6 class="text-primary"><i class="fas fa-building me-2"></i>企業情報</h6>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="company_id" class="form-label">所属企業 <span class="text-danger">*</span></label>
                            <select class="form-select" id="company_id" name="company_id">
                                <option value="">企業を選択してください</option>
                                <?php if (!empty($companies)): ?>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?= $company['id'] ?>" 
                                                <?= ($_POST['company_id'] ?? '') == $company['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">企業ユーザーが所属する企業を選択してください。</div>
                        </div>
                    </div>
                    
                    <!-- 拠点選択エリア -->
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">アクセス可能拠点 <span class="text-danger">*</span></label>
                            <div id="branch-selection-area">
                                <div class="text-muted">企業を選択してください</div>
                            </div>
                            <div class="form-text">このユーザーがアクセス可能な拠点を選択してください(複数選択可)</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="<?= base_url('users') ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-times me-1"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>登録
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 入力説明 -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-info-circle me-2"></i>入力項目について
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h6><i class="fas fa-user text-primary me-2"></i>基本情報</h6>
                <ul class="small">
                    <li><strong>氏名:</strong> ユーザーの氏名</li>
                    <li><strong>ログインID:</strong> ログイン時に使用するID</li>
                    <li><strong>メールアドレス:</strong> 連絡先・通知用</li>
                    <li><strong>ユーザータイプ:</strong> 権限レベル</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6><i class="fas fa-user-md text-primary me-2"></i>産業医固有項目</h6>
                <ul class="small">
                    <li><strong>契約形態:</strong> 法人契約 or 個人契約</li>
                    <li><strong>屋号:</strong> 任意、個人事業主の場合</li>
                    <li><strong>郵便番号・住所:</strong> 請求書に表示</li>
                    <li><strong>振込先情報:</strong> 報酬振込用</li>
                    <li>※産業医を選択時のみ表示</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6><i class="fas fa-building text-primary me-2"></i>企業ユーザー固有項目</h6>
                <ul class="small">
                    <li><strong>所属企業:</strong> 管理する企業</li>
                    <li><strong>アクセス可能拠点:</strong> 管理可能な拠点</li>
                    <li>※企業ユーザーを選択時のみ表示</li>
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>重要な注意事項</h6>
                    <ul class="mb-0 small">
                        <li><strong>ログインID:</strong> 半角英数字とアンダースコア(_)、ハイフン(-)のみ使用可能です。</li>
                        <li><strong>パスワード:</strong> アルファベットと数字を含めて8文字以上で設定してください。</li>
                        <li><strong>産業医の振込先:</strong> 入力する場合は銀行名、支店名、口座番号、口座名義の4項目すべて入力してください。</li>
                        <li><strong>郵便番号:</strong> ハイフンは自動で整形されます(123-4567形式)。</li>
                        <li><strong>口座番号:</strong> 数字のみで入力してください。</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userTypeSelect = document.getElementById('user_type');
    const doctorFields = document.getElementById('doctor-fields');
    const companyFields = document.getElementById('company-fields');
    const contractTypeField = document.getElementById('contract_type');
    const companyIdField = document.getElementById('company_id');
    const branchSelectionArea = document.getElementById('branch-selection-area');
    const passwordField = document.getElementById('password');
    const passwordConfirmField = document.getElementById('password_confirm');
    const loginIdField = document.getElementById('login_id');
    const postalCodeField = document.getElementById('postal_code');
    const bankAccountNumberField = document.getElementById('bank_account_number');
    
    // パスワード表示切り替え機能
    function initPasswordToggle() {
        const toggleButtons = document.querySelectorAll('.password-toggle');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    targetInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }
    
    // 郵便番号の自動整形
    postalCodeField.addEventListener('blur', function() {
        let value = this.value.replace(/[^\d]/g, '');
        if (value.length === 7) {
            this.value = value.substring(0, 3) + '-' + value.substring(3);
        }
    });
    
    // 郵便番号の入力制限
    postalCodeField.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        const currentValue = this.value.replace(/[^\d]/g, '');
        
        if (!/[\d-]/.test(char)) {
            e.preventDefault();
            return;
        }
        
        if (/\d/.test(char) && currentValue.length >= 7) {
            e.preventDefault();
        }
    });
    
    // 口座番号の入力制限(数字のみ)
    bankAccountNumberField.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        if (!/\d/.test(char)) {
            e.preventDefault();
        }
    });
    
    // 企業選択時に拠点一覧を取得・表示
    async function loadBranches(companyId) {
        if (!companyId) {
            branchSelectionArea.innerHTML = '<div class="text-muted">企業を選択してください</div>';
            return;
        }
        
        try {
            branchSelectionArea.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin me-2"></i>拠点を読み込み中...</div>';
            
            const url = `<?= base_url('users/api/branches') ?>?company_id=${companyId}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (response.ok && data.branches) {
                renderBranchSelection(data.branches);
            } else {
                branchSelectionArea.innerHTML = '<div class="text-danger">拠点データが見つかりません</div>';
            }
        } catch (error) {
            console.error('Branch loading error:', error);
            branchSelectionArea.innerHTML = '<div class="text-danger">拠点の取得に失敗しました</div>';
        }
    }
    
    // 拠点選択UIを描画
    function renderBranchSelection(branches) {
        if (branches.length === 0) {
            branchSelectionArea.innerHTML = '<div class="text-warning">この企業には拠点が登録されていません</div>';
            return;
        }
        
        let html = '<div class="border rounded p-3">';
        html += '<div class="form-text mb-2">アクセス可能にする拠点を選択してください(複数選択可)</div>';
        
        branches.forEach(branch => {
            html += `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="branch_ids[]" 
                           value="${branch.id}" id="branch_${branch.id}">
                    <label class="form-check-label" for="branch_${branch.id}">
                        <strong>${escapeHtml(branch.name)}</strong>
                        ${branch.address ? `<br><small class="text-muted">${escapeHtml(branch.address)}</small>` : ''}
                    </label>
                </div>
            `;
        });
        
        html += '</div>';
        html += '<div class="form-text mt-2">※ 少なくとも1つの拠点を選択してください</div>';
        
        branchSelectionArea.innerHTML = html;
    }
    
    // HTMLエスケープ関数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // ログインIDの入力制限
    function validateLoginId() {
        const value = loginIdField.value;
        const validPattern = /^[a-zA-Z0-9_-]*$/;
        
        if (value && !validPattern.test(value)) {
            loginIdField.setCustomValidity('半角英数字、アンダースコア(_)、ハイフン(-)のみ使用できます。');
        } else {
            loginIdField.setCustomValidity('');
        }
    }
    
    // ユーザータイプ変更時の処理
    function handleUserTypeChange() {
        const selectedType = userTypeSelect.value;
        
        // すべてのフィールドを非表示
        doctorFields.style.display = 'none';
        companyFields.style.display = 'none';
        
        // 必須属性をリセット
        contractTypeField.removeAttribute('required');
        companyIdField.removeAttribute('required');
        
        // 選択されたタイプに応じてフィールドを表示
        if (selectedType === 'doctor') {
            doctorFields.style.display = 'block';
            contractTypeField.setAttribute('required', 'required');
        } else if (selectedType === 'company') {
            companyFields.style.display = 'block';
            companyIdField.setAttribute('required', 'required');
        }
    }
    
    // パスワード確認の処理
    function validatePassword() {
        const password = passwordField.value;
        const passwordConfirm = passwordConfirmField.value;
        
        // 両方とも空の場合は問題なし(自動生成される)
        if (!password && !passwordConfirm) {
            passwordField.setCustomValidity('');
            passwordConfirmField.setCustomValidity('');
            return;
        }
        
        // どちらか一方のみ入力されている場合
        if (password && !passwordConfirm) {
            passwordConfirmField.setCustomValidity('パスワード確認を入力してください。');
            return;
        }
        
        if (!password && passwordConfirm) {
            passwordField.setCustomValidity('パスワードを入力してください。');
            passwordConfirmField.setCustomValidity('');
            return;
        }
        
        // パスワードが入力されている場合の要件チェック
        if (password) {
            // 8文字以上
            if (password.length < 8) {
                passwordField.setCustomValidity('パスワードは8文字以上で入力してください。');
                return;
            }
            
            // アルファベットを含む
            if (!/[a-zA-Z]/.test(password)) {
                passwordField.setCustomValidity('パスワードにはアルファベットを含めてください。');
                return;
            }
            
            // 数字を含む
            if (!/[0-9]/.test(password)) {
                passwordField.setCustomValidity('パスワードには数字を含めてください。');
                return;
            }
            
            // すべての要件を満たしている
            passwordField.setCustomValidity('');
        }
        
        // 両方入力されている場合は一致確認
        if (password !== passwordConfirm) {
            passwordConfirmField.setCustomValidity('パスワードが一致しません。');
        } else {
            passwordConfirmField.setCustomValidity('');
        }
    }
    
    // フォーム送信時の処理
    function handleFormSubmit(event) {
        const userType = userTypeSelect.value;
        
        // 企業ユーザーの場合、拠点が選択されているかチェック
        if (userType === 'company') {
            const selectedBranches = document.querySelectorAll('input[name="branch_ids[]"]:checked');
            if (selectedBranches.length === 0) {
                alert('少なくとも1つの拠点を選択してください。');
                event.preventDefault();
                return;
            }
        }
        
        // 産業医以外の場合は契約形態をクリア
        if (userType !== 'doctor') {
            contractTypeField.value = '';
        }
        
        // 企業ユーザー以外の場合は企業IDをクリア
        if (userType !== 'company') {
            companyIdField.value = '';
        }
        
        // ログインID検証
        validateLoginId();
        
        // パスワード確認
        validatePassword();
        
        // バリデーションエラーがある場合は送信を停止
        if (!event.target.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
    }
    
    // イベントリスナーの設定
    userTypeSelect.addEventListener('change', handleUserTypeChange);
    companyIdField.addEventListener('change', function() {
        const companyId = this.value;
        if (companyId) {
            loadBranches(companyId);
        } else {
            branchSelectionArea.innerHTML = '<div class="text-muted">企業を選択してください</div>';
        }
    });
    
    passwordField.addEventListener('input', validatePassword);
    passwordConfirmField.addEventListener('input', validatePassword);
    loginIdField.addEventListener('input', validateLoginId);
    
    // リアルタイムでログインIDの入力をチェック
    loginIdField.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        const validPattern = /[a-zA-Z0-9_-]/;
        
        if (!validPattern.test(char)) {
            e.preventDefault();
        }
    });
    
    // フォーム送信時の処理
    document.getElementById('userForm').addEventListener('submit', handleFormSubmit);
    
    // 初期化
    initPasswordToggle();
    handleUserTypeChange();
});

document.addEventListener('DOMContentLoaded', function() {
    const businessClassificationSelect = document.getElementById('business_classification');
    const invoiceNumberField = document.getElementById('invoice_number_field');
    const invoiceNumberInput = document.getElementById('invoice_registration_number');
    
    // 事業者区分の変更時の処理
    function handleBusinessClassificationChange() {
        const classification = businessClassificationSelect.value;
        
        if (classification === 'taxable') {
            // 課税事業者の場合はインボイス番号フィールドを表示・必須化
            invoiceNumberField.style.display = 'block';
            invoiceNumberInput.setAttribute('required', 'required');
        } else {
            // 免税事業者の場合はインボイス番号フィールドを非表示・任意化
            invoiceNumberField.style.display = 'none';
            invoiceNumberInput.removeAttribute('required');
            invoiceNumberInput.value = ''; // 値をクリア
        }
    }
    
    // インボイス番号の入力制限と自動フォーマット
    function formatInvoiceNumber(input) {
        let value = input.value.toUpperCase();
        
        // 先頭にTがない場合は自動で追加
        if (value.length > 0 && value[0] !== 'T') {
            value = 'T' + value;
        }
        
        // T以降は数字のみ
        value = 'T' + value.substring(1).replace(/[^\d]/g, '');
        
        // 最大14文字(T + 13桁)
        if (value.length > 14) {
            value = value.substring(0, 14);
        }
        
        input.value = value;
    }
    
    // インボイス番号のバリデーション表示
    function validateInvoiceNumber(input) {
        const value = input.value;
        const pattern = /^T\d{13}$/;
        
        if (value && !pattern.test(value)) {
            input.setCustomValidity('「T」+13桁の数字で入力してください。(例: T1234567890123)');
        } else {
            input.setCustomValidity('');
        }
    }
    
    // イベントリスナーの設定
    businessClassificationSelect.addEventListener('change', handleBusinessClassificationChange);
    
    invoiceNumberInput.addEventListener('input', function() {
        formatInvoiceNumber(this);
        validateInvoiceNumber(this);
    });
    
    invoiceNumberInput.addEventListener('blur', function() {
        validateInvoiceNumber(this);
    });
    
    // 初期化
    handleBusinessClassificationChange();
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.section-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #7dd3fc;
    box-shadow: 0 0 0 0.2rem rgba(125, 211, 252, 0.25);
}

.text-danger {
    color: #dc3545 !important;
}

.form-text {
    font-size: 0.875em;
    color: #6c757d;
}

#branch-selection-area .border {
    max-height: 300px;
    overflow-y: auto;
}

.form-check {
    margin-bottom: 0.75rem;
    padding: 0.5rem;
    padding-left: 1rem;
    border-radius: 0.25rem;
    transition: background-color 0.15s ease-in-out;
}

.form-check:hover {
    background-color: #f8f9fa;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.alert-info {
    background-color: #e7f3ff;
    border-color: #bee5eb;
    color: #0c5460;
}

.password-toggle {
    border-left: none;
    cursor: pointer;
}

.password-toggle:hover {
    background-color: #f8f9fa;
}

.password-toggle:focus {
    box-shadow: none;
    border-color: #7dd3fc;
}

.input-group .form-control:focus + .password-toggle {
    border-color: #7dd3fc;
}

@media (max-width: 768px) {
    .d-grid.gap-2.d-md-flex {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}

/* インボイス番号フィールドの追加スタイル */
#invoice_number_field {
    transition: all 0.3s ease;
}

.form-control:invalid {
    border-color: #dc3545;
}

.form-control:valid {
    border-color: #198754;
}
</style>