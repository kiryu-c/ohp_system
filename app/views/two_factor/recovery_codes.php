<?php
Session::requireLogin();
$recoveryCodes = Session::get('temp_recovery_codes');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>リカバリーコード - 産業医役務管理システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="fas fa-exclamation-triangle"></i> リカバリーコードの保存
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-circle"></i> 重要:</strong>
                            以下のリカバリーコードは、スマートフォンを紛失した場合など、認証アプリが使えなくなった時にログインするために必要です。
                            必ず安全な場所に保存してください。これらのコードは再表示されません。
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">リカバリーコード</h5>
                                <div class="row">
                                    <?php foreach ($recoveryCodes as $index => $code): ?>
                                        <div class="col-md-6 mb-2">
                                            <code class="d-block p-2 bg-light rounded">
                                                <?= $index + 1 ?>. <?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>
                                            </code>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-primary" onclick="copyCodes()">
                                        <i class="fas fa-copy"></i> すべてコピー
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="printCodes()">
                                        <i class="fas fa-print"></i> 印刷
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="downloadCodes()">
                                        <i class="fas fa-download"></i> ダウンロード
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <strong>使用方法:</strong>
                            <ul class="mb-0">
                                <li>各コードは1回のみ使用可能です</li>
                                <li>使用したコードは無効になります</li>
                                <li>すべてのコードを使い切る前に、セキュリティ設定から新しいコードを生成できます</li>
                            </ul>
                        </div>

                        <form method="POST" action="<?= base_url('two-factor/confirm-codes') ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirmSave" required>
                                <label class="form-check-label" for="confirmSave">
                                    リカバリーコードを安全な場所に保存しました
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="confirmBtn" disabled>
                                    <i class="fas fa-check"></i> 確認して完了
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const recoveryCodes = <?= json_encode($recoveryCodes) ?>;

        // チェックボックスの状態でボタンを有効化
        document.getElementById('confirmSave').addEventListener('change', function() {
            document.getElementById('confirmBtn').disabled = !this.checked;
        });

        // すべてのコードをコピー
        function copyCodes() {
            const codesText = recoveryCodes.map((code, index) => `${index + 1}. ${code}`).join('\n');
            navigator.clipboard.writeText(codesText).then(() => {
                alert('リカバリーコードをコピーしました');
            });
        }

        // 印刷
        function printCodes() {
            window.print();
        }

        // テキストファイルとしてダウンロード
        function downloadCodes() {
            const codesText = `産業医役務管理システム - リカバリーコード\n生成日時: ${new Date().toLocaleString('ja-JP')}\n\n` +
                recoveryCodes.map((code, index) => `${index + 1}. ${code}`).join('\n') +
                '\n\n注意: これらのコードは安全な場所に保管してください。';
            
            const blob = new Blob([codesText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'recovery-codes.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // 印刷用CSS
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                body * {
                    visibility: hidden;
                }
                .card, .card * {
                    visibility: visible;
                }
                .card {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
                .btn {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>