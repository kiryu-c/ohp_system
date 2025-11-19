<?php
// app/views/users/change_password.php
?>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>パスワード変更
                    </h4>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="<?= base_url('password/change') ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                <i class="fas fa-lock me-1"></i>現在のパスワード
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required autofocus>
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <i class="fas fa-key me-1"></i>新しいパスワード
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            <small class="text-muted">※ アルファベットと数字を含めて8文字以上で入力してください</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-check-circle me-1"></i>新しいパスワード（確認）
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- パスワード強度インジケーター -->
                        <div class="mb-4">
                            <label class="form-label">パスワード強度</label>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar" id="password-strength-bar" 
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="password-strength-text">パスワードを入力してください</small>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>戻る
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>変更する
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- パスワードのヒント -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle me-2"></i>安全なパスワードのヒント
                    </h5>
                    <ul class="mb-0">
                        <li>8文字以上の長さにする</li>
                        <li>大文字と小文字を混在させる</li>
                        <li>数字を含める</li>
                        <li>特殊文字（!@#$%など）を含める</li>
                        <li>個人情報（名前、生年月日など）を使わない</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// パスワード表示/非表示切り替え
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// パスワード強度チェック
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    let strength = 0;
    let strengthClass = 'bg-danger';
    let strengthMessage = '弱い';
    
    if (password.length >= 8) strength += 25;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
    if (password.match(/[0-9]/)) strength += 25;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 25;
    
    if (strength <= 25) {
        strengthClass = 'bg-danger';
        strengthMessage = '弱い';
    } else if (strength <= 50) {
        strengthClass = 'bg-warning';
        strengthMessage = 'やや弱い';
    } else if (strength <= 75) {
        strengthClass = 'bg-info';
        strengthMessage = '普通';
    } else {
        strengthClass = 'bg-success';
        strengthMessage = '強い';
    }
    
    strengthBar.className = 'progress-bar ' + strengthClass;
    strengthBar.style.width = strength + '%';
    strengthText.textContent = 'パスワード強度: ' + strengthMessage;
});

// パスワード確認チェック
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('パスワードが一致しません');
    } else {
        this.setCustomValidity('');
    }
});

// 新しいパスワード入力時も確認フィールドをチェック
document.getElementById('new_password').addEventListener('input', function() {
    const confirmField = document.getElementById('confirm_password');
    if (confirmField.value) {
        confirmField.dispatchEvent(new Event('input'));
    }
});
</script>

<style>
/* パスワード切り替えボタンのスタイル */
.input-group .btn-outline-secondary {
    border-color: #ced4da;
}

.input-group .btn-outline-secondary:hover {
    background-color: #e9ecef;
    border-color: #ced4da;
}

.input-group .btn-outline-secondary:focus {
    box-shadow: none;
    border-color: #ced4da;
}

/* パスワード強度バーのアニメーション */
#password-strength-bar {
    transition: width 0.3s ease, background-color 0.3s ease;
}
</style>