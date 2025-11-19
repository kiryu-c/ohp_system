<?php
// このファイルはbase.phpのレイアウト内で$contentとして表示される
?>

<div class="row">
    <div class="col-12">
        <h2><i class="fas fa-shield-alt"></i> セキュリティ設定</h2>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- 二要素認証 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-mobile-alt"></i> 二要素認証
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center mb-3">
                    <div class="col-md-8">
                        <h6>ステータス</h6>
                        <?php if ($is2FAEnabled): ?>
                            <span class="badge bg-success fs-6">
                                <i class="fas fa-check-circle"></i> 有効
                            </span>
                            <?php if (isset($twoFactorData['two_factor_enabled_at'])): ?>
                                <p class="text-muted small mb-0 mt-2">
                                    有効化日時: <?= htmlspecialchars($twoFactorData['two_factor_enabled_at'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary fs-6">
                                <i class="fas fa-times-circle"></i> 無効
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if ($is2FAEnabled): ?>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disable2FAModal">
                                <i class="fas fa-power-off"></i> 無効化
                            </button>
                        <?php else: ?>
                            <a href="<?= base_url('two-factor/setup') ?>" class="btn btn-primary">
                                <i class="fas fa-shield-alt"></i> 有効化
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    二要素認証を有効にすると、ログイン時にパスワードに加えて、スマートフォンアプリで生成される認証コードが必要になります。
                    これによりアカウントのセキュリティが大幅に向上します。
                </div>

                <?php if ($is2FAEnabled): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-key"></i>
                        リカバリーコードは安全な場所に保管していますか? 
                        スマートフォンを紛失した場合に必要です。
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- アクティブセッション -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-desktop"></i> アクティブセッション
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($activeSessions)): ?>
                    <p class="text-muted">アクティブなセッションはありません。</p>
                <?php else: ?>
                    <div class="list-group mb-3">
                        <?php foreach ($activeSessions as $session): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php if ($session['is_remember_session']): ?>
                                                <span class="badge bg-info">長期セッション</span>
                                            <?php endif; ?>
                                            <?php if (session_id() === $session['session_id']): ?>
                                                <span class="badge bg-success">現在のセッション</span>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="mb-1 small text-muted">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?= htmlspecialchars($session['ip_address'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                        <p class="mb-1 small text-muted">
                                            <i class="fas fa-clock"></i> 
                                            最終アクティビティ: <?= htmlspecialchars($session['last_activity'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                        <p class="mb-0 small text-muted text-truncate" style="max-width: 500px;">
                                            <?= htmlspecialchars($session['user_agent'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                    <?php if (session_id() !== $session['session_id']): ?>
                                        <form method="POST" action="<?= base_url('settings/revoke-session') ?>" class="ms-2">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="session_id" value="<?= htmlspecialchars($session['session_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('このセッションを無効化しますか?')">
                                                無効化
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="POST" action="<?= base_url('settings/revoke-all-sessions') ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('現在のセッション以外のすべてのセッションを無効化しますか?')">
                            <i class="fas fa-ban"></i> 他のすべてのセッションを無効化
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">セキュリティのヒント</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        強力なパスワードを使用しましょう
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        二要素認証を有効にしましょう
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        定期的にパスワードを変更しましょう
                    </li>
                    <li class="mb-0">
                        <i class="fas fa-check text-success"></i>
                        不審なセッションがないか確認しましょう
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 2FA無効化モーダル -->
<div class="modal fade" id="disable2FAModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">二要素認証の無効化</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= base_url('two-factor/disable') ?>">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        二要素認証を無効化すると、アカウントのセキュリティレベルが低下します。
                    </div>

                    <p>続行するには、現在のパスワードを入力してください。</p>

                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-danger">無効化</button>
                </div>
            </form>
        </div>
    </div>
</div>