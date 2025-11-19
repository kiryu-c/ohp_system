<?php
// app/views/email_logs/detail.php
?>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<!-- ヘッダー -->
<div class="header-section mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-envelope-open-text me-2"></i>メール送信ログ詳細</h2>
        <div class="header-buttons">
            <a href="<?= base_url('email-logs') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>一覧に戻る
            </a>
        </div>
    </div>
</div>

<!-- 基本情報カード -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>基本情報</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th style="width: 150px;">ログID</th>
                        <td><span class="badge bg-secondary fs-6">#<?= $log['id'] ?></span></td>
                    </tr>
                    <tr>
                        <th>メール種別</th>
                        <td>
                            <?php
                            $typeIcons = [
                                'password_reset' => '<i class="fas fa-key text-warning fa-lg"></i>',
                                'login_invitation' => '<i class="fas fa-user-plus text-info fa-lg"></i>',
                                'notification' => '<i class="fas fa-bell text-primary fa-lg"></i>',
                                'other' => '<i class="fas fa-envelope text-secondary fa-lg"></i>'
                            ];
                            $typeLabels = [
                                'password_reset' => 'パスワード再設定',
                                'login_invitation' => 'ログイン招待',
                                'notification' => '通知',
                                'other' => 'その他'
                            ];
                            echo $typeIcons[$log['email_type']] . ' <strong>' . $typeLabels[$log['email_type']] . '</strong>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>ステータス</th>
                        <td>
                            <?php if ($log['status'] === 'success'): ?>
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-check-circle"></i> 成功
                                </span>
                            <?php elseif ($log['status'] === 'failed'): ?>
                                <span class="badge bg-danger fs-6">
                                    <i class="fas fa-times-circle"></i> 失敗
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary fs-6">
                                    <i class="fas fa-clock"></i> 保留中
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th style="width: 150px;">送信日時</th>
                        <td>
                            <?php if ($log['sent_at']): ?>
                                <i class="fas fa-calendar-check text-success"></i>
                                <?= date('Y年m月d日 H:i:s', strtotime($log['sent_at'])) ?>
                            <?php else: ?>
                                <span class="text-muted">未送信</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>ログ記録日時</th>
                        <td>
                            <i class="fas fa-clock text-muted"></i>
                            <?= date('Y年m月d日 H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>送信実行者</th>
                        <td>
                            <?php if ($log['created_by_name']): ?>
                                <i class="fas fa-user text-info"></i>
                                <?= htmlspecialchars($log['created_by_name'], ENT_QUOTES, 'UTF-8') ?>
                                <small class="text-muted">(ID: <?= htmlspecialchars($log['created_by_login_id'], ENT_QUOTES, 'UTF-8') ?>)</small>
                            <?php else: ?>
                                <i class="fas fa-robot text-muted"></i>
                                <span class="text-muted">システム自動送信</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if ($log['error_message']): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-danger mb-0">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-circle"></i> エラーメッセージ
                        </h6>
                        <pre class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($log['error_message'], ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 送信者情報カード -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>送信者情報</h5>
    </div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <th style="width: 200px;">送信者メールアドレス</th>
                <td>
                    <i class="fas fa-envelope text-primary"></i>
                    <?= htmlspecialchars($log['sender_email'], ENT_QUOTES, 'UTF-8') ?>
                </td>
            </tr>
            <tr>
                <th>送信者名</th>
                <td>
                    <?php if ($log['sender_name']): ?>
                        <i class="fas fa-user text-info"></i>
                        <?= htmlspecialchars($log['sender_name'], ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- 受信者情報カード -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-inbox me-2"></i>受信者情報</h5>
    </div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <th style="width: 200px;">受信者メールアドレス</th>
                <td>
                    <i class="fas fa-envelope text-primary"></i>
                    <?= htmlspecialchars($log['recipient_email'], ENT_QUOTES, 'UTF-8') ?>
                </td>
            </tr>
            <tr>
                <th>受信者名</th>
                <td>
                    <?php if ($log['recipient_name']): ?>
                        <i class="fas fa-user text-info"></i>
                        <?= htmlspecialchars($log['recipient_name'], ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($log['recipient_user_id']): ?>
                <tr>
                    <th>受信者ユーザー</th>
                    <td>
                        <i class="fas fa-user-circle text-info"></i>
                        <a href="<?= base_url('users/' . $log['recipient_user_id'] . '/edit') ?>">
                            <?= htmlspecialchars($log['recipient_user_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <small class="text-muted">(ID: <?= htmlspecialchars($log['recipient_login_id'], ENT_QUOTES, 'UTF-8') ?>)</small>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- メール内容カード -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>メール内容</h5>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <h6 class="text-muted mb-2">
                <i class="fas fa-heading"></i> 件名
            </h6>
            <p class="fs-5 mb-0"><?= htmlspecialchars($log['subject'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <!-- HTML本文 -->
        <?php if ($log['body_html']): ?>
            <div class="mb-4">
                <h6 class="text-muted mb-2">
                    <i class="fas fa-code"></i> HTML本文
                </h6>
                <ul class="nav nav-tabs" id="emailBodyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="preview-tab" data-bs-toggle="tab" 
                                data-bs-target="#preview" type="button" role="tab">
                            <i class="fas fa-eye"></i> プレビュー
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="html-tab" data-bs-toggle="tab" 
                                data-bs-target="#html" type="button" role="tab">
                            <i class="fas fa-code"></i> HTMLソース
                        </button>
                    </li>
                </ul>
                <div class="tab-content border border-top-0 p-3" id="emailBodyTabsContent">
                    <div class="tab-pane fade show active" id="preview" role="tabpanel">
                        <div class="border rounded p-3" style="background-color: #f8f9fa; max-height: 600px; overflow-y: auto;">
                            <?= $log['body_html'] ?>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="html" role="tabpanel">
                        <pre class="bg-dark text-light p-3 rounded mb-0" style="max-height: 600px; overflow-y: auto;"><code><?= htmlspecialchars($log['body_html'], ENT_QUOTES, 'UTF-8') ?></code></pre>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- テキスト本文 -->
        <?php if ($log['body_text']): ?>
            <div class="mb-4">
                <h6 class="text-muted mb-2">
                    <i class="fas fa-align-left"></i> テキスト本文
                </h6>
                <pre class="bg-light p-3 rounded border mb-0" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap;"><?= htmlspecialchars($log['body_text'], ENT_QUOTES, 'UTF-8') ?></pre>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- メタデータカード -->
<?php if (!empty($log['metadata_decoded'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>追加情報</h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless mb-0">
                <?php foreach ($log['metadata_decoded'] as $key => $value): ?>
                    <tr>
                        <th style="width: 250px;">
                            <?php
                            // キー名を日本語表示に変換
                            $keyLabels = [
                                'login_id' => 'ログインID',
                                'sent_by_admin_id' => '送信者(管理者)ID',
                                'sent_by_admin_name' => '送信者(管理者)名',
                                'sent_by_user_id' => '送信者ユーザーID',
                                'sent_by_user_name' => '送信者ユーザー名',
                                'token' => 'トークン(一部)'
                            ];
                            echo $keyLabels[$key] ?? htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                            ?>
                        </th>
                        <td>
                            <?php if ($key === 'sent_by_admin_name' || $key === 'sent_by_user_name'): ?>
                                <i class="fas fa-user-shield text-info"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <!-- JSON表示も残す(開発者向け) -->
            <details class="mt-3">
                <summary class="text-muted" style="cursor: pointer;">
                    <small><i class="fas fa-code"></i> JSON形式で表示</small>
                </summary>
                <pre class="bg-light p-3 rounded border mt-2 mb-0" style="max-height: 300px; overflow-y: auto;"><?= json_encode($log['metadata_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
            </details>
        </div>
    </div>
<?php endif; ?>

<!-- 削除ボタン -->
<div class="card border-danger mb-4">
    <div class="card-body">
        <h6 class="text-danger mb-3">
            <i class="fas fa-exclamation-triangle"></i> 危険な操作
        </h6>
        <p class="text-muted">
            このログを削除すると、送信履歴が完全に失われます。この操作は取り消せません。
        </p>
        <form method="POST" action="<?= base_url('email-logs/delete') ?>" 
              onsubmit="return confirm('このログを削除してもよろしいですか?\n\nこの操作は取り消せません。');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= $log['id'] ?>">
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> このログを削除
            </button>
        </form>
    </div>
</div>

<style>
/* タブのカスタムスタイル */
#emailBodyTabs .nav-link {
    color: #495057;
}
#emailBodyTabs .nav-link.active {
    font-weight: bold;
}

/* プレビューエリアのメール内スタイルをリセット */
#preview table {
    margin-bottom: 0 !important;
}

/* コードブロックのスタイル */
pre code {
    font-size: 0.875rem;
    line-height: 1.5;
}
</style>