<?php
Session::requireLogin();
$userName = Session::get('user_name');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>二要素認証の設定 - 産業医役務管理システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-shield-alt"></i> 二要素認証の設定
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            二要素認証を設定すると、ログイン時にパスワードに加えて、スマートフォンアプリで生成される6桁のコードが必要になります。
                        </div>

                        <h5 class="mb-3">ステップ1: アプリのインストール</h5>
                        <p>Google Authenticatorアプリをスマートフォンにインストールしてください。</p>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fab fa-apple fa-3x mb-2"></i>
                                        <h6>iPhone</h6>
                                        <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" class="btn btn-sm btn-outline-primary">
                                            App Store
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fab fa-android fa-3x mb-2"></i>
                                        <h6>Android</h6>
                                        <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="btn btn-sm btn-outline-primary">
                                            Google Play
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3">ステップ2: QRコードをスキャン</h5>
                        <p>Google Authenticatorアプリで以下のQRコードをスキャンしてください。</p>
                        
                        <div class="text-center mb-4">
                            <div id="qrcode"></div>
                            <p class="text-muted mt-2 small">
                                QRコードがスキャンできない場合は、以下のキーを手動で入力してください：
                            </p>
                            <code id="secretKey" class="user-select-all"><?= htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') ?></code>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="copySecret()">
                                <i class="fas fa-copy"></i> コピー
                            </button>
                        </div>

                        <h5 class="mb-3">ステップ3: 認証コードの確認</h5>
                        <p>アプリに表示される6桁のコードを入力して、設定を完了してください。</p>
                        
                        <form method="POST" action="<?= base_url('two-factor/enable') ?>" id="enableForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="mb-3">
                                <label for="code" class="form-label">認証コード</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="code" 
                                    name="code" 
                                    required 
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    placeholder="6桁の数字"
                                    autocomplete="off"
                                >
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> 二要素認証を有効化
                                </button>
                                <a href="<?= base_url('settings/security') ?>" class="btn btn-outline-secondary">
                                    キャンセル
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // QRコード生成
        const qrCodeUrl = <?= json_encode($qrCodeUrl) ?>;
        new QRCode(document.getElementById("qrcode"), {
            text: qrCodeUrl,
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // 秘密鍵コピー機能
        function copySecret() {
            const secretKey = document.getElementById('secretKey').textContent;
            navigator.clipboard.writeText(secretKey).then(() => {
                alert('秘密鍵をコピーしました');
            });
        }

        // 数字のみ入力
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>