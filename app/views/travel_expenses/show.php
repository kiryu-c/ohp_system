<?php
// app/views/travel_expenses/show.php
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>交通費詳細
                        </h4>
                        <div>
                            <?php if ($userType === 'doctor' && $expense['doctor_id'] == Session::get('user_id') && $expense['status'] !== 'approved'): ?>
                                <!-- 編集ボタン（産業医、自分の記録、未承認のみ） -->
                                <a href="<?= base_url("travel_expenses/{$expense['id']}/edit") ?>" 
                                   class="btn btn-warning btn-sm me-2">
                                    <i class="fas fa-edit me-1"></i>編集
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?= base_url("service_records/{$expense['service_record_id']}") ?>" class="btn btn-light btn-sm me-2">
                                <i class="fas fa-arrow-left me-1"></i>役務詳細に戻る
                            </a>
                            <a href="<?= base_url('travel_expenses') ?>" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-list me-1"></i>交通費一覧
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- 役務記録情報 -->
                    <div class="alert alert-primary">
                        <h5 class="alert-heading mb-3">
                            <i class="fas fa-info-circle me-2"></i>対象役務記録
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>企業・拠点：</strong>
                                    <i class="fas fa-building me-1 text-success"></i>
                                    <?= htmlspecialchars($expense['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                    -
                                    <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                    <?= htmlspecialchars($expense['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="mb-1">
                                    <strong>役務日：</strong>
                                    <?= format_date($expense['service_date']) ?>
                                    (<?= date('w', strtotime($expense['service_date'])) == 0 ? '日' : 
                                           (date('w', strtotime($expense['service_date'])) == 6 ? '土' : '平日') ?>)
                                </p>
                                <p class="mb-0">
                                    <strong>役務時間：</strong>
                                    <?= htmlspecialchars($expense['start_time'], ENT_QUOTES, 'UTF-8') ?>
                                    -
                                    <?= htmlspecialchars($expense['end_time'], ENT_QUOTES, 'UTF-8') ?>
                                    (<?= format_total_hours($expense['service_hours']) ?>)
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $serviceTypeInfo = [
                                    'regular' => ['class' => 'success', 'label' => '定期訪問', 'icon' => 'calendar-check'],
                                    'emergency' => ['class' => 'warning', 'label' => '臨時訪問', 'icon' => 'exclamation-triangle'],
                                    'spot' => ['class' => 'info', 'label' => 'スポット', 'icon' => 'clock']
                                ];
                                $serviceType = $serviceTypeInfo[$expense['service_type'] ?? 'regular'];
                                ?>
                                <p class="mb-1">
                                    <strong>役務種別：</strong>
                                    <span class="badge bg-<?= $serviceType['class'] ?>">
                                        <i class="fas fa-<?= $serviceType['icon'] ?> me-1"></i>
                                        <?= $serviceType['label'] ?>
                                    </span>
                                </p>
                                <p class="mb-1">
                                    <strong>産業医：</strong>
                                    <i class="fas fa-user-md me-1 text-info"></i>
                                    <?= htmlspecialchars($expense['doctor_name'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <?php if (!empty($expense['service_description'])): ?>
                                    <p class="mb-0">
                                        <strong>業務内容：</strong>
                                        <span class="text-muted">
                                            <?= mb_strlen($expense['service_description']) > 50 ? 
                                                mb_substr($expense['service_description'], 0, 50) . '...' : 
                                                htmlspecialchars($expense['service_description'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 交通費詳細情報 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-train me-2 text-info"></i>交通費情報</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="35%">交通手段：</th>
                                    <td>
                                        <i class="<?= get_transport_type_icon($expense['transport_type']) ?> me-2"></i>
                                        <?= get_transport_type_label($expense['transport_type']) ?>
                                    </td>
                                </tr>
                                <?php if ($expense['trip_type']): ?>
                                <tr>
                                    <th>往復・片道：</th>
                                    <td>
                                        <i class="<?= get_trip_type_icon($expense['trip_type']) ?> me-2"></i>
                                        <?= get_trip_type_label($expense['trip_type']) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($expense['departure_point']): ?>
                                <tr>
                                    <th>出発地点：</th>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-success me-1"></i>
                                        <?= htmlspecialchars($expense['departure_point'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>到着地点：</th>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                        <?= htmlspecialchars($expense['arrival_point'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>金額：</th>
                                    <td>
                                        <span class="badge bg-success fs-6">
                                            <i class="fas fa-yen-sign me-1"></i>
                                            <?= number_format($expense['amount']) ?>円
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle me-2 text-warning"></i>ステータス・履歴</h5>
                            <div class="card border-<?= get_status_class($expense['status']) ?>">
                                <div class="card-body">
                                    <?php
                                    $statusInfo = [
                                        'pending' => ['class' => 'warning', 'label' => '承認待ち', 'icon' => 'clock', 'desc' => '管理者による承認を待機中です'],
                                        'approved' => ['class' => 'success', 'label' => '承認済み', 'icon' => 'check-circle', 'desc' => '管理者により承認されました'],
                                        'rejected' => ['class' => 'danger', 'label' => '差戻し', 'icon' => 'times-circle', 'desc' => '管理者より差戻しされました']
                                    ];
                                    $status = $statusInfo[$expense['status'] ?? 'pending'];
                                    ?>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-<?= $status['class'] ?> fs-6 me-3">
                                            <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                            <?= $status['label'] ?>
                                        </span>
                                        <span class="text-muted"><?= $status['desc'] ?></span>
                                    </div>
                                    
                                    <?php if (!empty($expense['admin_comment'])): ?>
                                        <div class="mt-3">
                                            <strong>管理者コメント：</strong>
                                            <div class="border-start border-<?= $status['class'] ?> ps-3 mt-2">
                                                <?= nl2br(htmlspecialchars($expense['admin_comment'], ENT_QUOTES, 'UTF-8')) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fas fa-plus-circle me-1"></i>
                                                <strong>登録日時：</strong>
                                                <?= format_date($expense['created_at'], 'Y年m月d日 H:i') ?>
                                            </small>
                                        </div>
                                        
                                        <?php if ($expense['status'] === 'approved' && !empty($expense['approved_at'])): ?>
                                            <div class="col-12 mt-1">
                                                <small class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    <strong>承認日時：</strong>
                                                    <?= format_date($expense['approved_at'], 'Y年m月d日 H:i') ?>
                                                </small>
                                            </div>
                                        <?php elseif ($expense['status'] === 'rejected' && !empty($expense['rejected_at'])): ?>
                                            <div class="col-12 mt-1">
                                                <small class="text-danger">
                                                    <i class="fas fa-times-circle me-1"></i>
                                                    <strong>差戻し日時：</strong>
                                                    <?= format_date($expense['rejected_at'], 'Y年m月d日 H:i') ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($expense['updated_at']) && $expense['updated_at'] !== $expense['created_at']): ?>
                                            <div class="col-12 mt-1">
                                                <small class="text-muted">
                                                    <i class="fas fa-edit me-1"></i>
                                                    <strong>最終更新：</strong>
                                                    <?= format_date($expense['updated_at'], 'Y年m月d日 H:i') ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- メモ・備考 -->
                    <?php if (!empty($expense['memo'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5><i class="fas fa-sticky-note me-2 text-secondary"></i>メモ・備考</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <?= nl2br(htmlspecialchars($expense['memo'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- レシート・領収書 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5><i class="fas fa-receipt me-2 text-primary"></i>レシート・領収書</h5>
                            <?php if (!empty($expense['receipt_file_path'])): ?>
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center">
                                                    <i class="<?= getFileIcon($expense['receipt_file_name']) ?> me-3 fa-2x"></i>
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($expense['receipt_file_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                                        <small class="text-muted">
                                                            ファイルサイズ: <?= formatFileSize($expense['receipt_file_size']) ?><br>
                                                            ファイル形式: <?= strtoupper(pathinfo($expense['receipt_file_name'], PATHINFO_EXTENSION)) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <a href="<?= base_url($expense['receipt_file_path']) ?>" 
                                                   target="_blank" class="btn btn-primary">
                                                    <i class="fas fa-external-link-alt me-1"></i>ファイルを表示
                                                </a>
                                                <a href="<?= base_url($expense['receipt_file_path']) ?>" 
                                                   download="<?= htmlspecialchars($expense['receipt_file_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                   class="btn btn-outline-primary ms-2">
                                                    <i class="fas fa-download me-1"></i>ダウンロード
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- 画像の場合はプレビュー表示 -->
                                        <?php if (isImageFile($expense['receipt_file_name'])): ?>
                                            <div class="mt-3">
                                                <div class="text-center">
                                                    <img src="<?= base_url($expense['receipt_file_path']) ?>" 
                                                         alt="レシート画像" 
                                                         class="img-fluid rounded shadow-sm" 
                                                         style="max-height: 400px; cursor: pointer;"
                                                         onclick="showImageModal(this.src)">
                                                    <div class="mt-2">
                                                        <small class="text-muted">画像をクリックすると拡大表示されます</small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light">
                                    <i class="fas fa-info-circle me-2"></i>
                                    レシート・領収書のファイルは添付されていません。
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 管理者用承認・差戻しフォーム -->
                    <?php if ($userType === 'admin' && $expense['status'] === 'pending'): ?>
                        <div class="row">
                            <div class="col-12">
                                <h5><i class="fas fa-clipboard-check me-2 text-primary"></i>管理者操作</h5>
                                <div class="row">
                                    <!-- 承認フォーム -->
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white">
                                                <i class="fas fa-check me-2"></i>承認
                                            </div>
                                            <div class="card-body">
                                                <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/approve") ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <div class="mb-3">
                                                        <label for="approve_comment" class="form-label">コメント（任意）</label>
                                                        <textarea class="form-control" id="approve_comment" name="comment" 
                                                                  rows="3" placeholder="承認時のコメントがあれば入力してください"></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-success w-100">
                                                        <i class="fas fa-check me-1"></i>承認する
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 差戻しフォーム -->
                                    <div class="col-md-6">
                                        <div class="card border-danger">
                                            <div class="card-header bg-danger text-white">
                                                <i class="fas fa-times me-2"></i>差戻し
                                            </div>
                                            <div class="card-body">
                                                <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/reject") ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <div class="mb-3">
                                                        <label for="reject_comment" class="form-label">差戻し理由 <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="reject_comment" name="comment" 
                                                                  rows="3" placeholder="差戻しの理由を入力してください" required></textarea>
                                                        <div class="form-text">差戻しの理由は必須です。産業医に通知されます。</div>
                                                    </div>
                                                    <button type="submit" class="btn btn-danger w-100">
                                                        <i class="fas fa-times me-1"></i>差戻しする
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 産業医用削除ボタン -->
                    <?php if ($userType === 'doctor' && $expense['doctor_id'] == Session::get('user_id') && in_array($expense['status'], ['pending', 'rejected'])): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-danger">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>危険な操作
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">
                                            この交通費記録を完全に削除します。削除後は復元できませんのでご注意ください。
                                        </p>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash me-1"></i>この交通費記録を削除
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 画像表示モーダル -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-image me-2"></i>レシート画像
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="レシート画像" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <a id="modalDownloadBtn" href="" download class="btn btn-primary">
                    <i class="fas fa-download me-1"></i>ダウンロード
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<?php if ($userType === 'doctor' && $expense['doctor_id'] == Session::get('user_id') && in_array($expense['status'], ['pending', 'rejected'])): ?>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i>交通費記録の削除
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>削除する交通費記録：</strong>
                    <ul class="list-unstyled mt-2">
                        <li><strong>役務日：</strong><?= format_date($expense['service_date']) ?></li>
                        <li><strong>交通手段：</strong><?= get_transport_type_label($expense['transport_type']) ?></li>
                        <li><strong>区間：</strong><?= format_travel_route($expense['departure_point'], $expense['arrival_point']) ?></li>
                        <li><strong>金額：</strong><?= format_amount($expense['amount']) ?></li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>注意：</strong>削除した記録は復元できません。
                    <?php if (!empty($expense['receipt_file_path'])): ?>
                        添付されたレシートファイルも同時に削除されます。
                    <?php endif; ?>
                </div>
                
                <p>本当にこの交通費記録を削除しますか？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form method="POST" action="<?= base_url("travel_expenses/{$expense['id']}/delete") ?>" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>削除する
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* カードスタイリング */
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    border-bottom: 1px solid #dee2e6;
}

.card-header.bg-info {
    background-color: #17a2b8 !important;
}

/* テーブルスタイリング */
.table th {
    font-weight: 600;
    color: #495057;
}

.table-borderless th,
.table-borderless td {
    border: none;
    padding: 0.5rem 0;
}

/* バッジスタイリング */
.badge.fs-6 {
    font-size: 1rem !important;
    padding: 0.5em 0.75em;
}

/* 境界線スタイル */
.border-start {
    border-left-width: 3px !important;
}

/* ファイル表示エリア */
.receipt-file-area {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

.receipt-file-area:hover {
    border-color: #17a2b8;
    background-color: #e3f2fd;
}

/* 画像プレビュー */
.img-fluid.rounded {
    border: 1px solid #dee2e6;
    transition: transform 0.3s ease;
}

.img-fluid.rounded:hover {
    transform: scale(1.02);
}

/* モーダルの画像 */
#modalImage {
    max-width: 100%;
    max-height: 70vh;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .btn-group .btn {
        margin-bottom: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .col-md-4, .col-md-6 {
        margin-bottom: 1rem;
    }
}

/* ステータス固有のスタイル */
.border-success {
    border-color: #28a745 !important;
}

.border-danger {
    border-color: #dc3545 !important;
}

.border-warning {
    border-color: #ffc107 !important;
}

/* アイコンの色 */
.fa-map-marker-alt.text-success {
    color: #28a745 !important;
}

.fa-map-marker-alt.text-danger {
    color: #dc3545 !important;
}

/* アラートのカスタマイズ */
.alert-primary {
    border-left: 4px solid #007bff;
}

.alert-light {
    border-left: 4px solid #f8f9fa;
    background-color: #f8f9fa;
}

/* 危険操作エリア */
.card.border-danger .card-header {
    background-color: #f8f9fa !important;
    color: #dc3545 !important;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 承認・差戻しフォームの送信確認
    const approveForm = document.querySelector('form[action*="/approve"]');
    const rejectForm = document.querySelector('form[action*="/reject"]');
    
    if (approveForm) {
        approveForm.addEventListener('submit', function(e) {
            if (!confirm('この交通費を承認しますか？\n承認後は変更できません。')) {
                e.preventDefault();
            }
        });
    }
    
    if (rejectForm) {
        rejectForm.addEventListener('submit', function(e) {
            const comment = document.getElementById('reject_comment').value.trim();
            if (!comment) {
                alert('差戻し理由を入力してください。');
                e.preventDefault();
                return;
            }
            
            if (!confirm('この交通費を差戻しますか？\n産業医に差戻し理由が通知されます。')) {
                e.preventDefault();
            }
        });
    }
    
    // ツールチップの初期化
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// 画像モーダル表示関数
function showImageModal(imageSrc) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    const modalImage = document.getElementById('modalImage');
    const downloadBtn = document.getElementById('modalDownloadBtn');
    
    modalImage.src = imageSrc;
    downloadBtn.href = imageSrc;
    
    modal.show();
}

// ファイルダウンロード時のトラッキング
document.addEventListener('click', function(e) {
    if (e.target.closest('a[download]')) {
        console.log('Receipt file downloaded');
    }
});

// 印刷機能（将来的な拡張用）
function printExpenseDetail() {
    window.print();
}

// ページ内リンクのスムーズスクロール
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        // 空のハッシュ(#のみ)やBootstrapドロップダウントグルを除外
        if (href && href !== '#' && !this.hasAttribute('data-bs-toggle')) {
            e.preventDefault();
            try {
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            } catch (error) {
                console.warn('Invalid selector:', href);
            }
        }
    });
});
</script>