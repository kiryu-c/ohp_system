<?php
// app/views/auth/login.php

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - 産業医役務管理システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow">
                    <div class="login-header">
                        <h2 class="mb-0">
                            <i class="fas fa-user-md"></i>
                            産業医<br>役務管理システム
                        </h2>
                    </div>
                    
                    <div class="login-body">
                        <?php 
                        // Flash メッセージの安全な取得と表示
                        $errorMessage = Session::flash('error');
                        if ($errorMessage): 
                        ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        $successMessage = Session::flash('success');
                        if ($successMessage): 
                        ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i>
                                <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        $warningMessage = Session::flash('warning');
                        if ($warningMessage): 
                        ?>
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?= htmlspecialchars($warningMessage, ENT_QUOTES, 'UTF-8') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        $infoMessage = Session::flash('info');
                        if ($infoMessage): 
                        ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle"></i>
                                <?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8') ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?= base_url('login') ?>" id="loginForm" autocomplete="on">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="mb-3">
                                <label for="login_id" class="form-label">
                                    <i class="fas fa-user"></i> ログインID
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="login_id" 
                                    name="login_id" 
                                    value="<?= htmlspecialchars($_POST['login_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    required 
                                    maxlength="50"
                                    autocomplete="username"
                                    autofocus
                                >
                                <div class="invalid-feedback">
                                    ログインIDを入力してください。
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> パスワード
                                </label>
                                <div class="input-group">
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="password" 
                                        name="password" 
                                        required 
                                        maxlength="255"
                                        autocomplete="current-password"
                                    >
                                    <button 
                                        type="button" 
                                        class="btn btn-outline-secondary" 
                                        id="togglePassword"
                                        tabindex="-1"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    パスワードを入力してください。
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember_session" name="remember_session">
                                    <label class="form-check-label" for="remember_session">
                                        このデバイスでセッションを保持する
                                    </label>
                                </div>
                            </div>
                            
                            <p><a href="/password-request">パスワードをお忘れの方</a></p>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="loginButton">
                                    <i class="fas fa-sign-in-alt"></i> ログイン
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- セキュリティ通知エリア -->
                <div class="text-center mt-3">
                    <small class="text-muted">
                        セキュリティを保つため、作業終了時は必ずログアウトしてください。
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- セッションタイムアウト警告モーダル -->
    <div class="modal fade" id="timeoutWarningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-clock"></i> セッション警告
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <p>セッションがまもなく終了します。</p>
                    <p>残り時間: <span id="remainingTime"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="extendSession">
                        セッション延長
                    </button>
                    <button type="button" class="btn btn-secondary" id="logoutNow">
                        ログアウト
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // パスワード表示/非表示切り替え
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                });
            }
            
            // フォーム送信時のセキュリティチェック
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            
            if (loginForm && loginButton) {
                loginForm.addEventListener('submit', function(e) {
                    // 二重送信防止
                    loginButton.disabled = true;
                    loginButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 認証中...';
                    
                    // 5秒後にボタンを再有効化（通信エラー対策）
                    setTimeout(() => {
                        loginButton.disabled = false;
                        loginButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> ログイン';
                    }, 5000);
                    
                    // 基本的な入力値検証
                    const loginIdInput = document.getElementById('login_id');
                    const passwordInput = document.getElementById('password');
                    
                    if (!loginIdInput || !passwordInput) {
                        e.preventDefault();
                        alert('システムエラーが発生しました。');
                        loginButton.disabled = false;
                        loginButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> ログイン';
                        return;
                    }
                    
                    const loginId = loginIdInput.value.trim();
                    const password = passwordInput.value;
                    
                    if (loginId.length === 0 || password.length === 0) {
                        e.preventDefault();
                        alert('ログインIDとパスワードを入力してください。');
                        loginButton.disabled = false;
                        loginButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> ログイン';
                        return;
                    }
                    
                    // 異常に長い入力値のチェック
                    if (loginId.length > 50 || password.length > 255) {
                        e.preventDefault();
                        alert('入力値が長すぎます。');
                        loginButton.disabled = false;
                        loginButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> ログイン';
                        return;
                    }
                });
            }
            
            // Caps Lock警告
            if (passwordInput) {
                passwordInput.addEventListener('keyup', function(e) {
                    if (e.getModifierState && e.getModifierState('CapsLock')) {
                        if (!document.getElementById('capsLockWarning')) {
                            const warning = document.createElement('div');
                            warning.id = 'capsLockWarning';
                            warning.className = 'alert alert-warning mt-2';
                            warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Caps Lockがオンになっています';
                            
                            const parentContainer = passwordInput.closest('.mb-3');
                            if (parentContainer) {
                                parentContainer.appendChild(warning);
                            }
                        }
                    } else {
                        const warning = document.getElementById('capsLockWarning');
                        if (warning) {
                            warning.remove();
                        }
                    }
                });
            }
            
            // ブラウザバック防止（セキュリティ対策）
            history.pushState(null, null, location.href);
            window.addEventListener('popstate', function(event) {
                history.pushState(null, null, location.href);
            });
            
            // 自動アラート削除（5秒後）
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        alert.classList.remove('show');
                        setTimeout(function() {
                            if (alert && alert.parentNode) {
                                alert.remove();
                            }
                        }, 150); // Bootstrap fade out transition time
                    }
                }, 5000);
            });
        });
    </script>
</body>
</html>