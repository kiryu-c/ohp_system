<?php
// app/views/users/edit.php 修正版
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-user-edit me-2"></i>
        <?php if ($isOwnProfile && !$isAdmin): ?>
            プロフィール編集
        <?php else: ?>
            ユーザー編集
        <?php endif; ?>
    </h2>
    <a href="<?= $isAdmin ? base_url('users') : base_url('dashboard') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>
        <?= $isAdmin ? 'ユーザー一覧に戻る' : 'ダッシュボードに戻る' ?>
    </a>
</div>

<?php if ($isOwnProfile && !$isAdmin): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>注意:</strong> ログインIDとユーザータイプは変更できません。変更が必要な場合は管理者にお問い合わせください。
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>ユーザー情報編集
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url("users/{$user['id']}") ?>" id="userForm">
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
                               value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>" 
                               required maxlength="100">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="login_id" class="form-label">
                            ログインID <span class="text-danger">*</span>
                            <?php if (!$isAdmin): ?>
                                <small class="text-muted">(変更不可)</small>
                            <?php endif; ?>
                        </label>
                        <input type="text" class="form-control" id="login_id" name="login_id" 
                               value="<?= htmlspecialchars($user['login_id'], ENT_QUOTES, 'UTF-8') ?>" 
                               required maxlength="50"
                               <?= !$isAdmin ? 'readonly' : '' ?>>
                        <?php if ($isAdmin): ?>
                            <div class="form-text">ログイン時に使用するIDです。</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" 
                               required maxlength="255">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="user_type" class="form-label">
                            ユーザータイプ <span class="text-danger">*</span>
                            <?php if (!$isAdmin): ?>
                                <small class="text-muted">(変更不可)</small>
                            <?php endif; ?>
                        </label>
                        <select class="form-select" id="user_type" name="user_type" required
                                <?= !$isAdmin ? 'disabled' : '' ?>>
                            <option value="">選択してください</option>
                            <option value="doctor" <?= $user['user_type'] === 'doctor' ? 'selected' : '' ?>>産業医</option>
                            <option value="company" <?= $user['user_type'] === 'company' ? 'selected' : '' ?>>企業ユーザー</option>
                            <option value="admin" <?= $user['user_type'] === 'admin' ? 'selected' : '' ?>>管理者</option>
                        </select>
                        <?php if (!$isAdmin): ?>
                            <!-- 非表示でユーザータイプを送信 -->
                            <input type="hidden" name="user_type" value="<?= htmlspecialchars($user['user_type'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($isAdmin): ?>
                <!-- 管理者のみステータス変更可能 -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="is_active" class="form-label">ステータス <span class="text-danger">*</span></label>
                            <select class="form-select" id="is_active" name="is_active" required>
                                <option value="1" <?= ($user['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>有効</option>
                                <option value="0" <?= ($user['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>無効</option>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- 産業医用の追加フィールド -->
            <div id="doctor-fields" style="display: <?= $user['user_type'] === 'doctor' ? 'block' : 'none' ?>;">
                <hr class="my-4">
                
                <div class="section-header mb-3">
                    <h6 class="text-primary"><i class="fas fa-user-md me-2"></i>産業医情報</h6>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="contract_type" class="form-label">契約形態 <span class="text-danger">*</span></label>
                            <select class="form-select" id="contract_type" name="contract_type" 
                                    <?= $user['user_type'] === 'doctor' ? 'required' : '' ?>>
                                <option value="corporate" <?= ($user['contract_type'] ?? 'corporate') === 'corporate' ? 'selected' : '' ?>>
                                    法人契約
                                </option>
                                <option value="individual" <?= ($user['contract_type'] ?? 'corporate') === 'individual' ? 'selected' : '' ?>>
                                    個人契約
                                </option>
                            </select>
                            <div class="form-text">この産業医の契約形態を選択してください。</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="partner_id" class="form-label">パートナーID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="partner_id" name="partner_id" 
                                   value="<?= htmlspecialchars($user['partner_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="10"
                                   placeholder="例: A123456789"
                                   <?= $user['user_type'] === 'doctor' ? 'required' : '' ?>>
                            <div class="form-text">10桁までの文字列で入力してください</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="trade_name" class="form-label">屋号</label>
                            <input type="text" class="form-control" id="trade_name" name="trade_name" 
                                   value="<?= htmlspecialchars($user['trade_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="255"
                                   placeholder="例: ○○医院">
                            <div class="form-text">個人事業主として活動している場合に入力</div>
                        </div>
                    </div>
                </div>
                
                <!-- 事業者区分セクション -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="business_classification" class="form-label">事業者区分 <span class="text-danger">*</span></label>
                            <select class="form-select" id="business_classification" name="business_classification" 
                                    <?= $user['user_type'] === 'doctor' ? 'required' : '' ?>>
                                <option value="">選択してください</option>
                                <option value="taxable" <?= ($user['business_classification'] ?? '') === 'taxable' ? 'selected' : '' ?>>
                                    課税事業者
                                </option>
                                <option value="tax_exempt" <?= ($user['business_classification'] ?? '') === 'tax_exempt' ? 'selected' : '' ?>>
                                    免税事業者
                                </option>
                            </select>
                            <div class="form-text">消費税の課税事業者か免税事業者かを選択してください</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3" id="invoice_number_field" 
                             style="display: <?= ($user['business_classification'] ?? '') === 'taxable' ? 'block' : 'none' ?>;">
                            <label for="invoice_registration_number" class="form-label">
                                インボイス登録番号 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="invoice_registration_number" 
                                   name="invoice_registration_number" 
                                   value="<?= htmlspecialchars($user['invoice_registration_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="14"
                                   placeholder="T1234567890123"
                                   pattern="T\d{13}"
                                   <?= ($user['business_classification'] ?? '') === 'taxable' ? 'required' : '' ?>>
                            <div class="form-text">「T」+13桁の数字で入力してください</div>
                        </div>
                    </div>
                </div>
                
                <!-- 住所情報 -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="postal_code" class="form-label">郵便番号</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                   value="<?= htmlspecialchars($user['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
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
                                      placeholder="例: 東京都千代田区丸の内1-1-1"><?= htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">請求書に表示される住所</div>
                        </div>
                    </div>
                </div>
                
                <!-- 振込先情報 -->
                <div class="section-header mb-3 mt-4">
                    <h6 class="text-primary"><i class="fas fa-university me-2"></i>振込先情報</h6>
                    <p class="text-muted small mb-0">※ 入力する場合はすべての項目を入力してください</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="bank_name" class="form-label">銀行名</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                   value="<?= htmlspecialchars($user['bank_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="100"
                                   placeholder="例: 三菱UFJ銀行">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="bank_branch_name" class="form-label">支店名</label>
                            <input type="text" class="form-control" id="bank_branch_name" name="bank_branch_name" 
                                   value="<?= htmlspecialchars($user['bank_branch_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
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
                                <option value="ordinary" <?= ($user['bank_account_type'] ?? 'ordinary') === 'ordinary' ? 'selected' : '' ?>>普通預金</option>
                                <option value="current" <?= ($user['bank_account_type'] ?? 'ordinary') === 'current' ? 'selected' : '' ?>>当座預金</option>
                                <option value="savings" <?= ($user['bank_account_type'] ?? 'ordinary') === 'savings' ? 'selected' : '' ?>>貯蓄預金</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="bank_account_number" class="form-label">口座番号</label>
                            <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" 
                                   value="<?= htmlspecialchars($user['bank_account_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
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
                                   value="<?= htmlspecialchars($user['bank_account_holder'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                   maxlength="255"
                                   placeholder="例: ヤマダ タロウ">
                            <div class="form-text">カタカナで入力</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 企業ユーザー用のフィールド（管理者のみ表示） -->
            <?php if ($isAdmin): ?>
                <div id="company-fields" style="display: <?= $user['user_type'] === 'company' ? 'block' : 'none' ?>;">
                    <hr class="my-4">
                    
                    <div class="section-header mb-3">
                        <h6 class="text-primary"><i class="fas fa-building me-2"></i>企業情報</h6>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="company_id" class="form-label">所属企業 <span class="text-danger">*</span></label>
                                <select class="form-select" id="company_id" name="company_id" 
                                        <?= $user['user_type'] === 'company' ? 'required' : '' ?>>
                                    <option value="">企業を選択してください</option>
                                    <?php if (!empty($companies)): ?>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?= $company['id'] ?>" 
                                                    <?= ($user['company_id'] ?? '') == $company['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- 拠点選択エリア -->
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">アクセス可能拠点 <span class="text-danger">*</span></label>
                                <div id="branch-selection-area">
                                    <?php if (!empty($user['company_id'])): ?>
                                        <!-- 拠点は後でJavaScriptで読み込まれます -->
                                        <div class="text-muted"><i class="fas fa-spinner fa-spin me-2"></i>読み込み中...</div>
                                    <?php else: ?>
                                        <div class="text-muted">企業を選択してください</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 現在の拠点情報表示 -->
                        <?php if ($user['user_type'] === 'company' && !empty($user['branches'])): ?>
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-building me-2"></i>現在の拠点紐づけ</h6>
                                    <div class="row">
                                        <?php foreach ($user['branches'] as $branch): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="badge bg-primary">
                                                    <?= htmlspecialchars($branch['company_name'], ENT_QUOTES, 'UTF-8') ?> - 
                                                    <?= htmlspecialchars($branch['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- パスワード変更 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-key me-2"></i>パスワード変更
                        <small class="text-muted">(変更する場合のみ入力)</small>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">新しいパスワード</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="8"
                                           pattern="^(?=.*[a-zA-Z])(?=.*[0-9]).{8,}$"
                                           title="アルファベットと数字を含めて8文字以上で入力してください"
                                           placeholder="変更しない場合は空欄">
                                    <button class="btn btn-outline-secondary password-toggle" type="button" data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">アルファベットと数字を含めて8文字以上で入力してください。</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">パスワード確認</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                           minlength="8"
                                           pattern="^(?=.*[a-zA-Z])(?=.*[0-9]).{8,}$"
                                           title="アルファベットと数字を含めて8文字以上で入力してください"
                                           placeholder="確認のため再度入力">
                                    <button class="btn btn-outline-secondary password-toggle" type="button" data-target="password_confirm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="<?= $isAdmin ? base_url('users') : base_url('dashboard') ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-times me-1"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>更新
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ユーザー情報サマリー（管理者のみ表示） -->
<?php if ($isAdmin): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>ユーザー情報
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>基本情報</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="120">ユーザーID:</th>
                            <td><?= $user['id'] ?></td>
                        </tr>
                        <tr>
                            <th>作成日:</th>
                            <td><?= isset($user['created_at']) ? date('Y/m/d H:i', strtotime($user['created_at'])) : '記録なし' ?></td>
                        </tr>
                        <tr>
                            <th>更新日:</th>
                            <td><?= isset($user['updated_at']) ? date('Y/m/d H:i', strtotime($user['updated_at'])) : '記録なし' ?></td>
                        </tr>
                        <tr>
                            <th>最終ログイン:</th>
                            <td><?= isset($user['last_login_at']) && $user['last_login_at'] ? date('Y/m/d H:i', strtotime($user['last_login_at'])) : '記録なし' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>権限・設定</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="120">ユーザータイプ:</th>
                            <td>
                                <span class="badge bg-<?php
                                    switch($user['user_type']) {
                                        case 'doctor': echo 'info'; break;
                                        case 'company': echo 'primary'; break;
                                        case 'admin': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?= get_user_type_label($user['user_type']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>ステータス:</th>
                            <td>
                                <span class="badge bg-<?= ($user['is_active'] ?? 1) == 1 ? 'success' : 'secondary' ?>">
                                    <?= ($user['is_active'] ?? 1) == 1 ? '有効' : '無効' ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($user['user_type'] === 'doctor'): ?>
                            <tr>
                                <th>契約形態:</th>
                                <td>
                                    <span class="badge bg-<?= ($user['contract_type'] ?? 'corporate') === 'corporate' ? 'primary' : 'info' ?>">
                                        <?= ($user['contract_type'] ?? 'corporate') === 'corporate' ? '法人契約' : '個人契約' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
/* 読み取り専用フィールドのスタイル */
.form-control[readonly],
.form-select[disabled] {
    background-color: #e9ecef;
    cursor: not-allowed;
}

/* 拠点選択チェックボックスのスタイル */
.branch-checkbox-container {
    max-height: 400px;
    overflow-y: auto;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background-color: #f8f9fa;
}

.branch-checkbox-container .form-check {
    padding: 0.5rem;
    margin: 0;
    border-radius: 0.25rem;
    transition: background-color 0.15s ease-in-out;
}

.branch-checkbox-container .form-check:hover {
    background-color: #e9ecef;
}

.branch-checkbox-container .form-check-input {
    margin-top: 0.35rem;
    cursor: pointer;
}

.branch-checkbox-container .form-check-label {
    cursor: pointer;
    user-select: none;
    margin-left: 0.25rem;
}

/* チェックされた項目のスタイル */
.branch-checkbox-container .form-check-input:checked + .form-check-label {
    font-weight: 500;
    color: #0d6efd;
}

/* スクロールバーのスタイル（Webkit系ブラウザ用） */
.branch-checkbox-container::-webkit-scrollbar {
    width: 8px;
}

.branch-checkbox-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.branch-checkbox-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.branch-checkbox-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const businessClassificationSelect = document.getElementById('business_classification');
    const invoiceNumberField = document.getElementById('invoice_number_field');
    const invoiceNumberInput = document.getElementById('invoice_registration_number');
    const passwordField = document.getElementById('password');
    const passwordConfirmField = document.getElementById('password_confirm');
    
    // 事業者区分の変更時の処理
    function handleBusinessClassificationChange() {
        const classification = businessClassificationSelect.value;
        
        if (classification === 'taxable') {
            invoiceNumberField.style.display = 'block';
            invoiceNumberInput.setAttribute('required', 'required');
        } else {
            invoiceNumberField.style.display = 'none';
            invoiceNumberInput.removeAttribute('required');
            invoiceNumberInput.value = '';
        }
    }
    
    // インボイス番号の入力制限と自動フォーマット
    function formatInvoiceNumber(input) {
        let value = input.value.toUpperCase();
        
        if (value.length > 0 && value[0] !== 'T') {
            value = 'T' + value;
        }
        
        value = 'T' + value.substring(1).replace(/[^\d]/g, '');
        
        if (value.length > 14) {
            value = value.substring(0, 14);
        }
        
        input.value = value;
    }
    
    // インボイス番号のバリデーション
    function validateInvoiceNumber(input) {
        const value = input.value;
        const pattern = /^T\d{13}$/;
        
        if (value && !pattern.test(value)) {
            input.setCustomValidity('「T」+13桁の数字で入力してください。(例: T1234567890123)');
        } else {
            input.setCustomValidity('');
        }
    }
    
    // パスワード確認
    function validatePassword() {
        const password = passwordField.value;
        const passwordConfirm = passwordConfirmField.value;
        
        // 両方とも空の場合は問題なし(変更しない)
        if (!password && !passwordConfirm) {
            passwordField.setCustomValidity('');
            passwordConfirmField.setCustomValidity('');
            return true;
        }
        
        // どちらか一方のみ入力されている場合
        if (password && !passwordConfirm) {
            passwordConfirmField.setCustomValidity('パスワード確認を入力してください。');
            return false;
        }
        
        if (!password && passwordConfirm) {
            passwordField.setCustomValidity('パスワードを入力してください。');
            passwordConfirmField.setCustomValidity('');
            return false;
        }
        
        // パスワードが入力されている場合の要件チェック
        if (password) {
            // 8文字以上
            if (password.length < 8) {
                passwordField.setCustomValidity('パスワードは8文字以上で入力してください。');
                return false;
            }
            
            // アルファベットを含む
            if (!/[a-zA-Z]/.test(password)) {
                passwordField.setCustomValidity('パスワードにはアルファベットを含めてください。');
                return false;
            }
            
            // 数字を含む
            if (!/[0-9]/.test(password)) {
                passwordField.setCustomValidity('パスワードには数字を含めてください。');
                return false;
            }
            
            // すべての要件を満たしている
            passwordField.setCustomValidity('');
        }
        
        // 両方入力されている場合は一致確認
        if (password !== passwordConfirm) {
            passwordConfirmField.setCustomValidity('パスワードが一致しません。');
            return false;
        } else {
            passwordConfirmField.setCustomValidity('');
            return true;
        }
    }
    
    // パスワード表示切り替え
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
    
    // イベントリスナーの設定
    if (businessClassificationSelect) {
        businessClassificationSelect.addEventListener('change', handleBusinessClassificationChange);
    }
    
    if (invoiceNumberInput) {
        invoiceNumberInput.addEventListener('input', function() {
            formatInvoiceNumber(this);
            validateInvoiceNumber(this);
        });
        
        invoiceNumberInput.addEventListener('blur', function() {
            validateInvoiceNumber(this);
        });
    }
    
    passwordField.addEventListener('input', validatePassword);
    passwordConfirmField.addEventListener('input', validatePassword);
    
    // 企業ユーザー用の拠点読み込み処理
    const userTypeSelect = document.getElementById('user_type');
    const companyIdSelect = document.getElementById('company_id');
    const companyFields = document.getElementById('company-fields');
    const doctorFields = document.getElementById('doctor-fields');
    const branchSelectionArea = document.getElementById('branch-selection-area');
    
    // ユーザータイプ変更時の処理
    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function() {
            const userType = this.value;
            
            // 産業医フィールドの表示/非表示
            if (doctorFields) {
                doctorFields.style.display = userType === 'doctor' ? 'block' : 'none';
                
                // パートナーIDの必須制御
                const partnerIdField = document.getElementById('partner_id');
                const contractTypeField = document.getElementById('contract_type');
                if (partnerIdField) {
                    if (userType === 'doctor') {
                        partnerIdField.setAttribute('required', 'required');
                    } else {
                        partnerIdField.removeAttribute('required');
                    }
                }
                if (contractTypeField) {
                    if (userType === 'doctor') {
                        contractTypeField.setAttribute('required', 'required');
                    } else {
                        contractTypeField.removeAttribute('required');
                    }
                }
            }
            
            // 企業ユーザーフィールドの表示/非表示
            if (companyFields) {
                companyFields.style.display = userType === 'company' ? 'block' : 'none';
            }
            
            // 企業ユーザーに変更した場合
            if (userType === 'company') {
                // 企業が選択されていれば拠点を読み込む
                if (companyIdSelect && companyIdSelect.value && branchSelectionArea) {
                    loadBranches(companyIdSelect.value);
                } else if (branchSelectionArea) {
                    // 企業が選択されていない場合は初期メッセージを表示
                    branchSelectionArea.innerHTML = '<div class="text-muted">企業を選択してください</div>';
                }
            } else {
                // 企業ユーザー以外の場合、拠点エリアをクリア
                if (branchSelectionArea) {
                    branchSelectionArea.innerHTML = '<div class="text-muted">企業を選択してください</div>';
                }
            }
        });
    }
    
    // 拠点を読み込む関数
    function loadBranches(companyId, selectedBranchIds = []) {
        if (!companyId || !branchSelectionArea) {
            return;
        }
        
        branchSelectionArea.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin me-2"></i>読み込み中...</div>';
        
        fetch('<?= base_url("api/branches/") ?>' + companyId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.branches && data.branches.length > 0) {
                    let html = '<div class="alert alert-info mb-3">';
                    html += '<i class="fas fa-info-circle me-2"></i>';
                    html += '複数の拠点を選択できます。選択された拠点のデータにアクセスできます。';
                    html += '</div>';
                    
                    html += '<div class="branch-checkbox-container">';
                    
                    // 全選択/全解除ボタン
                    html += '<div class="mb-3">';
                    html += '<button type="button" class="btn btn-sm btn-outline-primary me-2" id="select-all-branches">';
                    html += '<i class="fas fa-check-square me-1"></i>すべて選択';
                    html += '</button>';
                    html += '<button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all-branches">';
                    html += '<i class="fas fa-square me-1"></i>すべて解除';
                    html += '</button>';
                    html += '</div>';
                    
                    // チェックボックスリスト
                    html += '<div class="row">';
                    data.branches.forEach((branch, index) => {
                        const isSelected = selectedBranchIds.includes(branch.id.toString()) || selectedBranchIds.includes(branch.id);
                        const checkboxId = `branch_${branch.id}`;
                        
                        html += '<div class="col-md-6 col-lg-4 mb-2">';
                        html += '<div class="form-check">';
                        html += `<input class="form-check-input branch-checkbox" type="checkbox" `;
                        html += `name="branch_ids[]" value="${branch.id}" id="${checkboxId}" `;
                        html += `${isSelected ? 'checked' : ''} required-group="branch">`;
                        html += `<label class="form-check-label" for="${checkboxId}">`;
                        html += `${escapeHtml(branch.name)}`;
                        html += '</label>';
                        html += '</div>';
                        html += '</div>';
                    });
                    html += '</div>'; // row
                    
                    html += '</div>'; // branch-checkbox-container
                    html += '<div class="invalid-feedback d-block" id="branch-error" style="display: none !important;">';
                    html += '少なくとも1つの拠点を選択してください。';
                    html += '</div>';
                    
                    branchSelectionArea.innerHTML = html;
                    
                    // 全選択/全解除ボタンのイベントリスナー
                    document.getElementById('select-all-branches')?.addEventListener('click', function() {
                        document.querySelectorAll('.branch-checkbox').forEach(cb => cb.checked = true);
                        validateBranchSelection();
                    });
                    
                    document.getElementById('deselect-all-branches')?.addEventListener('click', function() {
                        document.querySelectorAll('.branch-checkbox').forEach(cb => cb.checked = false);
                        validateBranchSelection();
                    });
                    
                    // チェックボックスのバリデーション
                    document.querySelectorAll('.branch-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', validateBranchSelection);
                    });
                    
                    // 初期バリデーション
                    validateBranchSelection();
                    
                } else {
                    branchSelectionArea.innerHTML = '<div class="alert alert-warning">この企業には拠点が登録されていません。</div>';
                }
            })
            .catch(error => {
                console.error('拠点の読み込みエラー:', error);
                branchSelectionArea.innerHTML = '<div class="alert alert-danger">拠点の読み込みに失敗しました。</div>';
            });
    }
    
    // 拠点選択のバリデーション
    function validateBranchSelection() {
        const checkboxes = document.querySelectorAll('.branch-checkbox');
        const checked = Array.from(checkboxes).some(cb => cb.checked);
        const errorElement = document.getElementById('branch-error');
        
        if (checkboxes.length > 0) {
            checkboxes.forEach(cb => {
                if (checked) {
                    cb.removeAttribute('required');
                } else {
                    cb.setAttribute('required', 'required');
                }
            });
            
            if (errorElement) {
                if (checked) {
                    errorElement.style.display = 'none';
                } else {
                    errorElement.style.display = 'block';
                }
            }
        }
    }
    
    // HTMLエスケープ関数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 企業選択時の処理
    if (companyIdSelect) {
        companyIdSelect.addEventListener('change', function() {
            const companyId = this.value;
            
            if (companyId) {
                loadBranches(companyId);
            } else {
                if (branchSelectionArea) {
                    branchSelectionArea.innerHTML = '<div class="text-muted">企業を選択してください</div>';
                }
            }
        });
    }
    
    // 初期化
    initPasswordToggle();
    if (businessClassificationSelect) {
        handleBusinessClassificationChange();
    }
    
    // ページ読み込み時に企業ユーザーで企業が選択されている場合は拠点を読み込む
    if (userTypeSelect && userTypeSelect.value === 'company' && companyIdSelect && companyIdSelect.value) {
        // 既存の選択済み拠点IDを取得
        const existingBranchIds = <?= json_encode(array_column($user['branches'] ?? [], 'id')) ?>;
        loadBranches(companyIdSelect.value, existingBranchIds);
    }
});
</script>