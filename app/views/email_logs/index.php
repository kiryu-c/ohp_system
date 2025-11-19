<?php
// app/views/email_logs/index.php

// ページネーションURL構築のヘルパー関数
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return base_url('email-logs') . '?' . http_build_query($params);
}
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
        <h2><i class="fas fa-envelope-open-text me-2"></i>メール送信ログ</h2>
        <div class="header-buttons">
            <a href="<?= base_url('email-logs?export=csv&' . http_build_query($_GET)) ?>" class="btn btn-success">
                <i class="fas fa-file-csv me-1"></i>CSVエクスポート
            </a>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                <i class="fas fa-trash-alt me-1"></i>古いログを削除
            </button>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>ダッシュボードに戻る
            </a>
        </div>
    </div>
</div>

<!-- 検索・フィルター -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>フィルター条件
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= base_url('email-logs') ?>" class="filter-form" id="filterForm">
            <div class="row g-3">
                <div class="col-md-2 col-6">
                    <label for="email_type" class="form-label">メール種別</label>
                    <select class="form-select" id="email_type" name="email_type">
                        <option value="">全て</option>
                        <option value="password_reset" <?= ($_GET['email_type'] ?? '') === 'password_reset' ? 'selected' : '' ?>>パスワード再設定</option>
                        <option value="login_invitation" <?= ($_GET['email_type'] ?? '') === 'login_invitation' ? 'selected' : '' ?>>ログイン招待</option>
                        <option value="notification" <?= ($_GET['email_type'] ?? '') === 'notification' ? 'selected' : '' ?>>通知</option>
                        <option value="other" <?= ($_GET['email_type'] ?? '') === 'other' ? 'selected' : '' ?>>その他</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label for="status" class="form-label">ステータス</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全て</option>
                        <option value="success" <?= ($_GET['status'] ?? '') === 'success' ? 'selected' : '' ?>>成功</option>
                        <option value="failed" <?= ($_GET['status'] ?? '') === 'failed' ? 'selected' : '' ?>>失敗</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>保留中</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label for="recipient_email" class="form-label">受信者メール</label>
                    <input type="text" class="form-control" id="recipient_email" name="recipient_email" 
                           value="<?= htmlspecialchars($_GET['recipient_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="部分一致検索">
                </div>
                <div class="col-md-2 col-6">
                    <label for="start_date" class="form-label">開始日</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?= htmlspecialchars($_GET['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label for="end_date" class="form-label">終了日</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?= htmlspecialchars($_GET['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-2 col-12">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2 filter-buttons">
                        <button type="submit" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-search"></i>
                            <span class="btn-text">検索</span>
                        </button>
                        <a href="<?= base_url('email-logs') ?>" class="btn btn-outline-secondary flex-fill">
                            <i class="fas fa-redo"></i>
                            <span class="btn-text">リセット</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- モバイル用並び替え (1024px未満) -->
<div class="mobile-sort-section d-lg-none mb-3">
    <label for="mobile_sort" class="form-label">並び替え</label>
    <select class="form-select" id="mobile_sort">
        <option value="created_at-desc" <?= ($sortColumn ?? 'created_at') === 'created_at' && ($sortOrder ?? 'desc') === 'desc' ? 'selected' : '' ?>>
            送信日時(新しい順)
        </option>
        <option value="created_at-asc" <?= ($sortColumn ?? '') === 'created_at' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            送信日時(古い順)
        </option>
        <option value="email_type-asc" <?= ($sortColumn ?? '') === 'email_type' && ($sortOrder ?? '') === 'asc' ? 'selected' : '' ?>>
            メール種別(昇順)
        </option>
        <option value="status-desc" <?= ($sortColumn ?? '') === 'status' && ($sortOrder ?? '') === 'desc' ? 'selected' : '' ?>>
            ステータス(失敗優先)
        </option>
    </select>
</div>

<!-- 統計情報 -->
<div class="row mb-4">
    <div class="col-md-3 col-6">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?= number_format($stats['total']) ?></h3>
                <p class="mb-0">総送信数(30日)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?= number_format($stats['success']) ?></h3>
                <p class="mb-0">成功</p>
                <small>
                    <?php 
                    $successRate = $stats['total'] > 0 ? ($stats['success'] / $stats['total'] * 100) : 0;
                    echo number_format($successRate, 1) . '%'; 
                    ?>
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3 class="mb-1"><?= number_format($stats['failed']) ?></h3>
                <p class="mb-0">失敗</p>
                <small>
                    <?php 
                    $failRate = $stats['total'] > 0 ? ($stats['failed'] / $stats['total'] * 100) : 0;
                    echo number_format($failRate, 1) . '%'; 
                    ?>
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <p class="mb-2 small">種別別内訳</p>
                <small class="d-block">
                    <i class="fas fa-key"></i> パスワード: 
                    <?= $stats['by_type']['password_reset']['success'] + $stats['by_type']['password_reset']['failed'] ?>
                </small>
                <small class="d-block">
                    <i class="fas fa-user-plus"></i> 招待: 
                    <?= $stats['by_type']['login_invitation']['success'] + $stats['by_type']['login_invitation']['failed'] ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ログ一覧 -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>メール送信ログ一覧
                    <?php if ($total > 0): ?>
                        <span class="badge bg-primary">全<?= number_format($total) ?>件</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <!-- ページサイズ選択 -->
                    <div class="d-flex align-items-center">
                        <label for="email_logs_page_size" class="form-label me-2 mb-0 text-muted small">表示件数:</label>
                        <select id="email_logs_page_size" class="form-select form-select-sm" style="width: auto;">
                            <option value="20" <?= ($perPage == 20) ? 'selected' : '' ?>>20件</option>
                            <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50件</option>
                            <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100件</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>ログが見つかりませんでした。
                検索条件を変更してください。
            </div>
        <?php else: ?>
            
            <!-- デスクトップ表示（1024px以上） -->
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 140px;">種別</th>
                            <th style="width: 100px;">ステータス</th>
                            <th>受信者</th>
                            <th>件名</th>
                            <th style="width: 150px;">送信日時</th>
                            <th style="width: 100px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">#<?= $log['id'] ?></span>
                                </td>
                                <td>
                                    <?php
                                    $typeIcons = [
                                        'password_reset' => '<i class="fas fa-key text-warning"></i>',
                                        'login_invitation' => '<i class="fas fa-user-plus text-info"></i>',
                                        'notification' => '<i class="fas fa-bell text-primary"></i>',
                                        'other' => '<i class="fas fa-envelope text-secondary"></i>'
                                    ];
                                    $typeLabels = [
                                        'password_reset' => 'パスワード再設定',
                                        'login_invitation' => 'ログイン招待',
                                        'notification' => '通知',
                                        'other' => 'その他'
                                    ];
                                    echo $typeIcons[$log['email_type']] . ' ';
                                    ?>
                                    <small><?= $typeLabels[$log['email_type']] ?></small>
                                </td>
                                <td>
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> 成功</span>
                                    <?php elseif ($log['status'] === 'failed'): ?>
                                        <span class="badge bg-danger"><i class="fas fa-times-circle"></i> 失敗</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-clock"></i> 保留中</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($log['recipient_email'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <?php if ($log['recipient_name'] || $log['recipient_user_name']): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($log['recipient_name'] ?: $log['recipient_user_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 300px;" 
                                         title="<?= htmlspecialchars($log['subject'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($log['subject'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if ($log['error_message']): ?>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?= htmlspecialchars(mb_strimwidth($log['error_message'], 0, 50, '...'), ENT_QUOTES, 'UTF-8') ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['sent_at']): ?>
                                        <div><?= date('Y/m/d', strtotime($log['sent_at'])) ?></div>
                                        <small class="text-muted"><?= date('H:i', strtotime($log['sent_at'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('email-logs/detail?id=' . $log['id']) ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> 詳細
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- モバイル表示（1024px未満） -->
            <div class="d-lg-none">
                <?php foreach ($logs as $log): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-secondary me-2">#<?= $log['id'] ?></span>
                                    <?php
                                    $typeIcons = [
                                        'password_reset' => '<i class="fas fa-key text-warning"></i>',
                                        'login_invitation' => '<i class="fas fa-user-plus text-info"></i>',
                                        'notification' => '<i class="fas fa-bell text-primary"></i>',
                                        'other' => '<i class="fas fa-envelope text-secondary"></i>'
                                    ];
                                    $typeLabels = [
                                        'password_reset' => 'パスワード再設定',
                                        'login_invitation' => 'ログイン招待',
                                        'notification' => '通知',
                                        'other' => 'その他'
                                    ];
                                    echo $typeIcons[$log['email_type']] . ' ';
                                    echo '<small>' . $typeLabels[$log['email_type']] . '</small>';
                                    ?>
                                </div>
                                <?php if ($log['status'] === 'success'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> 成功</span>
                                <?php elseif ($log['status'] === 'failed'): ?>
                                    <span class="badge bg-danger"><i class="fas fa-times-circle"></i> 失敗</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fas fa-clock"></i> 保留中</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">受信者:</small><br>
                                <strong><?= htmlspecialchars($log['recipient_email'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if ($log['recipient_name'] || $log['recipient_user_name']): ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($log['recipient_name'] ?: $log['recipient_user_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">件名:</small><br>
                                <?= htmlspecialchars($log['subject'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            
                            <?php if ($log['error_message']): ?>
                                <div class="mb-2">
                                    <small class="text-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?= htmlspecialchars(mb_strimwidth($log['error_message'], 0, 100, '...'), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <small class="text-muted">送信日時:</small>
                                <?php if ($log['sent_at']): ?>
                                    <?= date('Y/m/d H:i', strtotime($log['sent_at'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-3">
                                <a href="<?= base_url('email-logs/detail?id=' . $log['id']) ?>" 
                                   class="btn btn-sm btn-info w-100">
                                    <i class="fas fa-eye"></i> 詳細を表示
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ページネーション -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="ページネーション" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- 前へ -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl($page - 1) ?>" aria-label="前へ">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- ページ番号 -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        if ($start > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl(1) ?>">1</a>
                            </li>
                            <?php if ($start > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl($totalPages) ?>"><?= $totalPages ?></a>
                            </li>
                        <?php endif; ?>

                        <!-- 次へ -->
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl($page + 1) ?>" aria-label="次へ">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <p class="text-center text-muted">
                    全<?= number_format($total) ?>件中 
                    <?= number_format(($page - 1) * $perPage + 1) ?>〜<?= number_format(min($page * $perPage, $total)) ?>件を表示
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- 古いログ削除モーダル -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= base_url('email-logs/bulk-delete') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title">古いログの一括削除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        この操作は取り消せません。慎重に実行してください。
                    </div>
                    <div class="mb-3">
                        <label class="form-label">削除対象期間</label>
                        <select name="months" class="form-select" required>
                            <option value="">選択してください</option>
                            <option value="3">3ヶ月より古いログ</option>
                            <option value="6">6ヶ月より古いログ</option>
                            <option value="12">12ヶ月より古いログ</option>
                            <option value="24">24ヶ月より古いログ</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        キャンセル
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> 削除実行
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ページサイズ変更時の処理
document.getElementById('email_logs_page_size').addEventListener('change', function() {
    const url = new URL(window.location);
    url.searchParams.set('per_page', this.value);
    url.searchParams.set('page', '1');
    window.location = url;
});

// モバイル並び替え
document.getElementById('mobile_sort')?.addEventListener('change', function() {
    const [column, order] = this.value.split('-');
    const url = new URL(window.location);
    url.searchParams.set('sort', column);
    url.searchParams.set('order', order);
    url.searchParams.set('page', '1');
    window.location = url;
});
</script>