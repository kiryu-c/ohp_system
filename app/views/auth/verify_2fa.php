<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>二要素認証 - 産業医役務管理システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">二要素認証</h4>
                    </div>
                    <div class="card-body">
                        <?php if (Session::flash('error')): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars(Session::flash('error'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-muted">Google Authenticatorアプリで生成された6桁のコードを入力してください。</p>
                        
                        <form method="POST" action="<?= base_url('login/verify-2fa') ?>" id="verify2faForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="mb-3">
                                <label for="code" class="form-label">認証コード</label>
                                <input 
                                    type="text" 
                                    class="form-control text-center" 
                                    id="code" 
                                    name="code" 
                                    required 
                                    maxlength="8"
                                    pattern="[0-9A-Z]{6,8}"
                                    autocomplete="off"
                                    autofocus
                                    style="font-size: 1.5rem; letter-spacing: 0.5rem;"
                                >
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">
                                    確認
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <button type="button" class="btn btn-link btn-sm" id="useRecoveryBtn">
                                    リカバリーコードを使用
                                </button>
                            </div>
                        </form>
                        
                        <!-- リカバリーコードフォーム(初期非表示) -->
                        <form method="POST" action="<?= base_url('login/verify-2fa') ?>" id="recoveryForm" style="display:none;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="use_recovery_code" value="1">
                            
                            <div class="mb-3">
                                <label for="recovery_code" class="form-label">リカバリーコード</label>
                                <input 
                                    type="text" 
                                    class="form-control text-center" 
                                    id="recovery_code" 
                                    name="code" 
                                    maxlength="16"
                                    autocomplete="off"
                                    style="font-size: 1.2rem; letter-spacing: 0.2rem;"
                                >
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-warning">
                                    リカバリーコードで確認
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <button type="button" class="btn btn-link btn-sm" id="useCodeBtn">
                                    認証コードを使用
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const verify2faForm = document.getElementById('verify2faForm');
            const recoveryForm = document.getElementById('recoveryForm');
            const useRecoveryBtn = document.getElementById('useRecoveryBtn');
            const useCodeBtn = document.getElementById('useCodeBtn');
            
            useRecoveryBtn.addEventListener('click', function() {
                verify2faForm.style.display = 'none';
                recoveryForm.style.display = 'block';
                document.getElementById('recovery_code').focus();
            });
            
            useCodeBtn.addEventListener('click', function() {
                recoveryForm.style.display = 'none';
                verify2faForm.style.display = 'block';
                document.getElementById('code').focus();
            });
        });
    </script>
</body>
</html>